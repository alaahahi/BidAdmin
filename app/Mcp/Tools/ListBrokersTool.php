<?php

namespace App\Mcp\Tools;

use App\Http\Resources\BrokerResource;
use App\Models\Broker;
use App\Mcp\Support\Paginates;
use App\Mcp\Tools\Concerns\RequiresAuth;
use App\Support\Lists;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_brokers')]
#[Description('List brokers. Available to any authenticated tenant user.')]
#[IsReadOnly]
class ListBrokersTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        if ($this->user($request) instanceof Response) {
            return $this->user($request);
        }

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:190'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = Broker::query();
        Lists::search($q, $data['search'] ?? null, ['name', 'email']);

        $paginator = Paginates::paginate($q->orderBy('name'), $request);

        return Response::structured(Paginates::toPayload(
            $paginator,
            BrokerResource::collection($paginator->items())->resolve(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search name or email'),
            'page' => $schema->integer()->description('Page number'),
            'per_page' => $schema->integer()->description('Results per page (default 25)'),
        ];
    }
}
