<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable()->unique();
            $t->string('email')->nullable();
            $t->string('phone_code', 8)->nullable();
            $t->string('phone')->nullable();
            $t->string('address')->nullable();
            $t->decimal('bid_limit', 12, 2)->nullable();      // max amount per bid
            $t->unsignedInteger('car_limit')->nullable();     // max simultaneous cars
            $t->decimal('credit_limit', 12, 2)->nullable();   // max open balance
            $t->json('tags')->nullable();
            $t->text('notes')->nullable();
            $t->string('login_notice_title')->nullable();
            $t->text('login_notice_description')->nullable();
            $t->boolean('can_bid')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('customer')->after('email');
            $t->foreignId('customer_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->string('provider', 30);
            $t->string('region', 10)->default('USA');
            $t->string('type')->nullable();
            $t->string('category')->nullable();
            $t->string('username');
            $t->text('password')->nullable();
            $t->string('company')->nullable();
            $t->string('account_number')->nullable();
            $t->string('external_id')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('address')->nullable();
            $t->decimal('balance_due', 12, 2)->default(0);
            $t->string('status', 20)->default('active');
            $t->string('action_status', 30)->default('ok');
            $t->json('tags')->nullable();
            $t->text('notes')->nullable();
            $t->string('membership_plan')->nullable();
            $t->date('next_billing_date')->nullable();
            $t->string('login_notice_title')->nullable();
            $t->text('login_notice_description')->nullable();
            $t->string('login_notice_button')->nullable();
            $t->timestamp('last_synced_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('account_customer', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->timestamp('assigned_at')->nullable();
            $t->timestamps();
            $t->unique(['account_id', 'customer_id']);
        });

        Schema::create('brokers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->boolean('covered')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('account_broker', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->foreignId('broker_id')->constrained()->cascadeOnDelete();
            $t->timestamp('assigned_at')->nullable();
            $t->timestamps();
            $t->unique(['account_id', 'broker_id']);
        });

        Schema::create('assignment_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('subject_type', 20); // customer | broker
            $t->unsignedBigInteger('subject_id');
            $t->string('subject_name')->nullable();
            $t->string('action', 20); // assigned | unassigned
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('account_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('group', 40);
            $t->string('name');
            $t->string('path')->nullable();
            $t->timestamps();
        });

        Schema::create('bids', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('provider', 30);
            $t->string('lot_number', 40)->index();
            $t->string('vin', 40)->nullable();
            $t->string('description')->nullable();
            $t->string('image_url')->nullable();
            $t->string('location')->nullable();
            $t->string('type', 20)->default('prebid'); // prebid | live | buy_now
            $t->string('phase', 20)->default('preliminary'); // preliminary | live | final
            $t->decimal('amount', 12, 2);
            $t->decimal('max_bid', 12, 2)->nullable();
            $t->decimal('current_bid', 12, 2)->nullable();
            $t->string('status', 20)->default('active'); // active | winning | outbid | won | lost
            $t->timestamp('sale_date')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('app', 40)->nullable();
            $t->timestamp('placed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('watchlist_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('provider', 30);
            $t->string('lot_number', 40);
            $t->string('vin', 40)->nullable();
            $t->string('description')->nullable();
            $t->string('image_url')->nullable();
            $t->string('sale_status', 30)->nullable();
            $t->string('auction_status', 30)->nullable();
            $t->timestamp('sale_date')->nullable();
            $t->timestamp('added_at')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('app', 40)->nullable();
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->string('type', 30)->index(); // login | lot_view | bid | watchlist_add | watchlist_remove | won
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $t->string('provider', 30)->nullable();
            $t->string('target')->nullable(); // lot number / page
            $t->string('description')->nullable();
            $t->decimal('amount', 12, 2)->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('app', 40)->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $t->string('provider', 30)->nullable();
            $t->string('invoice_number', 40)->index(); // lot or invoice #
            $t->string('vin', 40)->nullable();
            $t->string('description')->nullable();
            $t->string('location')->nullable();
            $t->string('image_url')->nullable();
            $t->string('type', 20)->default('lot'); // lot | relist | fee | storage
            $t->string('status', 20)->default('pending'); // paid | pending | overdue
            $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('amount_due', 12, 2)->default(0);
            $t->decimal('sale_price', 12, 2)->nullable();
            $t->decimal('fees', 12, 2)->nullable();
            $t->date('invoice_date')->nullable();
            $t->date('due_date')->nullable();
            $t->date('paid_at')->nullable();
            $t->foreignId('won_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->date('date');
            $t->string('description')->nullable();
            $t->string('image_url')->nullable();
            $t->foreignId('won_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('type', 30)->default('add_balance'); // add_balance | vehicle_charge | refund
            $t->string('status', 20)->default('completed'); // completed | pending | cancelled
            $t->string('session_id')->nullable();
            $t->string('method', 30)->nullable();
            $t->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->decimal('towing', 12, 2)->default(0);
            $t->decimal('loading', 12, 2)->default(0);
            $t->decimal('surcharges', 12, 2)->default(0);
            $t->json('lines')->nullable(); // [{yard, state, port, destination, price}]
            $t->timestamps();
        });

        Schema::create('customer_shipping_rate', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipping_rate_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipping_rate_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('token', 40)->unique();
            $t->string('lot_number')->nullable();
            $t->string('vin')->nullable();
            $t->string('description')->nullable();
            $t->string('image_url')->nullable();
            $t->string('pickup_yard')->nullable();
            $t->string('loading_port')->nullable();
            $t->string('delivery_port')->nullable();
            $t->decimal('sale_amount', 12, 2)->default(0);
            $t->decimal('shipping', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('phone_numbers', function (Blueprint $t) {
            $t->id();
            $t->string('number');
            $t->string('country', 5)->default('US');
            $t->string('status', 20)->default('active');
            $t->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $t->date('next_bill_at')->nullable();
            $t->decimal('monthly_price', 8, 2)->default(2);
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'phone_numbers', 'quotations', 'customer_shipping_rate', 'shipping_rates', 'transactions', 'invoices', 'activity_logs', 'watchlist_items', 'bids', 'account_documents', 'assignment_histories', 'account_broker', 'brokers', 'account_customer', 'accounts'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id');
            $t->dropColumn('role');
        });
        Schema::dropIfExists('customers');
    }
};
