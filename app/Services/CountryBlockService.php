<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Blocks app logins from tenant-configured countries.
 * Country is resolved from CF-IPCountry / X-Country-Code headers, then optional IP lookup.
 */
class CountryBlockService
{
    /** @return list<string> ISO country codes uppercase */
    public function blockedCountries(): array
    {
        $raw = Setting::get('blocked_countries', '');
        if (is_array($raw)) {
            return array_values(array_filter(array_map(fn ($c) => strtoupper(trim((string) $c)), $raw)));
        }

        $parts = preg_split('/[\s,;]+/', (string) $raw) ?: [];

        return array_values(array_filter(array_map(fn ($c) => strtoupper(trim($c)), $parts)));
    }

    public function countryFromRequest(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'X-Country-Code', 'X-App-Country'] as $header) {
            $value = strtoupper(trim((string) $request->header($header, '')));
            if ($value !== '' && $value !== 'XX' && preg_match('/^[A-Z]{2}$/', $value)) {
                return $value;
            }
        }

        $ip = $request->ip();
        if (! $ip || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return null;
        }

        return Cache::remember('geoip:'.$ip, 3600, function () use ($ip) {
            try {
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,countryCode']);
                if ($response->ok() && ($response->json('status') === 'success')) {
                    $code = strtoupper((string) $response->json('countryCode'));

                    return preg_match('/^[A-Z]{2}$/', $code) ? $code : null;
                }
            } catch (\Throwable) {
                //
            }

            return null;
        });
    }

    public function isBlocked(Request $request): bool
    {
        $blocked = $this->blockedCountries();
        if ($blocked === []) {
            return false;
        }

        $country = $this->countryFromRequest($request);
        if (! $country) {
            return false;
        }

        return in_array($country, $blocked, true);
    }
}
