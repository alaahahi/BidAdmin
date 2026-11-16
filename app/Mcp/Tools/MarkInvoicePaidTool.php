<?php

namespace App\Mcp\Tools;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Mcp\Tools\Concerns\RequiresAdmin;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('mark_invoice_paid')]
#[Description('Mark an invoice as paid (admin only).')]
#[IsDestructive]
class MarkInvoicePaidTool extends Tool
{
    use RequiresAdmin;

    public function handle(Request $request): Response
    {
        if ($denied = $this->ensureAdmin($request)) {
            return $denied;
        }

        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:invoices,id'],
        ]);

        $invoice = Invoice::findOrFail($data['id']);
        $invoice->update([
            'status' => 'paid',
            'amount_due' => 0,
            'paid_at' => now(),
        ]);

        return Response::structured([
            'data' => (new InvoiceResource($invoice->fresh()->load(['account:id,username,provider', 'wonBy:id,name,code'])))->resolve(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Invoice id')->required(),
        ];
    }
}
