<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchlistItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sale_date' => 'datetime', 'added_at' => 'datetime'];
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
