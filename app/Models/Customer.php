<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    protected $connection = 'tenant';
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'bid_limit' => 'decimal:2', 'credit_limit' => 'decimal:2', 'can_bid' => 'boolean'];
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class)->withPivot('assigned_at')->withTimestamps();
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function watchlistItems()
    {
        return $this->hasMany(WatchlistItem::class);
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'won_by_customer_id');
    }

    public function shippingRates()
    {
        return $this->belongsToMany(ShippingRate::class);
    }
}
