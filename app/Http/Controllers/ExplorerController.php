<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Bid;
use App\Models\Customer;
use App\Models\WatchlistItem;
use App\Support\Analytics;
use App\Support\Csv;
use App\Support\Lists;
use App\Support\Scope;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExplorerController extends Controller
{
    private function options(): array
    {
        return [
            'providers' => Account::PROVIDERS,
            'accounts' => Scope::accounts(Account::query())->orderBy('username')->get(['id', 'username', 'provider', 'region']),
            'customers' => Scope::isCustomer() ? Customer::whereKey(Scope::customerId())->get(['id', 'name']) : Customer::orderBy('name')->get(['id', 'name']),
            'activity_types' => ActivityLog::TYPES,
        ];
    }

    /** Default to the last 90 days unless a range is selected. */
    private function window($q, Request $r, string $col)
    {
        if (! $r->filled('from') && ! $r->filled('to')) {
            return $q->where($col, '>=', now()->subDays(90));
        }

        return Lists::dateRange($q, $r, $col);
    }

    public function analytics(Request $r)
    {
        $days = in_array((int) $r->input('days'), [7, 30, 90]) ? (int) $r->input('days') : 7;
        $f = ['days' => $days, 'from' => $r->input('from'), 'to' => $r->input('to'), 'type' => $r->input('type'), 'customer_id' => $r->input('customer_id'), 'account_id' => $r->input('account_id'), 'provider' => $r->input('provider')];
        $data = Analytics::build($f);
        if ($r->boolean('export')) {
            return Csv::download('analytics', $data['daily'], ['Date' => 'date', 'Logins' => 'login', 'Lots Viewed' => 'lot_view', 'Bids' => 'bid', 'Watchlist Adds' => 'watchlist_add', 'Watchlist Removes' => 'watchlist_remove', 'Cars Won' => 'won', 'Total' => 'total']);
        }

        return Inertia::render('explorer/analytics', ['analytics' => $data, 'filters' => array_filter($f + ['days' => $days]), 'options' => $this->options()]);
    }

    private function activityQuery(Request $r)
    {
        $q = Scope::owned(ActivityLog::query())->with(['customer:id,name', 'account:id,username,provider,region']);
        Lists::search($q, $r->input('search'), ['target', 'ip_address', 'app', 'description', 'customer.name', 'account.username']);
        foreach (['type', 'customer_id', 'account_id', 'provider', 'app'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        $this->window($q, $r, 'created_at');

        return Lists::sort($q, $r, ['created_at', 'type', 'ip_address', 'app']);
    }

    public function activity(Request $r)
    {
        return Inertia::render('explorer/activity', [
            'activities' => $this->activityQuery($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['type', 'customer_id', 'account_id', 'provider', 'app']),
            'options' => $this->options() + ['apps' => ActivityLog::query()->distinct()->pluck('app')->filter()->values()],
        ]);
    }

    public function activityExport(Request $r)
    {
        return Csv::download('activity', $this->activityQuery($r)->cursor(), [
            'Date/Time' => fn ($a) => $a->created_at->toDateTimeString(), 'Activity Type' => fn ($a) => ActivityLog::TYPES[$a->type] ?? $a->type,
            'Placed By' => 'customer.name', 'Account' => 'account.username', 'Target' => 'target', 'Amount' => 'amount', 'IP Address' => 'ip_address', 'App' => 'app',
        ]);
    }

    private function bidsQuery(Request $r, bool $active = false)
    {
        $q = Scope::owned(Bid::query())->with(['customer:id,name', 'account:id,username,provider,region']);
        Lists::search($q, $r->input('search'), ['lot_number', 'vin', 'description', 'type', 'provider', 'status', 'ip_address', 'customer.name', 'account.username']);
        foreach (['type', 'phase', 'provider', 'account_id', 'customer_id', 'status'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        if ($r->input('bids') === 'winning') {
            $q->whereIn('status', ['winning', 'won']);
        } elseif ($r->input('bids') === 'outbid') {
            $q->whereIn('status', ['outbid', 'lost']);
        }
        if ($active) {
            $q->whereIn('status', ['active', 'winning', 'outbid'])->where(fn ($w) => $w->whereNull('sale_date')->orWhere('sale_date', '>=', now()->subDay()));
            Lists::dateRange($q, $r, 'sale_date');

            return Lists::sort($q, $r, ['sale_date', 'current_bid', 'max_bid', 'account_id', 'customer_id', 'description'], 'sale_date', 'asc');
        }
        $this->window($q, $r, 'placed_at');

        return Lists::sort($q, $r, ['placed_at', 'lot_number', 'provider', 'type', 'phase', 'amount'], 'placed_at');
    }

    public function bids(Request $r)
    {
        return Inertia::render('explorer/bids', [
            'bids' => $this->bidsQuery($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['type', 'phase', 'provider', 'account_id', 'customer_id', 'bids']),
            'options' => $this->options(),
        ]);
    }

    public function bidsExport(Request $r)
    {
        return Csv::download('bids', $this->bidsQuery($r)->cursor(), [
            'Provider' => 'provider', 'Lot #' => 'lot_number', 'Date/Time' => fn ($b) => $b->placed_at?->toDateTimeString(), 'Account' => 'account.username',
            'Placed By' => 'customer.name', 'Type' => 'type', 'Phase' => 'phase', 'Bid Amount' => 'amount', 'Max Bid' => 'max_bid', 'Status' => 'status', 'IP Address' => 'ip_address', 'App' => 'app',
        ]);
    }

    public function activeBids(Request $r)
    {
        return Inertia::render('explorer/active-bids', [
            'bids' => $this->bidsQuery($r, true)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['status', 'customer_id', 'account_id']),
            'options' => $this->options(),
        ]);
    }

    public function activeBidsExport(Request $r)
    {
        return Csv::download('active-bids', $this->bidsQuery($r, true)->cursor(), [
            'Provider' => 'provider', 'Account' => 'account.username', 'Lot #' => 'lot_number', 'Description' => 'description', 'Sale Date' => fn ($b) => $b->sale_date?->toDateTimeString(),
            'Current Bid' => 'current_bid', 'Bid Status' => 'status', 'Max Bid' => 'max_bid', 'Placed By' => 'customer.name',
        ]);
    }

    private function watchQuery(Request $r)
    {
        $q = Scope::owned(WatchlistItem::query())->with(['customer:id,name', 'account:id,username,provider,region']);
        Lists::search($q, $r->input('search'), ['lot_number', 'vin', 'description', 'customer.name', 'account.username']);
        foreach (['provider', 'account_id', 'customer_id', 'sale_status', 'auction_status'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        Lists::dateRange($q, $r, 'added_at');

        return Lists::sort($q, $r, ['added_at', 'provider', 'sale_status', 'auction_status', 'description'], 'added_at');
    }

    public function watchlist(Request $r)
    {
        return Inertia::render('explorer/watchlist', [
            'items' => $this->watchQuery($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['provider', 'account_id', 'customer_id', 'sale_status', 'auction_status']),
            'options' => $this->options(),
        ]);
    }

    public function watchlistExport(Request $r)
    {
        return Csv::download('watchlist', $this->watchQuery($r)->cursor(), [
            'Provider' => 'provider', 'Lot #' => 'lot_number', 'Description' => 'description', 'Sale Status' => 'sale_status', 'Auction Status' => 'auction_status',
            'Added' => fn ($w) => $w->added_at?->toDateTimeString(), 'Account' => 'account.username', 'Customer' => 'customer.name',
        ]);
    }

    public function watchlistDestroy(WatchlistItem $item)
    {
        abort_if(Scope::isCustomer() && $item->customer_id !== Scope::customerId(), 403);
        ActivityLog::record('watchlist_remove', ['customer_id' => $item->customer_id, 'account_id' => $item->account_id, 'provider' => $item->provider, 'target' => $item->lot_number, 'description' => $item->description]);
        $item->delete();

        return back()->with('success', 'Removed from watchlist.');
    }
}
