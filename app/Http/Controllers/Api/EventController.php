<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Bid;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WatchlistItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Endpoints for the future browser extension / proxy. Events are attributed to the
 * customer that owns the Sanctum token (admins may pass customer_id), and the
 * request IP is recorded automatically.
 */
class EventController extends Controller
{
    private function context(Request $r): array
    {
        $user = $r->user();
        $customerId = $user->isCustomer() ? $user->customer_id : ($r->integer('customer_id') ?: null);
        if ($user->isAdmin() && $customerId && ! Customer::whereKey($customerId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'Unknown customer.']);
        }
        $accountId = $r->integer('account_id') ?: null;
        if ($accountId) {
            $account = Account::find($accountId);
            if (! $account) {
                throw ValidationException::withMessages(['account_id' => 'Unknown account.']);
            }
            if ($user->isCustomer() && ! $account->customers()->where('customers.id', $customerId)->exists()) {
                abort(403, 'This account is not assigned to you.');
            }
        }

        return [
            'customer_id' => $customerId,
            'account_id' => $accountId,
            'ip_address' => $r->ip(),
            'app' => $r->input('app') ?: ($r->header('X-App') ?: 'extension'),
            'provider' => $r->input('provider') ?: (isset($account) ? $account->provider : null),
        ];
    }

    public function activity(Request $r)
    {
        $data = $r->validate([
            'type' => ['required', Rule::in(array_keys(ActivityLog::TYPES))],
            'account_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'provider' => ['nullable', Rule::in(array_keys(Account::PROVIDERS))],
            'target' => ['nullable', 'string', 'max:190'],
            'lot_number' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'app' => ['nullable', 'string', 'max:40'],
            'meta' => ['nullable', 'array'],
        ]);
        $log = ActivityLog::create($this->context($r) + [
            'type' => $data['type'],
            'target' => $data['target'] ?? $data['lot_number'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        return response()->json(['data' => $log], 201);
    }

    public function bid(Request $r)
    {
        $data = $r->validate([
            'account_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'provider' => ['nullable', Rule::in(array_keys(Account::PROVIDERS))],
            'lot_number' => ['required', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'location' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', Rule::in(['prebid', 'live', 'buy_now'])],
            'phase' => ['nullable', Rule::in(['preliminary', 'live', 'final'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'max_bid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'winning', 'outbid', 'won', 'lost'])],
            'sale_date' => ['nullable', 'date'],
            'app' => ['nullable', 'string', 'max:40'],
        ]);
        $ctx = $this->context($r);
        $customer = $ctx['customer_id'] ? Customer::find($ctx['customer_id']) : null;
        if ($customer && ! $customer->can_bid) {
            abort(403, 'Bidding is disabled for this customer.');
        }
        if ($customer?->bid_limit && (float) $data['amount'] > (float) $customer->bid_limit) {
            throw ValidationException::withMessages(['amount' => 'Bid exceeds the customer bid limit of '.$customer->bid_limit.'.']);
        }
        $ctx['provider'] ??= 'copart';
        $bid = Bid::create($ctx + collect($data)->except(['account_id', 'customer_id', 'provider', 'app'])->all() + [
            'type' => $data['type'] ?? 'prebid', 'phase' => $data['phase'] ?? 'preliminary', 'status' => $data['status'] ?? 'active',
            'current_bid' => $data['amount'], 'placed_at' => now(),
        ]);
        ActivityLog::create(collect($ctx)->only(['customer_id', 'account_id', 'ip_address', 'app', 'provider'])->all() + [
            'type' => 'bid', 'target' => $bid->lot_number, 'description' => $bid->description, 'amount' => $bid->amount,
        ]);
        if ($bid->status === 'won') {
            ActivityLog::create(collect($ctx)->only(['customer_id', 'account_id', 'ip_address', 'app', 'provider'])->all() + [
                'type' => 'won', 'target' => $bid->lot_number, 'description' => $bid->description, 'amount' => $bid->amount,
            ]);
            // Attach the win to an existing invoice for that lot that has no winner yet.
            Invoice::where('invoice_number', $bid->lot_number)->whereNull('won_by_customer_id')->update(['won_by_customer_id' => $bid->customer_id]);
        }

        return response()->json(['data' => $bid], 201);
    }

    public function watchlist(Request $r)
    {
        $data = $r->validate([
            'action' => ['nullable', Rule::in(['add', 'remove'])],
            'account_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'provider' => ['nullable', Rule::in(array_keys(Account::PROVIDERS))],
            'lot_number' => ['required', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'sale_status' => ['nullable', 'string', 'max:30'],
            'auction_status' => ['nullable', 'string', 'max:30'],
            'sale_date' => ['nullable', 'date'],
            'app' => ['nullable', 'string', 'max:40'],
        ]);
        $ctx = $this->context($r);
        $ctx['provider'] ??= 'copart';
        $remove = ($data['action'] ?? 'add') === 'remove';
        $item = null;
        if ($remove) {
            WatchlistItem::where('lot_number', $data['lot_number'])->where('customer_id', $ctx['customer_id'])->delete();
        } else {
            $item = WatchlistItem::updateOrCreate(
                ['lot_number' => $data['lot_number'], 'customer_id' => $ctx['customer_id'], 'account_id' => $ctx['account_id']],
                ['provider' => $ctx['provider'], 'ip_address' => $ctx['ip_address'], 'app' => $ctx['app']] + collect($data)->only(['vin', 'description', 'image_url', 'sale_status', 'auction_status', 'sale_date'])->all() + ['added_at' => now()],
            );
        }
        ActivityLog::create(collect($ctx)->only(['customer_id', 'account_id', 'ip_address', 'app', 'provider'])->all() + [
            'type' => $remove ? 'watchlist_remove' : 'watchlist_add', 'target' => $data['lot_number'], 'description' => $data['description'] ?? null,
        ]);

        return response()->json(['data' => $item, 'removed' => $remove], $remove ? 200 : 201);
    }
}
