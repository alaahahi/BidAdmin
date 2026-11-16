<?php

namespace App\Mcp\Tools;

use App\Models\Account;
use App\Models\Bid;
use App\Models\Invoice;
use App\Mcp\Tools\Concerns\RequiresAuth;
use App\Support\Scope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('dashboard_stats')]
#[Description('Return key BidAdmin dashboard counters for the current tenant user.')]
#[IsReadOnly]
class DashboardStatsTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        if ($this->user($request) instanceof Response) {
            return $this->user($request);
        }

        $inv = Invoice::query();
        if (Scope::isCustomer()) {
            $inv->where('won_by_customer_id', Scope::customerId());
        }

        return Response::structured([
            'data' => [
                'accounts' => Scope::accounts(Account::query())->count(),
                'unpaid_invoices' => (clone $inv)->where('status', '!=', 'paid')->count(),
                'unpaid_amount' => (float) (clone $inv)->where('status', '!=', 'paid')->sum('amount_due'),
                'overdue_invoices' => (clone $inv)->where('status', 'overdue')->count(),
                'overdue_amount' => (float) (clone $inv)->where('status', 'overdue')->sum('amount_due'),
                'bids' => Scope::owned(Bid::query())->count(),
                'cars_won' => (clone $inv)->where('type', 'lot')->count(),
                'active_bids' => Scope::owned(Bid::query())->whereIn('status', ['active', 'winning'])->count(),
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
