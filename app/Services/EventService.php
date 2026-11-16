<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Bid;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shared validation + persistence for extension/API/MCP bid & activity events.
 */
class EventService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{customer_id: ?int, account_id: ?int, ip_address: ?string, app: string, provider: ?string}
     */
    public function context(User $user, array $input, ?string $ip = null): array
    {
        $customerId = $user->isCustomer() ? $user->customer_id : (isset($input['customer_id']) ? (int) $input['customer_id'] : null);
        if ($user->isAdmin() && $customerId && ! Customer::whereKey($customerId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'Unknown customer.']);
        }

        $accountId = isset($input['account_id']) ? (int) $input['account_id'] : null;
        $account = null;
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
            'ip_address' => $ip,
            'app' => $input['app'] ?? 'extension',
            'provider' => $input['provider'] ?? $account?->provider,
        ];
    }

    /** @return array<string, mixed> */
    public function activityRules(): array
    {
        return [
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
        ];
    }

    /** @return array<string, mixed> */
    public function bidRules(): array
    {
        return [
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
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function logActivity(User $user, array $data, ?string $ip = null): ActivityLog
    {
        $ctx = $this->context($user, $data, $ip);

        return ActivityLog::create($ctx + [
            'type' => $data['type'],
            'target' => $data['target'] ?? $data['lot_number'] ?? null,
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'meta' => $data['meta'] ?? null,
            'source' => $data['source'] ?? ($ctx['app'] === 'mcp' ? 'mcp' : null),
            'user_id' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function logBid(User $user, array $data, ?string $ip = null): Bid
    {
        $ctx = $this->context($user, $data, $ip);
        $customer = $ctx['customer_id'] ? Customer::find($ctx['customer_id']) : null;
        $this->assertBidAllowed($customer, $data);
        $ctx['provider'] ??= 'copart';
        $rawType = $data['type'] ?? 'prebid';
        $bidType = match ($rawType) {
            'bid_placed', 'bid', 'live' => 'live',
            'buy_now' => 'buy_now',
            default => 'prebid',
        };
        // Legacy extension/MCP always logged activity type "bid"; app events keep the richer labels.
        $activityType = $data['activity_type'] ?? 'bid';
        $bid = Bid::create($ctx + collect($data)->except(['account_id', 'customer_id', 'provider', 'app', 'source', 'type', 'url', 'title', 'raw', 'occurred_at', 'activity_type', 'meta'])->all() + [
            'type' => $bidType,
            'phase' => $data['phase'] ?? 'preliminary',
            'status' => $data['status'] ?? 'active',
            'description' => $data['description'] ?? $data['title'] ?? null,
            'current_bid' => $data['amount'],
            'placed_at' => isset($data['occurred_at']) ? $data['occurred_at'] : now(),
        ]);
        ActivityLog::create(collect($ctx)->only(['customer_id', 'account_id', 'ip_address', 'app', 'provider'])->all() + [
            'type' => $activityType,
            'target' => $bid->lot_number,
            'description' => $bid->description,
            'amount' => $bid->amount,
            'source' => $data['source'] ?? ($ctx['app'] === 'mcp' ? 'mcp' : null),
            'user_id' => $user->id,
            'meta' => $data['meta'] ?? null,
        ]);
        if ($bid->status === 'won') {
            ActivityLog::create(collect($ctx)->only(['customer_id', 'account_id', 'ip_address', 'app', 'provider'])->all() + [
                'type' => 'won',
                'target' => $bid->lot_number,
                'description' => $bid->description,
                'amount' => $bid->amount,
                'source' => $data['source'] ?? ($ctx['app'] === 'mcp' ? 'mcp' : null),
                'user_id' => $user->id,
            ]);
            Invoice::where('invoice_number', $bid->lot_number)->whereNull('won_by_customer_id')->update(['won_by_customer_id' => $bid->customer_id]);
        }

        return $bid;
    }

    public function assertBidAllowed(?Customer $customer, array $data): void
    {
        if ($customer && ! $customer->can_bid) {
            abort(403, 'Bidding is disabled for this customer.');
        }
        if ($customer?->bid_limit && isset($data['amount']) && (float) $data['amount'] > (float) $customer->bid_limit) {
            throw ValidationException::withMessages(['amount' => 'Bid exceeds the customer bid limit of '.$customer->bid_limit.'.']);
        }
        if ($customer?->car_limit !== null && $customer->activeVehicleCount() >= (int) $customer->car_limit) {
            throw ValidationException::withMessages(['lot_number' => 'Customer has reached the max vehicles limit of '.$customer->car_limit.'.']);
        }
    }

    /**
     * Batch ingest for the Flutter customer app (max 50 items).
     *
     * @param  list<array<string, mixed>>  $events
     * @return list<array{type: string, ok: bool, id?: int, error?: string}>
     */
    public function ingestAppEvents(User $user, array $events, ?string $ip = null, ?string $app = null): array
    {
        $results = [];
        foreach ($events as $index => $event) {
            try {
                $payload = $event + [
                    'app' => $app ?? ($event['app'] ?? 'app'),
                    'source' => 'app',
                    'description' => $event['description'] ?? $event['title'] ?? null,
                    'target' => $event['target'] ?? $event['lot_number'] ?? null,
                    'meta' => $event['meta'] ?? array_filter([
                        'url' => $event['url'] ?? null,
                        'raw' => $event['raw'] ?? null,
                        'vin' => $event['vin'] ?? null,
                    ], fn ($v) => $v !== null),
                ];
                $type = $event['type'] ?? '';
                if (in_array($type, ['bid_placed', 'prebid', 'buy_now'], true)) {
                    if (empty($event['lot_number'])) {
                        throw ValidationException::withMessages(["events.$index.lot_number" => 'Required for bid events.']);
                    }
                    if (! isset($event['amount'])) {
                        throw ValidationException::withMessages(["events.$index.amount" => 'Required for bid events.']);
                    }
                    $record = $this->logBid($user, $payload + [
                        'type' => $type,
                        'activity_type' => $type,
                        'lot_number' => $event['lot_number'],
                        'amount' => $event['amount'],
                    ], $ip);
                } elseif ($type === 'watchlist_add') {
                    if (empty($event['lot_number'])) {
                        throw ValidationException::withMessages(["events.$index.lot_number" => 'Required.']);
                    }
                    $record = $this->watchlist($user, $payload + ['action' => 'add', 'lot_number' => $event['lot_number']], $ip)['item'];
                } elseif ($type === 'watchlist_remove') {
                    if (empty($event['lot_number'])) {
                        throw ValidationException::withMessages(["events.$index.lot_number" => 'Required.']);
                    }
                    $this->watchlist($user, $payload + ['action' => 'remove', 'lot_number' => $event['lot_number']], $ip);
                    $record = null;
                } elseif (in_array($type, ['login', 'lot_view', 'search'], true)) {
                    $record = $this->logActivity($user, $payload + ['type' => $type], $ip);
                } else {
                    throw ValidationException::withMessages(["events.$index.type" => 'Unsupported event type.']);
                }
                $results[] = [
                    'type' => $type,
                    'ok' => true,
                    'id' => is_object($record) && isset($record->id) ? $record->id : null,
                ];
            } catch (ValidationException $e) {
                $results[] = [
                    'type' => $event['type'] ?? 'unknown',
                    'ok' => false,
                    'error' => collect($e->errors())->flatten()->first() ?? 'Validation failed',
                ];
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $results[] = [
                    'type' => $event['type'] ?? 'unknown',
                    'ok' => false,
                    'error' => $e->getMessage() ?: 'Forbidden',
                ];
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{item: ?WatchlistItem, removed: bool}
     */
    public function watchlist(User $user, array $data, ?string $ip = null): array
    {
        $ctx = $this->context($user, $data, $ip);
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
            'type' => $remove ? 'watchlist_remove' : 'watchlist_add',
            'target' => $data['lot_number'],
            'description' => $data['description'] ?? null,
            'source' => $data['source'] ?? ($ctx['app'] === 'mcp' ? 'mcp' : null),
            'user_id' => $user->id,
        ]);

        return ['item' => $item, 'removed' => $remove];
    }
}
