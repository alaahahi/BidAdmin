<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bid;
use App\Models\Invoice;
use App\Support\Analytics;
use App\Support\Scope;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $inv = Invoice::query();
        if (Scope::isCustomer()) {
            $inv->where('won_by_customer_id', Scope::customerId());
        }
        $analytics = Analytics::build(['days' => 30]);

        return Inertia::render('dashboard', [
            'stats' => [
                'accounts' => Scope::accounts(Account::query())->count(),
                'unpaid_invoices' => (clone $inv)->where('status', '!=', 'paid')->count(),
                'unpaid_amount' => (float) (clone $inv)->where('status', '!=', 'paid')->sum('amount_due'),
                'overdue_invoices' => (clone $inv)->where('status', 'overdue')->count(),
                'overdue_amount' => (float) (clone $inv)->where('status', 'overdue')->sum('amount_due'),
                'bids' => Scope::owned(Bid::query())->count(),
                'cars_won' => (clone $inv)->where('type', 'lot')->count(),
                'active_bids' => Scope::owned(Bid::query())->whereIn('status', ['active', 'winning'])->count(),
            ],
            'analytics' => $analytics,
            'recentInvoices' => (clone $inv)->with(['account:id,username,provider', 'wonBy:id,name'])->latest('invoice_date')->limit(6)->get(),
            'recentBids' => Scope::owned(Bid::query())->with(['account:id,username,provider', 'customer:id,name'])->latest('placed_at')->limit(6)->get(),
        ]);
    }
}
