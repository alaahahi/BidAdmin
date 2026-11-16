<?php

namespace App\Mcp\Tools;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\AssignmentHistory;
use App\Models\Broker;
use App\Models\Customer;
use App\Mcp\Tools\Concerns\RequiresAdmin;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('assign_account')]
#[Description('Assign a customer and/or broker to an auction account (admin only). Does not unassign existing relations.')]
#[IsDestructive]
class AssignAccountTool extends Tool
{
    use RequiresAdmin;

    public function handle(Request $request): Response
    {
        if ($denied = $this->ensureAdmin($request)) {
            return $denied;
        }

        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'broker_id' => ['nullable', 'integer', 'exists:brokers,id'],
        ]);

        if (empty($data['customer_id']) && empty($data['broker_id'])) {
            return Response::error('Provide customer_id and/or broker_id.');
        }

        $account = Account::findOrFail($data['account_id']);
        $userId = $request->user()->id;

        if (! empty($data['customer_id']) && ! $account->customers()->where('customers.id', $data['customer_id'])->exists()) {
            $account->customers()->attach($data['customer_id'], ['assigned_at' => now()]);
            AssignmentHistory::create([
                'account_id' => $account->id,
                'subject_type' => 'customer',
                'subject_id' => $data['customer_id'],
                'subject_name' => Customer::withTrashed()->find($data['customer_id'])?->name,
                'action' => 'assigned',
                'user_id' => $userId,
            ]);
        }

        if (! empty($data['broker_id']) && ! $account->brokers()->where('brokers.id', $data['broker_id'])->exists()) {
            $account->brokers()->attach($data['broker_id'], ['assigned_at' => now()]);
            AssignmentHistory::create([
                'account_id' => $account->id,
                'subject_type' => 'broker',
                'subject_id' => $data['broker_id'],
                'subject_name' => Broker::withTrashed()->find($data['broker_id'])?->name,
                'action' => 'assigned',
                'user_id' => $userId,
            ]);
        }

        $account->load(['customers:id,name,code', 'brokers:id,name']);

        return Response::structured([
            'data' => (new AccountResource($account))->resolve(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()->description('Auction account id')->required(),
            'customer_id' => $schema->integer()->description('Customer to assign'),
            'broker_id' => $schema->integer()->description('Broker to assign'),
        ];
    }
}
