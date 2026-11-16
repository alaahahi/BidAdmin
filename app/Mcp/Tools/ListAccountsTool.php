<?php

namespace App\Mcp\Tools;

use App\Http\Resources\AccountResource;
use App\Models\Account;
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

#[Name('list_accounts')]
#[Description('List auction accounts (Copart, IAAI, Manheim, EdgePipeline, NPA). Customers only see assigned accounts. Passwords are never returned.')]
#[IsReadOnly]
class ListAccountsTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        if ($this->user($request) instanceof Response) {
            return $this->user($request);
        }

        $data = $request->validate([
            'auction' => ['nullable', 'string', 'in:'.implode(',', array_keys(Account::PROVIDERS))],
            'status' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:190'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = Scope::accounts(Account::query()->with(['customers:id,name,code', 'brokers:id,name']));
        if (! empty($data['auction'])) {
            $q->where('provider', $data['auction']);
        }
        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        Lists::search($q, $data['search'] ?? null, ['username', 'company', 'account_number', 'email']);

        $paginator = Paginates::paginate($q->latest('id'), $request);

        return Response::structured(Paginates::toPayload(
            $paginator,
            AccountResource::collection($paginator->items())->resolve(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'auction' => $schema->string()->description('Auction provider: copart, iaai, manheim, edgepipeline, npa'),
            'status' => $schema->string()->description('Account status filter'),
            'search' => $schema->string()->description('Search username, company, account number, email'),
            'page' => $schema->integer()->description('Page number'),
            'per_page' => $schema->integer()->description('Results per page (default 25, max 100)'),
        ];
    }
}
