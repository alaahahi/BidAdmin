<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\InvoiceController;
use App\Http\Resources\InvoiceResource;
use App\Models\Account;
use App\Models\Invoice;
use App\Mcp\Tools\Concerns\RequiresAdmin;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('create_invoice')]
#[Description('Create an invoice (admin only).')]
#[IsDestructive]
class CreateInvoiceTool extends Tool
{
    use RequiresAdmin;

    public function handle(Request $request): Response
    {
        if ($denied = $this->ensureAdmin($request)) {
            return $denied;
        }

        try {
            $data = $request->validate([
                'account_id' => ['nullable', 'exists:accounts,id'],
                'provider' => ['nullable', Rule::in(array_keys(Account::PROVIDERS))],
                'invoice_number' => ['required', 'string', 'max:40'],
                'vin' => ['nullable', 'string', 'max:40'],
                'description' => ['nullable', 'string', 'max:255'],
                'location' => ['nullable', 'string', 'max:190'],
                'image_url' => ['nullable', 'url', 'max:500'],
                'type' => ['required', Rule::in(array_keys(InvoiceController::TYPES))],
                'status' => ['required', Rule::in(['paid', 'pending', 'overdue'])],
                'amount' => ['required', 'numeric', 'min:0'],
                'amount_due' => ['nullable', 'numeric', 'min:0'],
                'sale_price' => ['nullable', 'numeric', 'min:0'],
                'fees' => ['nullable', 'numeric', 'min:0'],
                'invoice_date' => ['nullable', 'date'],
                'due_date' => ['nullable', 'date'],
                'won_by_customer_id' => ['nullable', 'exists:customers,id'],
                'notes' => ['nullable', 'string'],
            ]);
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->implode(' '));
        }

        $data['amount_due'] ??= $data['status'] === 'paid' ? 0 : $data['amount'];
        $data['invoice_date'] ??= now()->toDateString();
        $invoice = Invoice::create($data);

        return Response::structured([
            'data' => (new InvoiceResource($invoice->load(['account:id,username,provider', 'wonBy:id,name,code'])))->resolve(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()->description('Auction account id'),
            'provider' => $schema->string()->description('Auction provider'),
            'invoice_number' => $schema->string()->description('Lot / invoice number')->required(),
            'vin' => $schema->string()->description('VIN'),
            'description' => $schema->string()->description('Description'),
            'location' => $schema->string()->description('Location'),
            'type' => $schema->string()->description('lot | relist | storage | fee')->required(),
            'status' => $schema->string()->description('paid | pending | overdue')->required(),
            'amount' => $schema->number()->description('Total amount')->required(),
            'amount_due' => $schema->number()->description('Amount due'),
            'sale_price' => $schema->number()->description('Sale price'),
            'fees' => $schema->number()->description('Fees'),
            'invoice_date' => $schema->string()->description('Invoice date YYYY-MM-DD'),
            'due_date' => $schema->string()->description('Due date YYYY-MM-DD'),
            'won_by_customer_id' => $schema->integer()->description('Winning customer id'),
            'notes' => $schema->string()->description('Notes'),
        ];
    }
}
