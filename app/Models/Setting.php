<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $guarded = ['id'];

    public const DEFAULTS = [
        'company_name' => 'BidAdmin',
        'logo_path' => null,
        'brand_color' => '#111827',
        'timezone' => 'Asia/Baghdad',
        'invoice_timezone' => 'account', // account | invoice
        'low_data_mode' => '0',
        'language' => 'en',
        'currency' => 'USD',
        'customer_portal' => '1',
        'customers_can_see_invoices' => '1',
        'default_per_page' => '10',
        'session_lifetime' => '120',
    ];

    public static function all_settings(): array
    {
        if (app()->bound('bidadmin.settings')) {
            return app('bidadmin.settings');
        }
        try {
            if (! Schema::hasTable('settings')) {
                return self::DEFAULTS;
            }

            $values = Cache::remember('bidadmin.settings', 60, fn () => array_merge(self::DEFAULTS, static::pluck('value', 'key')->all()));
            app()->instance('bidadmin.settings', $values);

            return $values;
        } catch (\Throwable) {
            return self::DEFAULTS;
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::all_settings()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $k => $v) {
            static::updateOrCreate(['key' => $k], ['value' => is_bool($v) ? ($v ? '1' : '0') : $v]);
        }
        Cache::forget('bidadmin.settings');
        app()->forgetInstance('bidadmin.settings');
    }
}
