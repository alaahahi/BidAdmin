<?php

namespace App\Support;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class Analytics
{
    /**
     * @param  array{days?:int, from?:string, to?:string, type?:string, customer_id?:int, account_id?:int, provider?:string}  $f
     */
    public static function build(array $f): array
    {
        $to = isset($f['to']) && $f['to'] ? Carbon::parse($f['to'])->endOfDay() : now()->endOfDay();
        $from = isset($f['from']) && $f['from'] ? Carbon::parse($f['from'])->startOfDay() : now()->subDays(($f['days'] ?? 7) - 1)->startOfDay();

        $q = Scope::owned(ActivityLog::query())->whereBetween('created_at', [$from, $to]);
        foreach (['type', 'customer_id', 'account_id', 'provider'] as $k) {
            if (! empty($f[$k])) {
                $q->where($k, $f[$k]);
            }
        }
        $rows = $q->get(['id', 'type', 'customer_id', 'account_id', 'provider', 'target', 'description', 'amount', 'app', 'created_at']);
        $count = fn ($t) => $rows->where('type', $t)->count();

        $days = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $d) {
            $days[$d->toDateString()] = ['date' => $d->toDateString(), 'login' => 0, 'lot_view' => 0, 'bid' => 0, 'watchlist_add' => 0, 'watchlist_remove' => 0, 'won' => 0, 'total' => 0];
        }
        foreach ($rows as $r) {
            $k = $r->created_at->toDateString();
            if (isset($days[$k])) {
                if (isset($days[$k][$r->type])) {
                    $days[$k][$r->type]++;
                }
                $days[$k]['total']++;
            }
        }

        $customers = Customer::withTrashed()->whereIn('id', $rows->pluck('customer_id')->filter()->unique())->pluck('name', 'id');
        $accounts = Account::withTrashed()->whereIn('id', $rows->pluck('account_id')->filter()->unique())->get(['id', 'username', 'provider'])->keyBy('id');

        $top = fn ($key, $labeler) => $rows->whereNotNull($key)->groupBy($key)->map(fn ($g, $id) => [
            'id' => $id, 'label' => $labeler($id), 'actions' => $g->count(), 'bids' => $g->where('type', 'bid')->count(), 'won' => $g->where('type', 'won')->count(),
        ])->sortByDesc('actions')->values()->take(5);

        $invQ = Invoice::query()->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()]);
        if (Scope::isCustomer()) {
            $invQ->where('won_by_customer_id', Scope::customerId());
        }
        if (! empty($f['account_id'])) {
            $invQ->where('account_id', $f['account_id']);
        }
        if (! empty($f['customer_id'])) {
            $invQ->where('won_by_customer_id', $f['customer_id']);
        }
        if (! empty($f['provider'])) {
            $invQ->where('provider', $f['provider']);
        }
        $won = $invQ->with('wonBy:id,name')->get(['id', 'provider', 'won_by_customer_id', 'amount']);

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'metrics' => [
                'total_actions' => $rows->count(),
                'logins' => $count('login'),
                'lots_viewed' => $count('lot_view'),
                'bids_placed' => $count('bid'),
                'cars_won' => $count('won'),
                'watchlist_adds' => $count('watchlist_add'),
                'watchlist_removes' => $count('watchlist_remove'),
                'active_customers' => $rows->pluck('customer_id')->filter()->unique()->count(),
            ],
            'daily' => array_values($days),
            'purchased' => [
                'all' => $won->groupBy(fn ($i) => Account::PROVIDERS[$i->provider] ?? ($i->provider ?: 'Other'))->map(fn ($g, $k) => ['name' => $k, 'count' => $g->count(), 'value' => (float) $g->sum('amount')])->values(),
                'inside' => $won->whereNotNull('won_by_customer_id')->groupBy(fn ($i) => $i->wonBy?->name ?? 'Unknown')->map(fn ($g, $k) => ['name' => $k, 'count' => $g->count(), 'value' => (float) $g->sum('amount')])->values(),
            ],
            'top_customers' => $top('customer_id', fn ($id) => $customers[$id] ?? 'Unknown'),
            'top_accounts' => $top('account_id', fn ($id) => $accounts[$id]->username ?? 'Unknown'),
            'top_vehicles' => $rows->whereNotNull('target')->whereIn('type', ['lot_view', 'bid', 'watchlist_add'])->groupBy('target')->map(fn ($g, $lot) => [
                'lot' => $lot, 'description' => $g->firstWhere('description')?->description, 'views' => $g->where('type', 'lot_view')->count(), 'bids' => $g->where('type', 'bid')->count(), 'actions' => $g->count(),
            ])->sortByDesc('actions')->values()->take(5),
            'by_type' => collect(ActivityLog::TYPES)->map(fn ($label, $t) => ['type' => $t, 'label' => $label, 'count' => $count($t)])->values(),
            'by_app' => $rows->groupBy(fn ($r) => $r->app ?: 'unknown')->map(fn ($g, $k) => ['name' => $k, 'count' => $g->count()])->values(),
            'by_provider' => $rows->groupBy(fn ($r) => Account::PROVIDERS[$r->provider] ?? ($r->provider ?: 'Other'))->map(fn ($g, $k) => ['name' => $k, 'count' => $g->count()])->values(),
        ];
    }
}
