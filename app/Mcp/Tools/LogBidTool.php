<?php

namespace App\Mcp\Tools;

use App\Http\Resources\BidResource;
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

#[Name('log_bid')]
#[Description('Log a bid (admin only). Reuses the same validation as POST /api/bids.')]
#[IsDestructive]
class LogBidTool extends Tool
{
    use RequiresAdmin;

    public function handle(Request $request, EventService $events): Response
    {
        if ($denied = $this->ensureAdmin($request)) {
            return $denied;
        }

        try {
            $data = $request->validate($events->bidRules());
            $bid = $events->logBid($request->user(), $data + ['app' => 'mcp', 'source' => 'mcp'], request()?->ip());
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->implode(' '));
        }

        return Response::structured([
            'data' => (new BidResource($bid->load(['customer:id,name', 'account:id,username,provider'])))->resolve(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()->description('Auction account id'),
            'customer_id' => $schema->integer()->description('Customer placing the bid'),
            'provider' => $schema->string()->description('Auction provider'),
            'lot_number' => $schema->string()->description('Lot number')->required(),
            'vin' => $schema->string()->description('VIN'),
            'description' => $schema->string()->description('Vehicle description'),
            'image_url' => $schema->string()->description('Image URL'),
            'location' => $schema->string()->description('Yard / location'),
            'type' => $schema->string()->description('prebid | live | buy_now'),
            'phase' => $schema->string()->description('preliminary | live | final'),
            'amount' => $schema->number()->description('Bid amount')->required(),
            'max_bid' => $schema->number()->description('Max bid'),
            'status' => $schema->string()->description('active | winning | outbid | won | lost'),
            'sale_date' => $schema->string()->description('Sale date YYYY-MM-DD'),
        ];
    }
}
