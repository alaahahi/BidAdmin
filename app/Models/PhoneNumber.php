<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneNumber extends Model
{
    protected $connection = 'tenant';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['next_bill_at' => 'date'];
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }
}
