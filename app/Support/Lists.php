<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class Lists
{
    public static function perPage(Request $r): int
    {
        $pp = (int) $r->input('per_page', \App\Models\Setting::get('default_per_page', 10));

        return in_array($pp, [10, 25, 50, 100]) ? $pp : 10;
    }

    public static function sort(Builder $q, Request $r, array $allowed, string $default = 'created_at', string $dir = 'desc'): Builder
    {
        $sort = $r->input('sort');
        $d = $r->input('dir') === 'asc' ? 'asc' : 'desc';
        if ($sort && in_array($sort, $allowed, true)) {
            return $q->orderBy($sort, $d);
        }

        return $q->orderBy($default, $dir);
    }

    public static function search(Builder $q, ?string $term, array $columns): Builder
    {
        if ($term === null || trim($term) === '') {
            return $q;
        }
        $term = '%'.trim($term).'%';

        return $q->where(function ($w) use ($columns, $term) {
            foreach ($columns as $c) {
                if (str_contains($c, '.')) {
                    [$rel, $col] = explode('.', $c, 2);
                    $w->orWhereHas($rel, fn ($x) => $x->where($col, 'like', $term));
                } else {
                    $w->orWhere($c, 'like', $term);
                }
            }
        });
    }

    public static function dateRange(Builder $q, Request $r, string $column): Builder
    {
        if ($r->filled('from')) {
            $q->whereDate($column, '>=', $r->input('from'));
        }
        if ($r->filled('to')) {
            $q->whereDate($column, '<=', $r->input('to'));
        }

        return $q;
    }

    public static function filters(Request $r, array $keys): array
    {
        return collect($r->only(array_merge($keys, ['search', 'sort', 'dir', 'per_page', 'trashed', 'from', 'to'])))->filter(fn ($v) => $v !== null && $v !== '')->all();
    }
}
