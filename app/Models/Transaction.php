<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $connection = 'tenant';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'decimal:2'];
    }

    public function wonBy()
    {
        return $this->belongsTo(Customer::class, 'won_by_customer_id')->withTrashed();
    }

    public static function balance(): float
    {
        $in = static::where('status', 'completed')->whereIn('type', ['add_balance', 'refund'])->sum('amount');
        $out = static::where('status', 'completed')->where('type', 'vehicle_charge')->sum('amount');

        return round($in - $out, 2);
    }
}
