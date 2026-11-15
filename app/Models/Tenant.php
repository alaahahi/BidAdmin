<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $connection = 'central';

    protected $fillable = ['name', 'slug', 'db_name', 'admin_email', 'status'];

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deleted_at === null;
    }

    public static function dbNameFor(string $slug): string
    {
        return 'bidadmin_'.$slug;
    }
}
