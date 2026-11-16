<?php

namespace App\Mcp\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;

class Paginates
{
    public static function perPage(Request $request, int $default = 25): int
    {
        $perPage = (int) ($request->get('per_page') ?? $default);

        return max(1, min(100, $perPage ?: $default));
    }

    /**
     * @return array{data: mixed, meta: array{current_page: int, last_page: int, per_page: int, total: int}}
     */
    public static function toPayload(LengthAwarePaginator $paginator, mixed $data): array
    {
        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public static function paginate(Builder $query, Request $request, int $default = 25): LengthAwarePaginator
    {
        return $query->paginate(self::perPage($request, $default), ['*'], 'page', max(1, (int) ($request->get('page') ?? 1)));
    }
}
