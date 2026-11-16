<?php

namespace App\Mcp\Tools;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Mcp\Support\Paginates;
use App\Mcp\Tools\Concerns\RequiresAuth;
use App\Support\Lists;
use App\Support\Scope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_customers')]
#[Description('List customers. Customer portal users only see themselves.')]
#[IsReadOnly]
class ListCustomersTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        $user = $this->user($request);
        if ($user instanceof Response) {
            return $user;
        }

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:190'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = Customer::query();
        if ($user->isCustomer()) {
            $q->whereKey($user->customer_id);
        }
        Lists::search($q, $data['search'] ?? null, ['name', 'code', 'email', 'phone']);

        $paginator = Paginates::paginate($q->orderBy('name'), $request);

        return Response::structured(Paginates::toPayload(
            $paginator,
            CustomerResource::collection($paginator->items())->resolve(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search name, code, email, phone'),
            'page' => $schema->integer()->description('Page number'),
            'per_page' => $schema->integer()->description('Results per page (default 25)'),
        ];
    }
}
