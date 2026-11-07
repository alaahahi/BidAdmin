<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Broker extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['covered' => 'boolean'];
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class)->withPivot('assigned_at')->withTimestamps();
    }
}
