<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait RequiresAdmin
{
    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isAdmin();
    }

    protected function ensureAdmin(Request $request): ?Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }
        if (! $user->isAdmin()) {
            return Response::error('Admin role required for this tool.');
        }

        return null;
    }
}
