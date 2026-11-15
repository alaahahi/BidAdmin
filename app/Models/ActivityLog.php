<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $connection = 'tenant';
    public const TYPES = ['login' => 'Login', 'lot_view' => 'Lot View', 'bid' => 'Bid Placed', 'watchlist_add' => 'Watchlist Add', 'watchlist_remove' => 'Watchlist Remove', 'won' => 'Car Won'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'amount' => 'decimal:2'];
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public static function record(string $type, array $attrs = []): self
    {
        return static::create(array_merge([
            'type' => $type,
            'ip_address' => request()?->ip(),
            'app' => 'web',
        ], $attrs));
    }
}
