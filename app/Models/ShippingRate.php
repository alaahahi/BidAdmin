<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $connection = 'tenant';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['lines' => 'array', 'valid_from' => 'date', 'valid_until' => 'date', 'towing' => 'decimal:2', 'loading' => 'decimal:2', 'surcharges' => 'decimal:2'];
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class);
    }
}
