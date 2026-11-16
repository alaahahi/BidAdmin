<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppAuthService
{
    public function __construct(
        private CountryBlockService $countries,
    ) {}

    /**
     * @return array{token: string, customer: Customer, user: User}
     */
    public function login(Request $request, string $code, string $deviceName, string $platform, string $appVersion): array
    {
        if ($this->countries->isBlocked($request)) {
            throw ValidationException::withMessages([
                'code' => 'Access from your country is not allowed.',
            ]);
        }

        $code = strtoupper(trim($code));
        $customer = Customer::where('login_code', $code)->first();
        if (! $customer) {
            throw ValidationException::withMessages(['code' => 'Invalid login code.']);
        }

        return DB::connection('tenant')->transaction(function () use ($request, $customer, $deviceName, $platform, $appVersion) {
            $user = $this->ensureUser($customer);
            $app = trim($platform.' '.$appVersion);
            $name = $deviceName !== '' ? $deviceName : 'app';

            // One permanent token per device name; null expires_at = no expiry (revoke via logout/UI).
            $user->tokens()->where('name', $name)->delete();
            $token = $user->createToken($name, ['app'], null);

            ActivityLog::create([
                'type' => 'login',
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'app' => Str::limit($app, 40, ''),
                'source' => 'app',
                'description' => 'App login',
                'meta' => [
                    'device_name' => $deviceName,
                    'platform' => $platform,
                    'app_version' => $appVersion,
                ],
            ]);

            return [
                'token' => $token->plainTextToken,
                'customer' => $customer->fresh(),
                'user' => $user,
            ];
        });
    }

    /** Revoke the current Sanctum Bearer token (app logout). */
    public function logout(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }
    }

    public function ensureUser(Customer $customer): User
    {
        if ($customer->user) {
            return $customer->user;
        }

        $email = $customer->email;
        if (! $email || User::where('email', $email)->exists()) {
            $email = 'app+'.$customer->id.'@customers.local';
        }

        return User::create([
            'name' => $customer->name,
            'email' => $email,
            'password' => Str::password(32),
            'role' => 'customer',
            'customer_id' => $customer->id,
            'email_verified_at' => now(),
        ]);
    }
}
