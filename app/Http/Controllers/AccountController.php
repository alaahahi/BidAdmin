<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountDocument;
use App\Models\ActivityLog;
use App\Models\AssignmentHistory;
use App\Models\Broker;
use App\Models\Customer;
use App\Support\Csv;
use App\Support\Lists;
use App\Support\Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AccountController extends Controller
{
    /** Customers only see invoice figures for vehicles they won themselves. */
    private static function own($q)
    {
        return Scope::isCustomer() ? $q->where('won_by_customer_id', Scope::customerId()) : $q;
    }

    private function query(Request $r)
    {
        $q = Scope::accounts(Account::query())
            ->withCount([
                'invoices' => fn ($i) => self::own($i),
                'invoices as paid_invoices_count' => fn ($i) => self::own($i->where('status', 'paid')),
                'invoices as unpaid_invoices_count' => fn ($i) => self::own($i->where('status', '!=', 'paid')),
                'customers', 'brokers',
            ]);
        if ($r->input('trashed') === '1' && ! Scope::isCustomer()) {
            $q->onlyTrashed();
        }
        Lists::search($q, $r->input('search'), ['username', 'company', 'provider', 'region', 'account_number']);
        foreach (['provider', 'region', 'action_status', 'status'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        if ($r->filled('tag')) {
            $q->where('tags', 'like', '%"'.$r->input('tag').'"%');
        }

        return Lists::sort($q, $r, ['provider', 'type', 'username', 'company', 'balance_due', 'status', 'created_at']);
    }

    public function index(Request $r)
    {
        $base = Scope::accounts(Account::query());
        $ids = (clone $base)->pluck('id');
        $unpaid = self::own(\App\Models\Invoice::whereIn('account_id', $ids)->where('status', '!=', 'paid'));

        return Inertia::render('accounts/index', [
            'accounts' => $this->query($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['provider', 'region', 'action_status', 'tag']),
            'stats' => [
                'total' => $ids->count(),
                'action_required' => (clone $base)->where('action_status', '!=', 'ok')->count(),
                'total_overdue' => (float) (clone $unpaid)->where('status', 'overdue')->sum('amount_due'),
                'total_unpaid' => (float) (clone $unpaid)->sum('amount_due'),
                'unpaid_invoices' => (clone $unpaid)->count(),
            ],
            'options' => $this->options(),
        ]);
    }

    public function options(): array
    {
        return [
            'providers' => Account::PROVIDERS,
            'regions' => Account::REGIONS,
            'tags' => Account::whereNotNull('tags')->pluck('tags')->flatten()->unique()->values(),
            'action_statuses' => ['ok' => 'No Action Needed', 'password_expired' => 'Password Expired', 'verification' => 'Verification Required', 'payment_due' => 'Payment Due', 'suspended' => 'Suspended'],
        ];
    }

    public function export(Request $r)
    {
        return Csv::download('accounts', $this->query($r)->cursor(), [
            'Provider' => fn ($a) => Account::PROVIDERS[$a->provider] ?? $a->provider,
            'Region' => 'region', 'Type' => 'type', 'Username' => 'username', 'Company' => 'company',
            'Balance Due' => 'balance_due', 'Invoices' => 'invoices_count', 'Unpaid Invoices' => 'unpaid_invoices_count',
            'Status' => 'status', 'Action Status' => 'action_status', 'Tags' => fn ($a) => implode('|', $a->tags ?? []),
        ]);
    }

    private function rules(?Account $a = null): array
    {
        return [
            'provider' => ['required', Rule::in(array_keys(Account::PROVIDERS))],
            'region' => ['required', 'string', 'max:10'],
            'username' => ['required', 'string', 'max:190'],
            'password' => [$a ? 'nullable' : 'required', 'string', 'max:190'],
            'type' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:190'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'external_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'balance_due' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'error'])],
            'action_status' => ['nullable', 'string', 'max:30'],
            'tags' => ['nullable', 'array'], 'tags.*' => ['string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'membership_plan' => ['nullable', 'string', 'max:100'],
            'next_billing_date' => ['nullable', 'date'],
        ];
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $data['account_number'] ??= $data['username'];
        $data['last_synced_at'] = now();
        $account = Account::create($data);

        return redirect()->route('accounts.show', $account)->with('success', 'Account added.');
    }

    public function show(Request $r, int $id)
    {
        $account = Scope::accounts(Account::withTrashed())->findOrFail($id);
        $tab = $r->input('tab', 'overview');
        $isAdmin = ! Scope::isCustomer();
        $account->load($isAdmin ? ['customers', 'brokers', 'documents'] : []);
        $inv = self::own($account->invoices()->getQuery());
        $data = [
            'account' => $account,
            'tab' => $tab,
            'stats' => [
                'customers' => $account->customers()->count(),
                'brokers' => $account->brokers()->count(),
                'total_invoices' => (clone $inv)->count(),
                'paid_invoices' => (clone $inv)->where('status', 'paid')->count(),
                'pending_invoices' => (clone $inv)->where('status', '!=', 'paid')->count(),
                'total_value' => (float) (clone $inv)->sum('amount'),
                'unpaid' => (float) (clone $inv)->where('status', '!=', 'paid')->sum('amount_due'),
                'due_soon' => (float) (clone $inv)->where('status', 'pending')->whereBetween('due_date', [now()->startOfDay(), now()->addDays(3)])->sum('amount_due'),
                'overdue' => (float) (clone $inv)->where('status', 'overdue')->sum('amount_due'),
            ],
            'options' => $this->options(),
            'isAdmin' => $isAdmin,
        ];
        $data['bids'] = Scope::owned($account->bids()->getQuery())->with('customer:id,name')->latest('placed_at')->paginate(10, ['*'], 'bids_page')->withQueryString();
        $data['invoices'] = self::own($account->invoices()->getQuery())->with('wonBy:id,name')->latest('invoice_date')->paginate(10, ['*'], 'inv_page')->withQueryString();
        if ($isAdmin) {
            $data['allCustomers'] = Customer::orderBy('name')->get(['id', 'name', 'code']);
            $data['allBrokers'] = Broker::orderBy('name')->get(['id', 'name', 'email']);
            $data['history'] = $account->histories()->with('user:id,name')->limit(50)->get();
            $data['documentGroups'] = AccountDocument::GROUPS;
        }
        if ($tab === 'analytics') {
            $data['analytics'] = \App\Support\Analytics::build(['account_id' => $account->id, 'days' => 30]);
        }

        return Inertia::render('accounts/show', $data);
    }

    public function update(Request $r, Account $account)
    {
        $data = $r->validate(array_merge($this->rules($account), [
            'provider' => ['sometimes', Rule::in(array_keys(Account::PROVIDERS))],
            'region' => ['sometimes', 'string', 'max:10'],
            'username' => ['sometimes', 'string', 'max:190'],
        ]));
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $account->update($data);

        return back()->with('success', 'Account updated.');
    }

    public function updateSettings(Request $r, Account $account)
    {
        $account->update($r->validate([
            'login_notice_title' => ['nullable', 'string', 'max:190'],
            'login_notice_description' => ['nullable', 'string', 'max:2000'],
            'login_notice_button' => ['nullable', 'string', 'max:60'],
        ]));

        return back()->with('success', 'Settings saved.');
    }

    public function revealPassword(Account $account)
    {
        ActivityLog::record('credential_reveal', ['account_id' => $account->id, 'provider' => $account->provider, 'description' => 'Password revealed by '.auth()->user()->name]);

        return response()->json(['password' => $account->password]);
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account deleted.');
    }

    public function restore(int $id)
    {
        Account::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Account restored.');
    }

    public function syncCustomers(Request $r, Account $account)
    {
        $ids = $r->validate(['customer_ids' => ['array'], 'customer_ids.*' => ['integer', 'exists:customers,id']])['customer_ids'] ?? [];
        self::syncWithHistory($account, 'customer', $ids);

        return back()->with('success', 'Customers updated.');
    }

    public function syncBrokers(Request $r, Account $account)
    {
        $ids = $r->validate(['broker_ids' => ['array'], 'broker_ids.*' => ['integer', 'exists:brokers,id']])['broker_ids'] ?? [];
        self::syncWithHistory($account, 'broker', $ids);

        return back()->with('success', 'Brokers updated.');
    }

    public static function syncWithHistory(Account $account, string $kind, array $ids): void
    {
        $rel = $kind === 'customer' ? $account->customers() : $account->brokers();
        $model = $kind === 'customer' ? Customer::class : Broker::class;
        $existing = $rel->get()->mapWithKeys(fn ($m) => [$m->id => $m->pivot->assigned_at])->all();
        $current = array_keys($existing);
        $ids = array_map('intval', $ids);
        $added = array_diff($ids, $current);
        $removed = array_diff($current, $ids);
        $rel->sync(collect($ids)->mapWithKeys(fn ($id) => [$id => ['assigned_at' => $existing[$id] ?? now()]])->all());
        foreach ([['assigned', $added], ['unassigned', $removed]] as [$action, $list]) {
            foreach ($list as $id) {
                AssignmentHistory::create([
                    'account_id' => $account->id, 'subject_type' => $kind, 'subject_id' => $id,
                    'subject_name' => $model::withTrashed()->find($id)?->name, 'action' => $action, 'user_id' => auth()->id(),
                ]);
            }
        }
    }

    public function uploadDocument(Request $r, Account $account)
    {
        $data = $r->validate(['group' => ['required', 'string', 'max:40'], 'name' => ['required', 'string', 'max:100'], 'file' => ['required', 'file', 'max:10240']]);
        $path = $r->file('file')->store('documents/'.$account->id, 'public');
        $account->documents()->create(['group' => $data['group'], 'name' => $data['name'], 'path' => $path]);

        return back()->with('success', 'Document uploaded.');
    }

    public function deleteDocument(Account $account, AccountDocument $document)
    {
        abort_unless($document->account_id === $account->id, 404);
        if ($document->path) {
            Storage::disk('public')->delete($document->path);
        }
        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
