<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\AuctionSession;
use App\Models\Bid;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerLoginCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppApiTest extends TestCase
{
    use RefreshDatabase;

    private function appHeaders(string $token, array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'X-App' => 'Android 1.0.0',
        ], $extra);
    }

    private function loginCustomer(Customer $customer, array $payload = []): array
    {
        app(CustomerLoginCodeService::class)->ensure($customer);
        $customer->refresh();

        $response = $this->postJson('/api/app/login', array_merge([
            'code' => $customer->login_code,
            'device_name' => 'Pixel 8',
            'platform' => 'Android',
            'app_version' => '1.0.0',
        ], $payload));

        return [$response, $customer->fresh()];
    }

    public function test_code_login_works_and_returns_app_token(): void
    {
        $customer = Customer::factory()->create(['login_code' => 'ABCD2345', 'name' => 'APP BUYER']);

        [$response] = $this->loginCustomer($customer, ['code' => 'ABCD2345']);

        $response->assertOk()
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonPath('customer.name', 'APP BUYER')
            ->assertJsonStructure(['token', 'token_type', 'customer' => ['id', 'name', 'code', 'can_bid', 'bid_limit', 'car_limit']]);

        $this->assertDatabaseHas('activity_logs', [
            'type' => 'login',
            'customer_id' => $customer->id,
            'source' => 'app',
            'app' => 'Android 1.0.0',
        ]);

        $token = $response->json('token');
        $this->withHeaders($this->appHeaders($token))
            ->getJson('/api/app/limits')
            ->assertOk()
            ->assertJsonPath('data.bid_limit', $customer->bid_limit);

        $pat = $customer->fresh()->user->tokens()->latest('id')->first();
        $this->assertNotNull($pat);
        $this->assertNull($pat->expires_at);
        $this->assertSame(['app'], $pat->abilities);
    }

    public function test_app_login_token_is_permanent_and_logout_revokes(): void
    {
        $customer = Customer::factory()->create(['login_code' => 'PERMCODE']);

        [$response] = $this->loginCustomer($customer, ['code' => 'PERMCODE', 'device_name' => 'Pixel 8']);
        $response->assertOk();
        $token = $response->json('token');

        $pat = $customer->fresh()->user->tokens()->where('name', 'Pixel 8')->first();
        $this->assertNotNull($pat);
        $this->assertNull($pat->expires_at);

        $this->withHeaders($this->appHeaders($token))
            ->postJson('/api/app/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $pat->id]);

        // HTTP tests keep the auth guard user across requests; clear so Bearer is re-checked.
        $this->app['auth']->forgetGuards();

        $this->withHeaders($this->appHeaders($token))
            ->getJson('/api/app/limits')
            ->assertUnauthorized();
    }

    public function test_app_relogin_same_device_replaces_token(): void
    {
        $customer = Customer::factory()->create(['login_code' => 'REPLCODE']);

        [$first] = $this->loginCustomer($customer, ['code' => 'REPLCODE', 'device_name' => 'iPhone']);
        $old = $first->json('token');

        [$second] = $this->loginCustomer($customer, ['code' => 'REPLCODE', 'device_name' => 'iPhone']);
        $new = $second->json('token');

        $this->assertNotSame($old, $new);
        $this->assertSame(1, $customer->fresh()->user->tokens()->where('name', 'iPhone')->count());

        $this->withHeaders($this->appHeaders($old))
            ->getJson('/api/app/limits')
            ->assertUnauthorized();

        $this->withHeaders($this->appHeaders($new))
            ->getJson('/api/app/limits')
            ->assertOk();
    }

    public function test_blocked_country_rejects_app_login(): void
    {
        Setting::put(['blocked_countries' => 'IR,KP']);
        $customer = Customer::factory()->create(['login_code' => 'BLOCKED1']);

        $this->withHeaders(['CF-IPCountry' => 'IR', 'Accept' => 'application/json'])
            ->postJson('/api/app/login', [
                'code' => 'BLOCKED1',
                'device_name' => 'phone',
                'platform' => 'Android',
                'app_version' => '1.0.0',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_accounts_list_only_assigned_app_providers_without_passwords(): void
    {
        $customer = Customer::factory()->create(['login_code' => 'LISTCODE']);
        $mine = Account::factory()->create(['provider' => 'copart', 'username' => 'mine-copart', 'password' => 'secret-pass', 'company' => 'Mine Co']);
        $iaai = Account::factory()->create(['provider' => 'iaai', 'username' => 'mine-iaai', 'password' => 'secret2']);
        $edge = Account::factory()->create(['provider' => 'edgepipeline', 'username' => 'edge-only', 'password' => 'secret3']);
        $other = Account::factory()->create(['provider' => 'manheim', 'username' => 'not-mine', 'password' => 'secret4']);
        $mine->customers()->attach($customer->id, ['assigned_at' => now()]);
        $iaai->customers()->attach($customer->id, ['assigned_at' => now()]);
        $edge->customers()->attach($customer->id, ['assigned_at' => now()]);

        [$login] = $this->loginCustomer($customer, ['code' => 'LISTCODE']);
        $token = $login->json('token');

        $response = $this->withHeaders($this->appHeaders($token))->getJson('/api/app/accounts');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $usernames = collect($data)->pluck('label')->implode(' ');
        $this->assertStringContainsString('mine-copart', $usernames);
        $this->assertStringContainsString('mine-iaai', $usernames);

        $json = $response->getContent();
        $this->assertStringNotContainsString('secret-pass', $json);
        $this->assertStringNotContainsString('secret2', $json);
        $this->assertStringNotContainsString('password', strtolower($json));

        foreach ($data as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('provider', $row);
            $this->assertArrayHasKey('region', $row);
            $this->assertArrayHasKey('label', $row);
            $this->assertArrayHasKey('start_url', $row);
            $this->assertArrayNotHasKey('password', $row);
            $this->assertArrayNotHasKey('username', $row);
            $this->assertContains($row['provider'], ['copart', 'iaai', 'manheim']);
        }

        $this->assertTrue(collect($data)->pluck('id')->doesntContain($other->id));
        $this->assertTrue(collect($data)->pluck('id')->doesntContain($edge->id));
    }

    public function test_customer_cannot_open_other_accounts_session(): void
    {
        $a = Customer::factory()->create(['login_code' => 'AAAA1111']);
        $b = Customer::factory()->create(['login_code' => 'BBBB2222']);
        $accountA = Account::factory()->create(['provider' => 'copart']);
        $accountB = Account::factory()->create(['provider' => 'iaai']);
        $accountA->customers()->attach($a->id, ['assigned_at' => now()]);
        $accountB->customers()->attach($b->id, ['assigned_at' => now()]);

        AuctionSession::create([
            'account_id' => $accountB->id,
            'cookies' => [['name' => 'sid', 'value' => 'abc']],
            'local_storage' => ['token' => 'xyz'],
            'user_agent' => 'TestAgent',
            'captured_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        [$login] = $this->loginCustomer($a, ['code' => 'AAAA1111']);
        $token = $login->json('token');

        $this->withHeaders($this->appHeaders($token))
            ->getJson('/api/app/accounts/'.$accountB->id.'/session')
            ->assertNotFound();

        AuctionSession::create([
            'account_id' => $accountA->id,
            'cookies' => [['name' => 'cid', 'value' => 'mine']],
            'local_storage' => ['k' => 'v'],
            'user_agent' => 'MineAgent',
            'captured_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $ok = $this->withHeaders($this->appHeaders($token))
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->getJson('/api/app/accounts/'.$accountA->id.'/session')
            ->assertOk()
            ->assertJsonPath('data.user_agent', 'MineAgent')
            ->assertJsonPath('data.cookies.0.value', 'mine');

        $this->assertArrayHasKey('local_storage', $ok->json('data'));
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'session_open',
            'customer_id' => $a->id,
            'account_id' => $accountA->id,
            'ip_address' => '198.51.100.20',
            'app' => 'Android 1.0.0',
            'source' => 'app',
        ]);
    }

    public function test_events_are_attributed_to_customer_ip_and_app(): void
    {
        $customer = Customer::factory()->create(['login_code' => 'EVENT001', 'bid_limit' => 10000, 'car_limit' => 5]);
        $account = Account::factory()->create(['provider' => 'copart']);
        $account->customers()->attach($customer->id, ['assigned_at' => now()]);

        [$login] = $this->loginCustomer($customer, ['code' => 'EVENT001']);
        $token = $login->json('token');

        $this->withHeaders($this->appHeaders($token))
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.55'])
            ->postJson('/api/app/events', [
                'platform' => 'Android',
                'app_version' => '1.0.0',
                'events' => [
                    [
                        'type' => 'lot_view',
                        'account_id' => $account->id,
                        'lot_number' => '90001',
                        'url' => 'https://www.copart.com/lot/90001',
                        'occurred_at' => now()->toIso8601String(),
                    ],
                    [
                        'type' => 'bid_placed',
                        'account_id' => $account->id,
                        'lot_number' => '90001',
                        'title' => '2020 HONDA',
                        'amount' => 2500,
                        'url' => 'https://www.copart.com/lot/90001',
                        'occurred_at' => now()->toIso8601String(),
                    ],
                    [
                        'type' => 'watchlist_add',
                        'account_id' => $account->id,
                        'lot_number' => '90002',
                        'title' => '2019 TOYOTA',
                        'occurred_at' => now()->toIso8601String(),
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('accepted', 3)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseHas('activity_logs', [
            'type' => 'lot_view',
            'customer_id' => $customer->id,
            'account_id' => $account->id,
            'ip_address' => '203.0.113.55',
            'app' => 'Android 1.0.0',
            'source' => 'app',
            'target' => '90001',
        ]);
        $this->assertDatabaseHas('bids', [
            'customer_id' => $customer->id,
            'lot_number' => '90001',
            'amount' => 2500,
            'ip_address' => '203.0.113.55',
            'app' => 'Android 1.0.0',
        ]);
        $this->assertDatabaseHas('watchlist_items', [
            'customer_id' => $customer->id,
            'lot_number' => '90002',
            'ip_address' => '203.0.113.55',
            'app' => 'Android 1.0.0',
        ]);
        $this->assertEquals(1, Bid::where('lot_number', '90001')->count());
    }

    public function test_bid_over_limit_is_rejected_in_events_batch(): void
    {
        $customer = Customer::factory()->create(['login_code' => 'LIMIT001', 'bid_limit' => 1000]);
        $account = Account::factory()->create(['provider' => 'copart']);
        $account->customers()->attach($customer->id, ['assigned_at' => now()]);

        [$login] = $this->loginCustomer($customer, ['code' => 'LIMIT001']);
        $token = $login->json('token');

        $this->withHeaders($this->appHeaders($token))
            ->postJson('/api/app/events', [
                'events' => [[
                    'type' => 'prebid',
                    'account_id' => $account->id,
                    'lot_number' => '1',
                    'amount' => 5000,
                    'occurred_at' => now()->toIso8601String(),
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('failed', 1);

        $this->assertEquals(0, Bid::count());
    }

    public function test_admin_can_store_and_test_auction_session(): void
    {
        $admin = User::where('email', 'admin@bidadmin.test')->firstOrFail();
        $account = Account::factory()->create(['provider' => 'copart']);

        $this->actingAs($admin)->put('/accounts/'.$account->id.'/session', [
            'cookies' => json_encode([['name' => 'sid', 'value' => 'tok']]),
            'local_storage' => json_encode(['auth' => '1']),
            'user_agent' => 'Mozilla/5.0',
            'expires_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $session = AuctionSession::where('account_id', $account->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('valid', $session->status());
        $this->assertEquals('tok', $session->cookies[0]['value']);

        $this->actingAs($admin)->postJson('/accounts/'.$account->id.'/session/test')
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('status', 'valid');
    }

    public function test_customer_login_code_generated_on_create(): void
    {
        $admin = User::where('email', 'admin@bidadmin.test')->firstOrFail();
        $this->actingAs($admin)->post('/customers', [
            'name' => 'CODE BUYER',
            'phone_code' => '+964',
            'phone' => '7501112233',
        ])->assertRedirect();

        $customer = Customer::where('name', 'CODE BUYER')->firstOrFail();
        $this->assertNotNull($customer->login_code);
        $this->assertEquals(8, strlen($customer->login_code));

        $this->actingAs($admin)->post('/customers/'.$customer->id.'/login-code')->assertRedirect();
        $this->assertNotEquals($customer->login_code, $customer->fresh()->login_code);
    }
}
