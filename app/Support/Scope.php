<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Restricts queries for customer users to their own data. */
class Scope
{
    public static function user(): ?User
    {
        return auth()->user();
    }

    public static function isCustomer(): bool
    {
        return (bool) self::user()?->isCustomer();
    }

    public static function customerId(): ?int
    {
        return self::isCustomer() ? (self::user()->customer_id ?? 0) : null;
    }

    public static function accounts(Builder $q): Builder
    {
        if (self::isCustomer()) {
            $q->whereHas('customers', fn ($c) => $c->where('customers.id', self::customerId()));
        }

        return $q;
    }

    /** For tables with a customer_id column (bids, watchlist, activity). */
    public static function owned(Builder $q, string $column = 'customer_id'): Builder
    {
        if (self::isCustomer()) {
            $q->where($column, self::customerId());
        }

        return $q;
    }
}
