<?php

namespace App\Mcp\Tools;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Mcp\Tools\Concerns\RequiresAuth;
use App\Support\Scope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_account')]
#[Description('Get a single auction account by id. Password and encrypted fields are never returned.')]
#[IsReadOnly]
class GetAccountTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        if ($this->user($request) instanceof Response) {
            return $this->user($request);
        }

        $data = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $account = Scope::accounts(Account::query()->with(['customers:id,name,code', 'brokers:id,name']))
            ->whereKey($data['id'])
            ->first();

        if (! $account) {
            return Response::error('Account not found.');
        }

        return Response::structured([
            'data' => (new AccountResource($account))->resolve(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Account id')->required(),
        ];
    }
}
