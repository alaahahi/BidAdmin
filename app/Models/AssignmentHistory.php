<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentHistory extends Model
{
    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
