<?php

namespace App\Mcp\Tools;

use App\Http\Resources\ActivityLogResource;
use App\Mcp\Tools\Concerns\RequiresAdmin;
use App\Services\EventService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('log_activity')]
#[Description('Log an activity event (admin only). Reuses the same validation as POST /api/activity.')]
#[IsDestructive]
class LogActivityTool extends Tool
{
    use RequiresAdmin;

    public function handle(Request $request, EventService $events): Response
    {
        if ($denied = $this->ensureAdmin($request)) {
            return $denied;
        }

        try {
            $data = $request->validate($events->activityRules());
            $log = $events->logActivity($request->user(), $data + ['app' => 'mcp', 'source' => 'mcp'], request()?->ip());
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->implode(' '));
        }

        return Response::structured([
            'data' => (new ActivityLogResource($log))->resolve(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('login | lot_view | bid | watchlist_add | watchlist_remove | won')->required(),
            'account_id' => $schema->integer()->description('Auction account id'),
            'customer_id' => $schema->integer()->description('Customer id'),
            'provider' => $schema->string()->description('Auction provider'),
            'target' => $schema->string()->description('Target / lot number'),
            'lot_number' => $schema->string()->description('Lot number (alias for target)'),
            'description' => $schema->string()->description('Description'),
            'amount' => $schema->number()->description('Amount'),
            'meta' => $schema->object()->description('Optional metadata object'),
        ];
    }
}
