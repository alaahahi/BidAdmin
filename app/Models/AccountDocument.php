<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDocument extends Model
{
    public const GROUPS = ['Identity' => ['Passport', 'National ID', 'Driver License'], 'Business' => ['Trade License', 'Tax Certificate', 'Dealer License'], 'Agreements' => ['Broker Agreement', 'Power of Attorney'], 'Other' => ['Other']];

    protected $guarded = ['id'];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->path ? asset('storage/'.$this->path) : null;
    }
}
