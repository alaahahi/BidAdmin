<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Endpoints for the future browser extension / proxy. Events are attributed to the
 * customer that owns the Sanctum token (admins may pass customer_id), and the
 * request IP is recorded automatically.
 */
class EventController extends Controller
{
    public function __construct(private EventService $events) {}

    public function activity(Request $r)
    {
        $data = $r->validate($this->events->activityRules());
        $log = $this->events->logActivity($r->user(), $data + [
            'app' => $data['app'] ?? ($r->header('X-App') ?: 'extension'),
        ], $r->ip());

        return response()->json(['data' => $log], 201);
    }

    public function bid(Request $r)
    {
        $data = $r->validate($this->events->bidRules());
        $bid = $this->events->logBid($r->user(), $data + [
            'app' => $data['app'] ?? ($r->header('X-App') ?: 'extension'),
        ], $r->ip());

        return response()->json(['data' => $bid], 201);
    }

    public function watchlist(Request $r)
    {
        $data = $r->validate([
            'action' => ['nullable', Rule::in(['add', 'remove'])],
            'account_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'provider' => ['nullable', Rule::in(array_keys(\App\Models\Account::PROVIDERS))],
            'lot_number' => ['required', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'sale_status' => ['nullable', 'string', 'max:30'],
            'auction_status' => ['nullable', 'string', 'max:30'],
            'sale_date' => ['nullable', 'date'],
            'app' => ['nullable', 'string', 'max:40'],
        ]);
        $result = $this->events->watchlist($r->user(), $data + [
            'app' => $data['app'] ?? ($r->header('X-App') ?: 'extension'),
        ], $r->ip());

        return response()->json(['data' => $result['item'], 'removed' => $result['removed']], $result['removed'] ? 200 : 201);
    }
}
