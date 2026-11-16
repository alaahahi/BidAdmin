<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuctionSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuctionSessionService
{
    /**
     * @param  array{cookies?: mixed, local_storage?: mixed, user_agent?: ?string, captured_at?: mixed, expires_at?: mixed}  $data
     */
    public function upsert(Account $account, array $data): AuctionSession
    {
        $cookies = $this->normalizeJson($data['cookies'] ?? null, 'cookies');
        $localStorage = $this->normalizeJson($data['local_storage'] ?? null, 'local_storage');

        return DB::connection('tenant')->transaction(function () use ($account, $data, $cookies, $localStorage) {
            $session = AuctionSession::withTrashed()->firstOrNew(['account_id' => $account->id]);
            if ($session->trashed()) {
                $session->restore();
            }

            $session->fill([
                'cookies' => $cookies,
                'local_storage' => $localStorage,
                'user_agent' => $data['user_agent'] ?? $session->user_agent,
                'captured_at' => $data['captured_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? $session->expires_at,
            ]);
            $session->save();

            return $session->fresh();
        });
    }

    /** @return array{status: string, valid: bool, message: string, expires_at: ?string, captured_at: ?string} */
    public function test(Account $account): array
    {
        $session = $account->auctionSession;
        if (! $session) {
            return [
                'status' => 'missing',
                'valid' => false,
                'message' => 'No session stored for this account.',
                'expires_at' => null,
                'captured_at' => null,
            ];
        }

        $status = $session->status();
        $valid = $status === 'valid';

        return [
            'status' => $status,
            'valid' => $valid,
            'message' => match ($status) {
                'valid' => 'Session looks valid.',
                'expired' => 'Session has expired.',
                default => 'Session data is incomplete.',
            },
            'expires_at' => $session->expires_at?->toIso8601String(),
            'captured_at' => $session->captured_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    private function normalizeJson(mixed $value, string $field): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw ValidationException::withMessages([$field => 'Must be valid JSON.']);
            }

            return $decoded;
        }

        if (! is_array($value)) {
            throw ValidationException::withMessages([$field => 'Must be a JSON object or array.']);
        }

        return $value;
    }
}
