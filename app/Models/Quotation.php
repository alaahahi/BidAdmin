<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quotation extends Model
{
    protected $connection = 'tenant';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sale_amount' => 'decimal:2', 'shipping' => 'decimal:2', 'total' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Quotation $q) => $q->token ??= Str::random(20));
        static::saving(fn (Quotation $q) => $q->total = (float) $q->sale_amount + (float) $q->shipping);
    }

    public function shippingRate()
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
