<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Support\Csv;
use App\Support\Lists;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public const TYPES = ['add_balance' => 'Add Balance', 'vehicle_charge' => 'Vehicle Charge', 'refund' => 'Refund'];

    private function query(Request $r)
    {
        $q = Transaction::query()->with('wonBy:id,name');
        Lists::search($q, $r->input('search'), ['amount', 'status', 'session_id', 'description']);
        foreach (['status', 'type'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        if ($r->filled('won_by')) {
            $q->where('won_by_customer_id', $r->input('won_by'));
        }
        Lists::dateRange($q, $r, 'date');

        return Lists::sort($q, $r, ['date', 'amount', 'type', 'status'], 'date');
    }

    public function index(Request $r)
    {
        return Inertia::render('finance/index', [
            'transactions' => $this->query($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['status', 'type', 'won_by']),
            'options' => ['types' => self::TYPES, 'customers' => Customer::orderBy('name')->get(['id', 'name'])],
            'balance' => Transaction::balance(),
        ]);
    }

    public function export(Request $r)
    {
        return Csv::download('transactions', $this->query($r)->cursor(), [
            'Date' => fn ($t) => $t->date->toDateString(), 'Description' => 'description', 'Won By' => 'wonBy.name', 'Amount' => 'amount',
            'Type' => fn ($t) => self::TYPES[$t->type] ?? $t->type, 'Status' => 'status', 'Session ID' => 'session_id', 'Method' => 'method',
        ]);
    }

    /** Records a manual balance top-up. No real payment is processed. */
    public function store(Request $r)
    {
        $data = $r->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'card', 'other'])],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
        ]);
        Transaction::create($data + ['type' => 'add_balance', 'status' => 'completed', 'date' => $data['date'] ?? now()->toDateString(), 'session_id' => 'man_'.Str::lower(Str::random(16))]);

        return back()->with('success', 'Balance added.');
    }

    public function update(Request $r, Transaction $transaction)
    {
        $transaction->update($r->validate(['status' => ['required', Rule::in(['completed', 'pending', 'cancelled'])]]));

        return back()->with('success', 'Transaction updated.');
    }
}
