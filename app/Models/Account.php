<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    protected $connection = 'tenant';
    use HasFactory, SoftDeletes;

    public const PROVIDERS = ['copart' => 'Copart', 'iaai' => 'IAAI', 'manheim' => 'Manheim', 'edgepipeline' => 'EdgePipeline', 'npa' => 'NPA'];

    public const REGIONS = [
        'copart' => ['USA', 'CAN', 'UK', 'UAE', 'DE'],
        'iaai' => ['USA', 'CAN', 'UK'],
        'manheim' => ['USA', 'CAN'],
        'edgepipeline' => ['USA'],
        'npa' => ['USA'],
    ];

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'tags' => 'array',
            'balance_due' => 'decimal:2',
            'next_billing_date' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class)->withPivot('assigned_at')->withTimestamps();
    }

    public function brokers()
    {
        return $this->belongsToMany(Broker::class)->withPivot('assigned_at')->withTimestamps();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function watchlistItems()
    {
        return $this->hasMany(WatchlistItem::class);
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function documents()
    {
        return $this->hasMany(AccountDocument::class);
    }

    public function histories()
    {
        return $this->hasMany(AssignmentHistory::class)->latest();
    }
}
