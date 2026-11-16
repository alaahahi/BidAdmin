<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Invoice */
class InvoiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'provider' => $this->provider,
            'invoice_number' => $this->invoice_number,
            'vin' => $this->vin,
            'description' => $this->description,
            'location' => $this->location,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => $this->amount,
            'amount_due' => $this->amount_due,
            'sale_price' => $this->sale_price,
            'fees' => $this->fees,
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at?->toDateString(),
            'won_by_customer_id' => $this->won_by_customer_id,
            'notes' => $this->notes,
            'account' => $this->whenLoaded('account', fn () => $this->account ? [
                'id' => $this->account->id, 'username' => $this->account->username, 'provider' => $this->account->provider,
            ] : null),
            'won_by' => $this->whenLoaded('wonBy', fn () => $this->wonBy ? [
                'id' => $this->wonBy->id, 'name' => $this->wonBy->name, 'code' => $this->wonBy->code,
            ] : null),
        ];
    }
}
