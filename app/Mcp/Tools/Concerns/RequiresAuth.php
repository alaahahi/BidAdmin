<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait RequiresAuth
{
    protected function user(Request $request): User|Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }

        return $user;
    }
}
