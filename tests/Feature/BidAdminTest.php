<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Bid;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BidAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_seeded_database_renders_every_admin_page(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@bidadmin.test')->firstOrFail();
        $account = Account::first();
        $customer = Customer::first();
        $invoice = Invoice::first();

        $pages = [
            '/dashboard', '/accounts', "/accounts/{$account->id}", '/invoices', "/invoices/{$invoice->id}", '/customers', "/customers/{$customer->id}", '/brokers',
            '/finance', '/explorer/analytics', '/explorer/activity', '/explorer/active-bids', '/explorer/bids', '/explorer/watchlist',
            '/shipping-rates', '/quotations', '/numbers', '/settings',
        ];
        foreach (['overview', 'credentials', 'documents', 'bids', 'invoices', 'customers', 'brokers', 'analytics', 'settings'] as $tab) {
            $pages[] = "/accounts/{$account->id}?tab={$tab}";
        }
        foreach (['details', 'credentials', 'accounts', 'invoices', 'bids', 'watchlist', 'analytics', 'settings'] as $tab) {
            $pages[] = "/customers/{$customer->id}?tab={$tab}";
        }
        foreach (['branding', 'app', 'timezone', 'performance', 'language', 'development', 'team'] as $tab) {
            $pages[] = "/settings?tab={$tab}";
        }
        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        foreach (['/accounts/export', '/customers/export', '/invoices/export', '/brokers/export', '/finance/export', '/explorer/bids/export', '/explorer/activity/export', '/explorer/watchlist/export', '/explorer/active-bids/export'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
        $this->actingAs($admin)->get("/invoices/{$invoice->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_account_password_is_encrypted_hidden_and_revealable_by_admin_only(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/accounts', ['provider' => 'copart', 'region' => 'USA', 'username' => 'dealer42', 'password' => 'S3cret!pass'])->assertRedirect();
        $account = Account::where('username', 'dealer42')->firstOrFail();

        $raw = DB::table('accounts')->where('id', $account->id)->value('password');
        $this->assertNotEquals('S3cret!pass', $raw);
        $this->assertEquals('S3cret!pass', $account->password);

        $this->actingAs($admin)->get('/accounts')->assertOk()->assertDontSee('S3cret!pass')
            ->assertInertia(fn (Assert $p) => $p->component('accounts/index')->has('accounts.data', 1)->missing('accounts.data.0.password'));
        $this->actingAs($admin)->get("/accounts/{$account->id}")->assertDontSee('S3cret!pass');

        $this->actingAs($admin)->postJson("/accounts/{$account->id}/reveal-password")->assertOk()->assertJson(['password' => 'S3cret!pass']);
        $this->assertDatabaseHas('activity_logs', ['type' => 'credential_reveal', 'account_id' => $account->id]);

        $customerUser = User::factory()->customer()->create();
        $this->actingAs($customerUser)->postJson("/accounts/{$account->id}/reveal-password")->assertForbidden();
    }

    public function test_accounts_can_be_searched_filtered_updated_deleted_and_restored(): void
    {
        $admin = $this->admin();
        Account::factory()->create(['username' => 'alpha1', 'provider' => 'copart']);
        $iaai = Account::factory()->create(['username' => 'bravo2', 'provider' => 'iaai', 'tags' => ['vip']]);

        $this->actingAs($admin)->get('/accounts?provider=iaai')->assertInertia(fn (Assert $p) => $p->has('accounts.data', 1)->where('accounts.data.0.username', 'bravo2'));
        $this->actingAs($admin)->get('/accounts?search=alpha')->assertInertia(fn (Assert $p) => $p->has('accounts.data', 1));
        $this->actingAs($admin)->get('/accounts?tag=vip')->assertInertia(fn (Assert $p) => $p->has('accounts.data', 1));

        $this->actingAs($admin)->put("/accounts/{$iaai->id}", ['company' => 'NEW CO', 'status' => 'active'])->assertRedirect();
        $this->assertEquals('NEW CO', $iaai->fresh()->company);

        $this->actingAs($admin)->delete("/accounts/{$iaai->id}")->assertRedirect();
        $this->assertSoftDeleted($iaai);
        $this->actingAs($admin)->get('/accounts?trashed=1')->assertInertia(fn (Assert $p) => $p->has('accounts.data', 1));
        $this->actingAs($admin)->post("/accounts/{$iaai->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($iaai);
    }

    public function test_assigning_customers_records_history(): void
    {
        $admin = $this->admin();
        $account = Account::factory()->create();
        [$a, $b] = Customer::factory()->count(2)->create();

        $this->actingAs($admin)->put("/accounts/{$account->id}/customers", ['customer_ids' => [$a->id, $b->id]])->assertRedirect();
        $this->actingAs($admin)->put("/accounts/{$account->id}/customers", ['customer_ids' => [$b->id]])->assertRedirect();

        $this->assertEquals([$b->id], $account->customers()->pluck('customers.id')->all());
        $this->assertDatabaseHas('assignment_histories', ['account_id' => $account->id, 'subject_id' => $a->id, 'action' => 'assigned']);
        $this->assertDatabaseHas('assignment_histories', ['account_id' => $account->id, 'subject_id' => $a->id, 'action' => 'unassigned']);
        $this->assertEquals(3, $account->histories()->count());
    }

    public function test_invoice_won_by_is_set_from_winning_bid(): void
    {
        $admin = $this->admin();
        $account = Account::factory()->create();
        [$winner, $loser] = Customer::factory()->count(2)->create();
        Bid::factory()->create(['account_id' => $account->id, 'customer_id' => $loser->id, 'lot_number' => '55501234', 'amount' => 9000, 'status' => 'outbid']);
        Bid::factory()->create(['account_id' => $account->id, 'customer_id' => $winner->id, 'lot_number' => '55501234', 'amount' => 8000, 'status' => 'won']);

        $this->actingAs($admin)->post('/invoices', ['account_id' => $account->id, 'invoice_number' => '55501234', 'type' => 'lot', 'status' => 'pending', 'amount' => 8500, 'due_date' => now()->addDays(2)->toDateString()])->assertRedirect();
        $invoice = Invoice::where('invoice_number', '55501234')->firstOrFail();
        $this->assertEquals($winner->id, $invoice->won_by_customer_id);
        $this->assertEquals('copart', $invoice->provider);
        $this->assertEquals(8500, (float) $invoice->amount_due);

        $invoice->update(['status' => 'paid']);
        $this->assertEquals(0, (float) $invoice->fresh()->amount_due);

        $overdue = Invoice::factory()->create(['status' => 'pending', 'due_date' => now()->subDays(5)->toDateString()]);
        $this->assertEquals('overdue', $overdue->fresh()->status);

        $this->actingAs($admin)->get('/invoices?status=paid')->assertInertia(fn (Assert $p) => $p->has('invoices.data', 1)->where('stats.paid', 1));
    }

    public function test_customer_only_sees_own_data_and_is_blocked_from_admin_pages(): void
    {
        $mine = Account::factory()->create(['username' => 'mine01']);
        Account::factory()->create(['username' => 'other01']);
        $me = Customer::factory()->create();
        $other = Customer::factory()->create();
        $mine->customers()->attach($me->id, ['assigned_at' => now()]);
        $user = User::factory()->customer($me->id)->create();

        Bid::factory()->create(['customer_id' => $me->id, 'account_id' => $mine->id]);
        Bid::factory()->create(['customer_id' => $other->id]);
        Invoice::factory()->create(['won_by_customer_id' => $me->id]);
        $foreign = Invoice::factory()->create(['won_by_customer_id' => $other->id]);

        $this->actingAs($user)->get('/accounts')->assertOk()->assertInertia(fn (Assert $p) => $p->has('accounts.data', 1)->where('accounts.data.0.username', 'mine01'));
        $this->actingAs($user)->get('/explorer/bids')->assertInertia(fn (Assert $p) => $p->has('bids.data', 1));
        $this->actingAs($user)->get('/invoices')->assertInertia(fn (Assert $p) => $p->has('invoices.data', 1));
        $this->actingAs($user)->get("/invoices/{$foreign->id}")->assertNotFound();
        $this->actingAs($user)->get('/accounts/'.Account::where('username', 'other01')->value('id'))->assertNotFound();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        foreach (['/customers', '/brokers', '/finance', '/settings', '/shipping-rates', '/quotations', '/numbers'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
        $this->actingAs($user)->post('/accounts', ['provider' => 'copart', 'region' => 'USA', 'username' => 'x', 'password' => 'y'])->assertForbidden();
    }

    public function test_api_attributes_events_to_token_customer_with_ip(): void
    {
        $account = Account::factory()->create();
        $customer = Customer::factory()->create(['bid_limit' => 10000]);
        $account->customers()->attach($customer->id, ['assigned_at' => now()]);
        $user = User::factory()->customer($customer->id)->create();
        $token = $user->createToken('ext')->plainTextToken;
        $h = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];

        $this->withHeaders($h)->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/api/bids', ['account_id' => $account->id, 'lot_number' => '77712345', 'amount' => 5000, 'max_bid' => 6000, 'customer_id' => 999])
            ->assertCreated()->assertJsonPath('data.customer_id', $customer->id)->assertJsonPath('data.ip_address', '203.0.113.9');
        $this->assertDatabaseHas('activity_logs', ['type' => 'bid', 'customer_id' => $customer->id, 'target' => '77712345', 'ip_address' => '203.0.113.9']);

        $this->withHeaders($h)->postJson('/api/bids', ['account_id' => $account->id, 'lot_number' => '1', 'amount' => 20000])->assertStatus(422);

        $this->withHeaders($h)->postJson('/api/activity', ['type' => 'lot_view', 'account_id' => $account->id, 'lot_number' => '77712345', 'app' => 'chrome-ext'])
            ->assertCreated()->assertJsonPath('data.customer_id', $customer->id)->assertJsonPath('data.app', 'chrome-ext');

        $this->withHeaders($h)->postJson('/api/watchlist', ['account_id' => $account->id, 'lot_number' => '77712345', 'description' => '2024 TOYOTA'])->assertCreated();
        $this->assertDatabaseHas('watchlist_items', ['customer_id' => $customer->id, 'lot_number' => '77712345']);
        $this->withHeaders($h)->postJson('/api/watchlist', ['account_id' => $account->id, 'lot_number' => '77712345', 'action' => 'remove'])->assertOk();
        $this->assertDatabaseMissing('watchlist_items', ['customer_id' => $customer->id, 'lot_number' => '77712345']);
        $this->assertEquals(1, ActivityLog::where('type', 'watchlist_remove')->count());

        $foreign = Account::factory()->create();
        $this->withHeaders($h)->postJson('/api/activity', ['type' => 'lot_view', 'account_id' => $foreign->id])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->postJson('/api/activity', ['type' => 'login'])->assertUnauthorized();
    }

    public function test_won_bid_via_api_sets_invoice_winner(): void
    {
        $account = Account::factory()->create();
        $customer = Customer::factory()->create();
        $account->customers()->attach($customer->id, ['assigned_at' => now()]);
        $invoice = Invoice::factory()->create(['account_id' => $account->id, 'invoice_number' => '88800011']);
        $this->assertNull($invoice->won_by_customer_id);
        $token = User::factory()->customer($customer->id)->create()->createToken('ext')->plainTextToken;

        $this->withToken($token)->postJson('/api/bids', ['account_id' => $account->id, 'lot_number' => '88800011', 'amount' => 4000, 'status' => 'won'])->assertCreated();
        $this->assertEquals($customer->id, $invoice->fresh()->won_by_customer_id);
        $this->assertDatabaseHas('activity_logs', ['type' => 'won', 'target' => '88800011']);
    }

    public function test_customer_portal_login_and_token_creation(): void
    {
        $admin = $this->admin();
        $customer = Customer::factory()->create();
        $this->actingAs($admin)->put("/customers/{$customer->id}/credentials", ['email' => 'portal@example.com', 'password' => 'password123'])->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'portal@example.com', 'role' => 'customer', 'customer_id' => $customer->id]);

        $this->actingAs($admin)->post("/customers/{$customer->id}/tokens", ['name' => 'ext'])->assertSessionHas('token');
        $this->assertEquals(1, $customer->user()->first()->tokens()->count());

        auth()->logout();
        $this->post('/login', ['email' => 'portal@example.com', 'password' => 'password123'])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('activity_logs', ['type' => 'login', 'customer_id' => $customer->id]);
    }

    public function test_crud_for_customers_brokers_finance_shipping_and_settings(): void
    {
        $admin = $this->admin();
        $account = Account::factory()->create();

        $this->actingAs($admin)->post('/customers', ['name' => 'NEW BUYER', 'phone_code' => '+964', 'phone' => '7500000000'])->assertRedirect();
        $c = Customer::where('name', 'NEW BUYER')->firstOrFail();
        $this->assertNotEmpty($c->code);
        $this->actingAs($admin)->put("/customers/{$c->id}/accounts", ['account_ids' => [$account->id]])->assertRedirect();
        $this->assertTrue($account->customers()->whereKey($c->id)->exists());

        $this->actingAs($admin)->post('/brokers', ['name' => 'Broker X', 'email' => 'bx@example.com', 'covered' => true, 'account_ids' => [$account->id]])->assertRedirect();
        $this->assertDatabaseHas('account_broker', ['account_id' => $account->id]);

        $this->actingAs($admin)->post('/finance', ['amount' => 300, 'method' => 'cash'])->assertRedirect();
        $this->assertDatabaseHas('transactions', ['amount' => 300, 'type' => 'add_balance', 'status' => 'completed']);

        $this->actingAs($admin)->post('/shipping-rates', ['name' => 'Q1', 'towing' => 0, 'loading' => 100, 'surcharges' => 50, 'lines' => [['yard' => 'DALLAS', 'state' => 'TX', 'port' => 'Houston', 'destination' => 'Umm Qasr', 'price' => 1500]], 'customer_ids' => [$c->id]])->assertRedirect();
        $rate = \App\Models\ShippingRate::firstOrFail();
        $this->actingAs($admin)->post('/quotations', ['shipping_rate_id' => $rate->id, 'line' => 0, 'description' => '2024 KIA K5', 'sale_amount' => 10000])->assertRedirect();
        $q = \App\Models\Quotation::firstOrFail();
        $this->assertEquals(1650, (float) $q->shipping);
        $this->assertEquals(11650, (float) $q->total);
        $this->get("/q/{$q->token}")->assertOk()->assertSee('2024 KIA K5');

        $this->actingAs($admin)->post('/numbers', ['country' => 'US'])->assertRedirect();
        $this->assertDatabaseCount('phone_numbers', 1);

        $this->actingAs($admin)->put('/settings', ['section' => 'branding', 'company_name' => 'Najoum Bids', 'brand_color' => '#0f766e'])->assertRedirect();
        $this->actingAs($admin)->put('/settings', ['section' => 'language', 'language' => 'ar'])->assertRedirect();
        $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('settings.company_name', 'Najoum Bids')->where('settings.language', 'ar'));
        $this->actingAs($admin)->put('/settings', ['section' => 'language', 'language' => 'ckb'])->assertRedirect();
        $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('settings.language', 'ckb'));
        $this->actingAs($admin)->post('/settings/tokens', ['name' => 'server'])->assertSessionHas('token');
    }
}
