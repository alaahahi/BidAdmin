<?php

namespace App\Mcp\Tools;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Setting;
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

#[Name('list_invoices')]
#[Description('List invoices. Customers only see invoices they won when portal invoices are enabled.')]
#[IsReadOnly]
class ListInvoicesTool extends Tool
{
    use RequiresAuth;

    public function handle(Request $request): Response
    {
        if ($this->user($request) instanceof Response) {
            return $this->user($request);
        }

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:paid,pending,overdue'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = Invoice::query()->with(['account:id,username,provider', 'wonBy:id,name,code']);
        if (Scope::isCustomer()) {
            $q->where('won_by_customer_id', Scope::customerId());
            if (Setting::get('customers_can_see_invoices', '1') !== '1') {
                $q->whereRaw('1 = 0');
            }
        } elseif (! empty($data['customer_id'])) {
            $q->where('won_by_customer_id', $data['customer_id']);
        }
        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }

        $paginator = Paginates::paginate($q->latest('invoice_date'), $request);

        return Response::structured(Paginates::toPayload(
            $paginator,
            InvoiceResource::collection($paginator->items())->resolve(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('paid | pending | overdue'),
            'customer_id' => $schema->integer()->description('Filter by winning customer (admins only)'),
            'page' => $schema->integer()->description('Page number'),
            'per_page' => $schema->integer()->description('Results per page (default 25)'),
        ];
    }
}
