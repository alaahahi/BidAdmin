<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Bid;
use App\Models\Customer;
use App\Models\Invoice;
use App\Support\Csv;
use App\Support\Lists;
use App\Support\Scope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public const TYPES = ['lot' => 'Lot', 'relist' => 'Relist Fee', 'storage' => 'Storage Fee', 'fee' => 'Other Fee'];

    private function base()
    {
        $q = Invoice::query();
        if (Scope::isCustomer()) {
            $q->where('won_by_customer_id', Scope::customerId());
            if (\App\Models\Setting::get('customers_can_see_invoices', '1') !== '1') {
                $q->whereRaw('1 = 0');
            }
        }

        return $q;
    }

    private function query(Request $r)
    {
        $q = $this->base()->with(['account:id,username,provider,region', 'wonBy:id,name,code']);
        if ($r->input('trashed') === '1' && ! Scope::isCustomer()) {
            $q->onlyTrashed();
        }
        Lists::search($q, $r->input('search'), ['invoice_number', 'vin', 'description']);
        foreach (['type', 'account_id', 'provider', 'status'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        if ($r->filled('won_by')) {
            $r->input('won_by') === 'none' ? $q->whereNull('won_by_customer_id') : $q->where('won_by_customer_id', $r->input('won_by'));
        }
        Lists::dateRange($q, $r, 'invoice_date');

        return Lists::sort($q, $r, ['provider', 'invoice_number', 'invoice_date', 'type', 'status', 'amount', 'amount_due', 'due_date'], 'invoice_date');
    }

    public function index(Request $r)
    {
        $b = $this->base();

        return Inertia::render('invoices/index', [
            'invoices' => $this->query($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['type', 'account_id', 'provider', 'status', 'won_by']),
            'stats' => [
                'total' => (clone $b)->count(),
                'paid' => (clone $b)->where('status', 'paid')->count(),
                'pending' => (clone $b)->where('status', '!=', 'paid')->count(),
                'total_value' => (float) (clone $b)->sum('amount'),
                'unpaid' => (float) (clone $b)->where('status', '!=', 'paid')->sum('amount_due'),
                'due_soon' => (float) (clone $b)->where('status', 'pending')->whereBetween('due_date', [now()->startOfDay(), now()->addDays(3)])->sum('amount_due'),
                'overdue' => (float) (clone $b)->where('status', 'overdue')->sum('amount_due'),
            ],
            'options' => $this->options(),
        ]);
    }

    private function options(): array
    {
        return [
            'types' => self::TYPES,
            'providers' => Account::PROVIDERS,
            'accounts' => Scope::accounts(Account::query())->orderBy('username')->get(['id', 'username', 'provider', 'region']),
            'customers' => Scope::isCustomer() ? [] : Customer::orderBy('name')->get(['id', 'name', 'code']),
        ];
    }

    public function export(Request $r)
    {
        return Csv::download('invoices', $this->query($r)->cursor(), [
            'Provider' => fn ($i) => Account::PROVIDERS[$i->provider] ?? $i->provider,
            'Lot/Invoice #' => 'invoice_number', 'Date' => fn ($i) => $i->invoice_date?->toDateString(),
            'Account' => 'account.username', 'Type' => 'type', 'Status' => 'status', 'Amount' => 'amount', 'Due' => 'amount_due',
            'Due Date' => fn ($i) => $i->due_date?->toDateString(), 'VIN' => 'vin', 'Description' => 'description', 'Location' => 'location',
            'Won By' => 'wonBy.name',
        ]);
    }

    private function rules(): array
    {
        return [
            'account_id' => ['nullable', 'exists:accounts,id'],
            'provider' => ['nullable', Rule::in(array_keys(Account::PROVIDERS))],
            'invoice_number' => ['required', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:190'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'status' => ['required', Rule::in(['paid', 'pending', 'overdue'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'amount_due' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'won_by_customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $data['amount_due'] ??= $data['status'] === 'paid' ? 0 : $data['amount'];
        $data['invoice_date'] ??= now()->toDateString();
        $invoice = Invoice::create($data);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.'.($invoice->won_by_customer_id && empty($data['won_by_customer_id']) ? ' Won By was set from the winning bid.' : ''));
    }

    public function show(Request $r, int $id)
    {
        $invoice = $this->base()->withTrashed()->with(['account.customers:id,name,code,email,phone', 'account.brokers:id,name,email', 'wonBy'])->findOrFail($id);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'tab' => $r->input('tab', 'overview'),
            'bids' => Scope::owned(Bid::where('lot_number', $invoice->invoice_number))->with(['customer:id,name', 'account:id,username,provider'])->latest('placed_at')->get(),
            'activities' => Scope::owned(ActivityLog::where('target', $invoice->invoice_number))->with(['customer:id,name', 'account:id,username'])->latest()->limit(100)->get(),
            'options' => $this->options(),
            'isAdmin' => ! Scope::isCustomer(),
        ]);
    }

    public function update(Request $r, Invoice $invoice)
    {
        $data = $r->validate($this->rules());
        if (! array_key_exists('won_by_customer_id', $data) || $data['won_by_customer_id'] === null) {
            $data['won_by_customer_id'] = null; // re-resolve from bids
        }
        $invoice->update($data);

        return back()->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function restore(int $id)
    {
        Invoice::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Invoice restored.');
    }

    public function pdf(int $id)
    {
        $invoice = $this->base()->withTrashed()->with(['account', 'wonBy'])->findOrFail($id);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice, 'settings' => \App\Models\Setting::all_settings()])
            ->download('invoice-'.$invoice->invoice_number.'.pdf');
    }
}
