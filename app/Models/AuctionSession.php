<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuctionSession extends Model
{
    protected $connection = 'tenant';

    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['cookies', 'local_storage'];

    protected function casts(): array
    {
        return [
            'cookies' => 'encrypted:array',
            'local_storage' => 'encrypted:array',
            'captured_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function isValid(): bool
    {
        if (empty($this->cookies) && empty($this->local_storage)) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function status(): string
    {
        if (empty($this->cookies) && empty($this->local_storage)) {
            return 'missing';
        }

        return $this->isValid() ? 'valid' : 'expired';
    }
}
