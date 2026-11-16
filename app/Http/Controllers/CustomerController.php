<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerLoginCodeService;
use App\Support\Analytics;
use App\Support\Csv;
use App\Support\Lists;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(private CustomerLoginCodeService $loginCodes) {}

    private function query(Request $r)
    {
        $q = Customer::query()->with(['accounts:id,provider,region,username,status'])
            ->withCount([
                'invoices',
                'invoices as paid_invoices_count' => fn ($i) => $i->where('status', 'paid'),
                'invoices as unpaid_invoices_count' => fn ($i) => $i->where('status', '!=', 'paid'),
            ]);
        if ($r->input('trashed') === '1') {
            $q->onlyTrashed();
        }
        Lists::search($q, $r->input('search'), ['name', 'email', 'phone', 'code']);
        if ($r->filled('account_id')) {
            $r->input('account_id') === 'none'
                ? $q->doesntHave('accounts')
                : $q->whereHas('accounts', fn ($a) => $a->where('accounts.id', $r->input('account_id')));
        }
        if ($r->input('invoices') === 'with') {
            $q->has('invoices');
        } elseif ($r->input('invoices') === 'unpaid') {
            $q->whereHas('invoices', fn ($i) => $i->where('status', '!=', 'paid'));
        } elseif ($r->input('invoices') === 'none') {
            $q->doesntHave('invoices');
        }
        if ($r->filled('tag')) {
            $q->where('tags', 'like', '%"'.$r->input('tag').'"%');
        }

        return Lists::sort($q, $r, ['name', 'code', 'email', 'phone', 'created_at']);
    }

    public function index(Request $r)
    {
        return Inertia::render('customers/index', [
            'customers' => $this->query($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['account_id', 'invoices', 'tag']),
            'stats' => ['active' => Customer::count()],
            'options' => [
                'accounts' => Account::orderBy('username')->get(['id', 'username', 'provider', 'region']),
                'tags' => Customer::whereNotNull('tags')->pluck('tags')->flatten()->unique()->values(),
            ],
        ]);
    }

    public function export(Request $r)
    {
        return Csv::download('customers', $this->query($r)->cursor(), [
            'Name' => 'name', 'Customer Code' => 'code', 'Email' => 'email', 'Phone' => fn ($c) => trim($c->phone_code.' '.$c->phone),
            'Address' => 'address', 'Accounts' => fn ($c) => $c->accounts->pluck('username')->implode('|'),
            'Bid Limit' => 'bid_limit', 'Car Limit' => 'car_limit', 'Credit Limit' => 'credit_limit',
            'Invoices' => 'invoices_count', 'Unpaid Invoices' => 'unpaid_invoices_count', 'Tags' => fn ($c) => implode('|', $c->tags ?? []),
        ]);
    }

    private function rules(?Customer $c = null): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('customers', 'code')->ignore($c?->id)],
            'email' => ['nullable', 'email', 'max:190'],
            'phone_code' => ['nullable', 'string', 'max:8'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'bid_limit' => ['nullable', 'numeric', 'min:0'],
            'car_limit' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable', 'array'], 'tags.*' => ['string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'can_bid' => ['nullable', 'boolean'],
            'login_notice_title' => ['nullable', 'string', 'max:190'],
            'login_notice_description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $data['code'] = $data['code'] ?? null ?: 'C-'.strtoupper(Str::random(6));
        $data['login_code'] = $data['login_code'] ?? $this->loginCodes->generateUnique();
        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer added.');
    }

    public function show(Request $r, int $id)
    {
        $customer = Customer::withTrashed()->with(['accounts', 'user:id,customer_id,email,name'])->findOrFail($id);
        if (! $customer->trashed()) {
            $this->loginCodes->ensure($customer);
            $customer->refresh();
        }
        $tab = $r->input('tab', 'details');
        $data = [
            'customer' => $customer,
            'tab' => $tab,
            'stats' => [
                'accounts' => $customer->accounts->count(),
                'invoices' => $customer->invoices()->count(),
                'unpaid' => (float) $customer->invoices()->where('status', '!=', 'paid')->sum('amount_due'),
                'bids' => $customer->bids()->count(),
                'won' => $customer->invoices()->count(),
                'watchlist' => $customer->watchlistItems()->count(),
            ],
            'allAccounts' => Account::orderBy('username')->get(['id', 'username', 'provider', 'region']),
            'invoices' => $customer->invoices()->with('account:id,username,provider')->latest('invoice_date')->paginate(10, ['*'], 'inv_page')->withQueryString(),
            'bids' => $customer->bids()->with('account:id,username,provider')->latest('placed_at')->paginate(10, ['*'], 'bids_page')->withQueryString(),
            'watchlist' => $customer->watchlistItems()->with('account:id,username,provider')->latest('added_at')->paginate(10, ['*'], 'wl_page')->withQueryString(),
            'tokens' => $customer->user ? $customer->user->tokens()->latest()->get(['id', 'name', 'last_used_at', 'created_at', 'expires_at']) : [],
        ];
        if ($tab === 'analytics') {
            $data['analytics'] = Analytics::build(['customer_id' => $customer->id, 'days' => 30]);
        }

        return Inertia::render('customers/show', $data);
    }

    public function update(Request $r, Customer $customer)
    {
        $rules = $this->rules($customer);
        $rules['name'] = ['sometimes', 'string', 'max:190'];
        $customer->update($r->validate($rules));

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    public function restore(int $id)
    {
        Customer::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Customer restored.');
    }

    public function syncAccounts(Request $r, Customer $customer)
    {
        $ids = array_map('intval', $r->validate(['account_ids' => ['array'], 'account_ids.*' => ['integer', 'exists:accounts,id']])['account_ids'] ?? []);
        $current = $customer->accounts()->pluck('accounts.id')->all();
        foreach (Account::whereIn('id', array_unique(array_merge($ids, $current)))->get() as $account) {
            $customerIds = $account->customers()->pluck('customers.id')->all();
            $customerIds = in_array($account->id, $ids) ? array_unique(array_merge($customerIds, [$customer->id])) : array_values(array_diff($customerIds, [$customer->id]));
            AccountController::syncWithHistory($account, 'customer', $customerIds);
        }

        return back()->with('success', 'Accounts updated.');
    }

    /** Create or update the customer's portal login. */
    public function credentials(Request $r, Customer $customer)
    {
        $user = $customer->user;
        $data = $r->validate([
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:190'],
        ]);
        $attrs = ['name' => $customer->name, 'email' => $data['email'], 'role' => 'customer', 'customer_id' => $customer->id];
        if (! empty($data['password'])) {
            $attrs['password'] = $data['password'];
        }
        $user ? $user->update($attrs) : User::create($attrs + ['email_verified_at' => now()]);

        return back()->with('success', 'Login saved.');
    }

    public function regenerateLoginCode(Customer $customer)
    {
        $this->loginCodes->regenerate($customer);

        return back()->with('success', 'App login code regenerated.');
    }

    /** Issue a Sanctum API token for the customer's browser extension. */
    public function createToken(Request $r, Customer $customer)
    {
        abort_unless($customer->user, 422, 'Create a login for this customer first.');
        $name = $r->validate(['name' => ['nullable', 'string', 'max:60']])['name'] ?? 'extension';
        // Permanent unless revoked; null expires_at (settings tokens may set optional days).
        $token = $customer->user->createToken($name ?: 'extension', ['events:write'], null);

        return back()->with('token', $token->plainTextToken)->with('success', 'Token created. Copy it now, it will not be shown again.');
    }

    public function revokeToken(Customer $customer, int $token)
    {
        $customer->user?->tokens()->where('id', $token)->delete();

        return back()->with('success', 'Token revoked.');
    }
}
