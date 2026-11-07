<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'max_bid' => 'decimal:2', 'current_bid' => 'decimal:2', 'sale_date' => 'datetime', 'placed_at' => 'datetime'];
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }
}
