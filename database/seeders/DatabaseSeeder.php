<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountDocument;
use App\Models\ActivityLog;
use App\Models\AssignmentHistory;
use App\Models\Bid;
use App\Models\Broker;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PhoneNumber;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WatchlistItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    private array $vehicles = [
        ['2025 TOYOTA CAMRY XSE/XLE/LE/SE', '4T1DAACK6SU036090'], ['2025 TOYOTA GRAND HIGHLANDER HYBRID LIMITED', '5TDACAB56SS050962'],
        ['2026 NISSAN SENTRA SV', '3N1AB8CV6TY233440'], ['2026 NISSAN SENTRA S', '3N1AB8BV0TY223618'], ['2025 TOYOTA CAMRY LE', '4T1DBADK1SU016230'],
        ['2026 TOYOTA CAMRY SE', '4T1DAACK7TU264164'], ['2024 HONDA ACCORD EX', '1HGCY1F36RA012345'], ['2023 HYUNDAI SONATA SEL', 'KMHL64JA1PA123456'],
        ['2024 KIA K5 GT-LINE', '5XXG64J21RG234567'], ['2022 LEXUS ES 350', '58ABZ1B10NU123987'], ['2025 TOYOTA RAV4 XLE', '2T3P1RFV5SW456789'],
        ['2024 CHEVROLET MALIBU LT', '1G1ZD5ST2RF345678'], ['2023 FORD EXPLORER XLT', '1FMSK8DH5PGA45678'], ['2025 NISSAN ALTIMA SV', '1N4BL4DV5SN367890'],
        ['2024 TOYOTA COROLLA LE', '5YFB4MDE1RP098765'], ['2023 JEEP GRAND CHEROKEE L', '1C4RJKBG8P8765432'], ['2024 MAZDA CX-5 TOURING', 'JM3KFBCM1R0456123'],
        ['2025 HYUNDAI ELANTRA SE', 'KMHLL4DG3SU876543'], ['2022 BMW X5 XDRIVE40I', '5UXCR6C05N9K12345'], ['2024 TOYOTA TACOMA SR5', '3TMLB5JN4RM012678'],
    ];

    private array $yards = ['MS - JACKSON', 'FL - ORLANDO SOUTH', 'ON - LONDON', 'NY - ROCHESTER', 'RI - PROVIDENCE', 'TX - DALLAS/FT WORTH', 'CA - LOS ANGELES', 'GA - ATLANTA EAST', 'NJ - SOMERVILLE', 'IL - CHICAGO NORTH'];

    private array $ips = ['185.71.12.44', '37.236.88.19', '5.62.141.7', '149.255.34.210', '91.106.40.12', '178.18.244.90'];

    public function run(): void
    {
        Setting::put(['company_name' => 'BidAdmin', 'brand_color' => '#111827', 'timezone' => 'Asia/Baghdad', 'language' => 'en']);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@bidadmin.test', 'password' => 'password', 'role' => 'admin', 'email_verified_at' => now()]);
        User::create(['name' => 'Salam Jalal', 'email' => 'salam@bidadmin.test', 'password' => 'password', 'role' => 'admin', 'email_verified_at' => now()]);

        // Auction accounts
        $acc = [];
        $acc[] = Account::create(['provider' => 'iaai', 'region' => 'USA', 'type' => 'BROKER BIDDER', 'category' => 'C', 'username' => 'najoum_621@naj.ae', 'password' => 'Naj#2026!iaai', 'company' => 'NEJOUM AL JAZEERA USED CARS LLC', 'account_number' => 'najoum_621@naj.ae', 'external_id' => '705375', 'phone' => '2819027600', 'address' => 'INDUSTRIAL AREA 4 BEHIND SH. SAQR ST, SHARJAH, XX', 'status' => 'active', 'action_status' => 'ok', 'tags' => ['broker', 'priority'], 'membership_plan' => 'BROKER BIDDER', 'next_billing_date' => '2027-09-01', 'last_synced_at' => now()->subMinutes(14)]);
        $acc[] = Account::create(['provider' => 'copart', 'region' => 'CAN', 'type' => 'ENTERPRISE_BIDDER', 'category' => 'A', 'username' => '553598', 'password' => 'CpCan!553598', 'company' => 'NEJOUM AL JAZEERA USED CARS', 'account_number' => '553598', 'external_id' => 'CA-553598', 'email' => 'canada@najoum.example', 'phone' => '4165550142', 'address' => '1200 BAY ST, TORONTO, ON', 'status' => 'active', 'action_status' => 'ok', 'tags' => ['canada'], 'membership_plan' => 'ENTERPRISE', 'next_billing_date' => '2027-03-15', 'last_synced_at' => now()->subHour()]);
        $acc[] = Account::create(['provider' => 'copart', 'region' => 'USA', 'type' => 'ENTERPRISE_BIDDER', 'category' => 'A', 'username' => '553598', 'password' => 'CpUsa!553598', 'company' => 'NEJOUM AL JAZEERA USED CARS', 'account_number' => '553598', 'external_id' => 'US-553598', 'email' => 'usa@najoum.example', 'phone' => '9725550188', 'address' => '14185 DALLAS PKWY, DALLAS, TX', 'balance_due' => 1250, 'status' => 'active', 'action_status' => 'payment_due', 'tags' => ['usa', 'priority'], 'membership_plan' => 'ENTERPRISE', 'next_billing_date' => '2027-03-15', 'last_synced_at' => now()->subMinutes(5)]);
        $acc[] = Account::create(['provider' => 'manheim', 'region' => 'USA', 'type' => 'DEALER', 'category' => 'B', 'username' => 'erbil.motors', 'password' => 'Mh!Erbil2026', 'company' => 'ERBIL MOTORS TRADING', 'account_number' => '5012784', 'external_id' => 'MH-5012784', 'email' => 'buy@erbilmotors.example', 'phone' => '7135550111', 'address' => 'HOUSTON, TX', 'status' => 'active', 'action_status' => 'ok', 'tags' => ['dealer'], 'membership_plan' => 'DEALER', 'next_billing_date' => '2027-01-10', 'last_synced_at' => now()->subHours(3)]);
        $acc[] = Account::create(['provider' => 'iaai', 'region' => 'CAN', 'type' => 'PUBLIC BUYER', 'category' => 'B', 'username' => 'kurdcars.ca', 'password' => 'IaCa#7788', 'company' => 'KURD CARS EXPORT INC', 'account_number' => 'BUY-778812', 'external_id' => '778812', 'phone' => '6045550199', 'address' => 'VANCOUVER, BC', 'status' => 'active', 'action_status' => 'password_expired', 'tags' => ['canada'], 'membership_plan' => 'PREMIUM', 'next_billing_date' => '2026-12-01', 'last_synced_at' => now()->subDays(2)]);

        // Brokers
        $brokers = collect([
            ['name' => 'NEJOUM AL JAZEERA', 'email' => 'brokers@naj.ae', 'covered' => true],
            ['name' => 'Galaxy Auto Brokers', 'email' => 'ops@galaxybrokers.example', 'covered' => false],
        ])->map(fn ($b) => Broker::create($b));

        // Customers
        $customers = collect([
            ['name' => 'ASO RANYAI', 'phone' => '7704456681', 'email' => null, 'bid_limit' => 15000, 'car_limit' => 3, 'credit_limit' => 30000, 'tags' => ['vip'], 'address' => 'Sulaymaniyah, Iraq'],
            ['name' => 'NASHWAN', 'phone' => '7504381717', 'email' => 'nashwan@example.com', 'bid_limit' => 20000, 'car_limit' => 5, 'credit_limit' => 50000, 'tags' => ['dealer'], 'address' => 'Erbil, Iraq'],
            ['name' => 'DR BAXTYAR', 'phone' => '7504470959', 'email' => null, 'bid_limit' => null, 'car_limit' => null, 'credit_limit' => null, 'tags' => [], 'address' => 'Duhok, Iraq'],
            ['name' => 'SARWAN SALAM', 'phone' => '7501609964', 'email' => 'sarwan@example.com', 'bid_limit' => 12000, 'car_limit' => 2, 'credit_limit' => 25000, 'tags' => ['new'], 'address' => 'Erbil, Iraq'],
            ['name' => 'HEMIN KARIM', 'phone' => '7701234567', 'email' => 'hemin@example.com', 'bid_limit' => 30000, 'car_limit' => 6, 'credit_limit' => 80000, 'tags' => ['vip', 'dealer'], 'address' => 'Kirkuk, Iraq'],
            ['name' => 'AHMED AL-OBAIDI', 'phone' => '7809876543', 'email' => 'ahmed@example.com', 'bid_limit' => 8000, 'car_limit' => 2, 'credit_limit' => 15000, 'tags' => [], 'address' => 'Baghdad, Iraq'],
        ])->values()->map(fn ($c, $i) => Customer::create($c + ['code' => sprintf('BA-%04d', 1001 + $i), 'phone_code' => '+964']));

        // Demo customer portal login
        User::create(['name' => 'NASHWAN', 'email' => 'customer@bidadmin.test', 'password' => 'password', 'role' => 'customer', 'customer_id' => $customers[1]->id, 'email_verified_at' => now()]);

        // Assignments (with history)
        $assign = function (Account $a, array $customerIdx, array $brokerIdx = []) use ($customers, $brokers, $admin) {
            foreach ($customerIdx as $i) {
                $when = now()->subDays(rand(20, 120));
                $a->customers()->attach($customers[$i]->id, ['assigned_at' => $when]);
                AssignmentHistory::create(['account_id' => $a->id, 'subject_type' => 'customer', 'subject_id' => $customers[$i]->id, 'subject_name' => $customers[$i]->name, 'action' => 'assigned', 'user_id' => $admin->id, 'created_at' => $when, 'updated_at' => $when]);
            }
            foreach ($brokerIdx as $i) {
                $a->brokers()->attach($brokers[$i]->id, ['assigned_at' => now()->subDays(60)]);
                AssignmentHistory::create(['account_id' => $a->id, 'subject_type' => 'broker', 'subject_id' => $brokers[$i]->id, 'subject_name' => $brokers[$i]->name, 'action' => 'assigned', 'user_id' => $admin->id, 'created_at' => now()->subDays(60), 'updated_at' => now()->subDays(60)]);
            }
        };
        $assign($acc[0], [0, 1, 2, 3], [0]);
        $assign($acc[1], [2, 3]);
        $assign($acc[2], [0, 1, 2, 3, 4], [1]);
        $assign($acc[3], [4, 5]);
        $assign($acc[4], [5, 1]);
        // one historical un-assignment
        AssignmentHistory::create(['account_id' => $acc[1]->id, 'subject_type' => 'customer', 'subject_id' => $customers[5]->id, 'subject_name' => $customers[5]->name, 'action' => 'assigned', 'user_id' => $admin->id, 'created_at' => now()->subDays(90), 'updated_at' => now()->subDays(90)]);
        AssignmentHistory::create(['account_id' => $acc[1]->id, 'subject_type' => 'customer', 'subject_id' => $customers[5]->id, 'subject_name' => $customers[5]->name, 'action' => 'unassigned', 'user_id' => $admin->id, 'created_at' => now()->subDays(30), 'updated_at' => now()->subDays(30)]);

        // Documents
        foreach ($acc as $a) {
            $a->documents()->create(['group' => 'Business', 'name' => 'Trade License']);
            $a->documents()->create(['group' => 'Identity', 'name' => 'Passport']);
        }
        $acc[0]->documents()->create(['group' => 'Agreements', 'name' => 'Broker Agreement']);

        $accountCustomers = collect($acc)->mapWithKeys(fn ($a) => [$a->id => $a->customers()->pluck('customers.id')->all()]);
        $pick = fn ($arr) => $arr[array_rand($arr)];

        // Activity, bids, watchlist over the last 90 days
        for ($d = 89; $d >= 0; $d--) {
            $day = now()->subDays($d);
            $events = rand(3, 12);
            for ($e = 0; $e < $events; $e++) {
                $a = $pick($acc);
                $cid = $pick($accountCustomers[$a->id]);
                [$desc, $vin] = $pick($this->vehicles);
                $lot = (string) rand(40000000, 79999999);
                $at = (clone $day)->setTime(rand(7, 23), rand(0, 59), rand(0, 59));
                $ip = $pick($this->ips);
                $app = $pick(['extension', 'extension', 'web', 'mobile']);
                $base = ['customer_id' => $cid, 'account_id' => $a->id, 'provider' => $a->provider, 'ip_address' => $ip, 'app' => $app];
                if (rand(0, 3) === 0) {
                    ActivityLog::create($base + ['type' => 'login', 'target' => Account::PROVIDERS[$a->provider].' '.$a->region, 'created_at' => $at, 'updated_at' => $at]);
                }
                ActivityLog::create($base + ['type' => 'lot_view', 'target' => $lot, 'description' => $desc, 'created_at' => $at, 'updated_at' => $at]);
                $r = rand(0, 9);
                if ($r < 3) {
                    $amount = rand(20, 280) * 50;
                    $status = $d < 2 ? $pick(['active', 'winning', 'outbid']) : $pick(['outbid', 'lost', 'lost', 'outbid']);
                    $sale = $d < 2 ? now()->addDays(rand(0, 4))->setTime(rand(9, 17), 0) : (clone $at)->addDays(rand(0, 3));
                    Bid::create($base + ['lot_number' => $lot, 'vin' => $vin, 'description' => $desc, 'location' => $pick($this->yards), 'type' => $pick(['prebid', 'prebid', 'live', 'buy_now']), 'phase' => $pick(['preliminary', 'live', 'final']), 'amount' => $amount, 'max_bid' => $amount + rand(1, 10) * 100, 'current_bid' => $amount + rand(0, 6) * 50, 'status' => $status, 'sale_date' => $sale, 'placed_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
                    ActivityLog::create($base + ['type' => 'bid', 'target' => $lot, 'description' => $desc, 'amount' => $amount, 'created_at' => $at, 'updated_at' => $at]);
                } elseif ($r < 5) {
                    $exists = $d < 30;
                    if ($exists) {
                        WatchlistItem::create($base + ['lot_number' => $lot, 'vin' => $vin, 'description' => $desc, 'sale_status' => $pick(['On Approval', 'Pure Sale', 'Minimum Bid', 'Sold']), 'auction_status' => $pick(['Upcoming', 'Live', 'Ended']), 'sale_date' => (clone $at)->addDays(rand(1, 7)), 'added_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
                    }
                    ActivityLog::create($base + ['type' => 'watchlist_add', 'target' => $lot, 'description' => $desc, 'created_at' => $at, 'updated_at' => $at]);
                    if (! $exists && rand(0, 1)) {
                        ActivityLog::create($base + ['type' => 'watchlist_remove', 'target' => $lot, 'description' => $desc, 'created_at' => (clone $at)->addHours(5), 'updated_at' => $at]);
                    }
                }
            }
        }

        // Won vehicles -> winning bid + invoice (Won By resolved automatically from the bid)
        for ($i = 0; $i < 22; $i++) {
            $a = $acc[$i % 3 === 0 ? 0 : ($i % 3 === 1 ? 2 : $pick([1, 3, 4]))];
            $cid = $pick($accountCustomers[$a->id]);
            [$desc, $vin] = $this->vehicles[$i % count($this->vehicles)];
            $lot = (string) rand(40000000, 79999999);
            $date = Carbon::now()->subDays($i * 3 + rand(0, 2))->setTime(rand(10, 18), rand(0, 59));
            $amount = rand(45, 260) * 100 + rand(0, 99);
            $ip = $pick($this->ips);
            $base = ['customer_id' => $cid, 'account_id' => $a->id, 'provider' => $a->provider, 'ip_address' => $ip, 'app' => 'extension'];
            Bid::create($base + ['lot_number' => $lot, 'vin' => $vin, 'description' => $desc, 'location' => $pick($this->yards), 'type' => 'live', 'phase' => 'final', 'amount' => $amount - 400, 'max_bid' => $amount, 'current_bid' => $amount - 400, 'status' => 'won', 'sale_date' => $date, 'placed_at' => (clone $date)->subMinutes(3), 'created_at' => $date, 'updated_at' => $date]);
            ActivityLog::create($base + ['type' => 'bid', 'target' => $lot, 'description' => $desc, 'amount' => $amount - 400, 'created_at' => (clone $date)->subMinutes(3), 'updated_at' => $date]);
            ActivityLog::create($base + ['type' => 'won', 'target' => $lot, 'description' => $desc, 'amount' => $amount - 400, 'created_at' => $date, 'updated_at' => $date]);
            $status = $i < 3 ? 'pending' : ($i === 5 || $i === 9 ? 'overdue' : 'paid');
            $due = $status === 'overdue' ? $date->copy()->addDays(3) : ($status === 'pending' ? now()->addDays($i + 1) : $date->copy()->addDays(3));
            Invoice::create(['account_id' => $a->id, 'provider' => $a->provider, 'invoice_number' => $lot, 'vin' => $vin, 'description' => $desc, 'location' => $pick($this->yards), 'type' => 'lot', 'status' => $status, 'sale_price' => $amount - 400, 'fees' => 400, 'amount' => $amount, 'amount_due' => $status === 'paid' ? 0 : $amount, 'invoice_date' => $date->toDateString(), 'due_date' => $due->toDateString(), 'created_at' => $date]);
        }
        Invoice::create(['account_id' => $acc[2]->id, 'provider' => 'copart', 'invoice_number' => 'RL-'.rand(100000, 999999), 'description' => 'Relist fee - lot not paid on time', 'type' => 'relist', 'status' => 'pending', 'amount' => 150, 'amount_due' => 150, 'invoice_date' => now()->subDays(2)->toDateString(), 'due_date' => now()->addDays(2)->toDateString()]);
        Invoice::create(['account_id' => $acc[0]->id, 'provider' => 'iaai', 'invoice_number' => 'ST-'.rand(100000, 999999), 'description' => 'Storage fee - 6 days', 'type' => 'storage', 'status' => 'paid', 'amount' => 120, 'amount_due' => 0, 'invoice_date' => now()->subDays(12)->toDateString(), 'due_date' => now()->subDays(9)->toDateString()]);

        // Finance
        Transaction::create(['date' => now()->subDays(3), 'amount' => 200, 'type' => 'add_balance', 'status' => 'completed', 'session_id' => 'man_demo0000000001', 'method' => 'card']);
        Transaction::create(['date' => now()->subDays(3), 'amount' => 200, 'type' => 'add_balance', 'status' => 'cancelled', 'session_id' => 'man_demo0000000002', 'method' => 'card']);
        Transaction::create(['date' => now()->subDays(20), 'amount' => 300, 'type' => 'add_balance', 'status' => 'completed', 'session_id' => 'man_demo0000000003', 'method' => 'bank_transfer']);
        foreach (Invoice::where('type', 'lot')->latest('invoice_date')->limit(5)->get() as $inv) {
            Transaction::create(['date' => $inv->invoice_date, 'amount' => 20, 'type' => 'vehicle_charge', 'status' => 'completed', 'description' => $inv->description.' — '.$inv->invoice_number, 'won_by_customer_id' => $inv->won_by_customer_id, 'invoice_id' => $inv->id]);
        }

        // Shipping
        $r1 = ShippingRate::create(['name' => 'Standard Rates Q4 2026', 'valid_from' => '2026-10-01', 'valid_until' => '2026-12-31', 'towing' => 0, 'loading' => 150, 'surcharges' => 75, 'lines' => [
            ['yard' => 'DALLAS', 'state' => 'TX', 'port' => 'Houston, TX', 'destination' => 'Umm Qasr, IQ', 'price' => 1850],
            ['yard' => 'ORLANDO SOUTH', 'state' => 'FL', 'port' => 'Jacksonville, FL', 'destination' => 'Umm Qasr, IQ', 'price' => 1950],
            ['yard' => 'LOS ANGELES', 'state' => 'CA', 'port' => 'Long Beach, CA', 'destination' => 'Jebel Ali, AE', 'price' => 1700],
            ['yard' => 'SOMERVILLE', 'state' => 'NJ', 'port' => 'Newark, NJ', 'destination' => 'Mersin, TR', 'price' => 1600],
        ]]);
        $r1->customers()->sync($customers->pluck('id')->take(4));
        $r2 = ShippingRate::create(['name' => 'VIP Dealer Rates', 'valid_from' => '2026-09-01', 'valid_until' => '2027-02-28', 'towing' => 0, 'loading' => 100, 'surcharges' => 50, 'lines' => [
            ['yard' => 'DALLAS', 'state' => 'TX', 'port' => 'Houston, TX', 'destination' => 'Umm Qasr, IQ', 'price' => 1600],
            ['yard' => 'ATLANTA EAST', 'state' => 'GA', 'port' => 'Savannah, GA', 'destination' => 'Umm Qasr, IQ', 'price' => 1750],
        ]]);
        $r2->customers()->sync([$customers[4]->id, $customers[1]->id]);
        foreach ([[$r1, 0, 0, 14050], [$r1, 1, 1, 22656], [$r2, 0, 4, 18400]] as [$rate, $line, $ci, $sale]) {
            $l = $rate->lines[$line];
            Quotation::create(['shipping_rate_id' => $rate->id, 'customer_id' => $customers[$ci]->id, 'lot_number' => (string) rand(40000000, 79999999), 'vin' => $this->vehicles[$line + $ci][1], 'description' => $this->vehicles[$line + $ci][0], 'pickup_yard' => $l['yard'].', '.$l['state'], 'loading_port' => $l['port'], 'delivery_port' => $l['destination'], 'sale_amount' => $sale, 'shipping' => $l['price'] + $rate->towing + $rate->loading + $rate->surcharges]);
        }

        // Numbers (UI demo only)
        PhoneNumber::create(['number' => '+1 (555) 214-7730', 'country' => 'US', 'status' => 'active', 'account_id' => $acc[2]->id, 'next_bill_at' => now()->addDays(12)]);
        PhoneNumber::create(['number' => '+1 (604) 555-0187', 'country' => 'CA', 'status' => 'active', 'account_id' => $acc[1]->id, 'next_bill_at' => now()->addDays(20)]);
        PhoneNumber::create(['number' => '+1 (555) 980-1123', 'country' => 'US', 'status' => 'paused', 'account_id' => null, 'next_bill_at' => now()->addDays(5)]);
    }
}
