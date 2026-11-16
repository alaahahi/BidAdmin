<?php

namespace App\Mcp\Tools;

use App\Http\Resources\BidResource;
use App\Models\Bid;
use App\Mcp\Support\Paginates;
use App\Mcp\Tools\Concerns\RequiresAuth;
use App\Support\Scope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_bids')]
#[Description('List bids with optional filters. Customers only see their own bids.')]
#[IsReadOnly]
class ListBidsTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        if ($this->user($request) instanceof Response) {
            return $this->user($request);
        }

        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'account_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = Scope::owned(Bid::query()->with(['customer:id,name', 'account:id,username,provider']));
        if (! empty($data['customer_id']) && ! Scope::isCustomer()) {
            $q->where('customer_id', $data['customer_id']);
        }
        if (! empty($data['account_id'])) {
            $q->where('account_id', $data['account_id']);
        }
        if (! empty($data['from'])) {
            $q->whereDate('placed_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $q->whereDate('placed_at', '<=', $data['to']);
        }

        $paginator = Paginates::paginate($q->latest('placed_at'), $request);

        return Response::structured(Paginates::toPayload(
            $paginator,
            BidResource::collection($paginator->items())->resolve(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->description('Filter by customer id (admins only)'),
            'account_id' => $schema->integer()->description('Filter by auction account id'),
            'from' => $schema->string()->description('From date (YYYY-MM-DD)'),
            'to' => $schema->string()->description('To date (YYYY-MM-DD)'),
            'page' => $schema->integer()->description('Page number'),
            'per_page' => $schema->integer()->description('Results per page (default 25)'),
        ];
    }
}
