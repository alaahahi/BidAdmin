<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppAccountResource;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppAccountController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 403);

        $accounts = $customer->accounts()
            ->whereIn('provider', Account::APP_PROVIDERS)
            ->orderBy('provider')
            ->orderBy('username')
            ->get(['accounts.id', 'provider', 'region', 'username', 'company']);

        return AppAccountResource::collection($accounts);
    }

    public function session(Request $request, int $id)
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 403);

        $account = $customer->accounts()
            ->whereIn('provider', Account::APP_PROVIDERS)
            ->where('accounts.id', $id)
            ->first();

        abort_unless($account, 404);

        $session = $account->auctionSession;
        abort_unless($session && $session->isValid(), 404, 'No valid session for this account.');

        $app = $this->appLabel($request);

        ActivityLog::create([
            'type' => 'session_open',
            'customer_id' => $customer->id,
            'user_id' => $request->user()->id,
            'account_id' => $account->id,
            'provider' => $account->provider,
            'ip_address' => $request->ip(),
            'app' => $app,
            'source' => 'app',
            'description' => 'Session opened in app',
        ]);

        // Explicitly expose encrypted fields for the assigned customer only.
        return response()->json([
            'data' => [
                'account_id' => $account->id,
                'cookies' => $session->cookies ?? [],
                'local_storage' => $session->local_storage ?? [],
                'user_agent' => $session->user_agent,
                'captured_at' => $session->captured_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
            ],
        ]);
    }

    private function appLabel(Request $request): string
    {
        $fromHeader = $request->header('X-App');
        if ($fromHeader) {
            return Str::limit($fromHeader, 40, '');
        }

        return Str::limit(trim(($request->input('platform') ?? '').' '.($request->input('app_version') ?? '')), 40, '') ?: 'app';
    }
}
