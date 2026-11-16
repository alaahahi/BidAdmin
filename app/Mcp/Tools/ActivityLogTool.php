<?php

namespace App\Mcp\Tools;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
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

#[Name('activity_log')]
#[Description('Query activity logs. Customers only see their own activity. Limit max 100.')]
#[IsReadOnly]
class ActivityLogTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        if ($this->user($request) instanceof Response) {
            return $this->user($request);
        }

        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:40'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = min(100, max(1, (int) ($data['limit'] ?? 25)));

        $q = Scope::owned(ActivityLog::query()->with(['customer:id,name', 'account:id,username']));
        if (! empty($data['customer_id']) && ! Scope::isCustomer()) {
            $q->where('customer_id', $data['customer_id']);
        }
        if (! empty($data['type'])) {
            $q->where('type', $data['type']);
        }
        if (! empty($data['from'])) {
            $q->whereDate('created_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $q->whereDate('created_at', '<=', $data['to']);
        }

        $rows = $q->latest('id')->limit($limit)->get();

        return Response::structured([
            'data' => ActivityLogResource::collection($rows)->resolve(),
            'meta' => ['limit' => $limit, 'count' => $rows->count()],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->description('Filter by customer id (admins only)'),
            'type' => $schema->string()->description('Activity type (login, lot_view, bid, mcp_tool, ...)'),
            'from' => $schema->string()->description('From date (YYYY-MM-DD)'),
            'to' => $schema->string()->description('To date (YYYY-MM-DD)'),
            'limit' => $schema->integer()->description('Max rows (default 25, max 100)'),
        ];
    }
}
