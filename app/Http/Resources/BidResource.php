<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bid */
class BidResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'customer_id' => $this->customer_id,
            'provider' => $this->provider,
            'lot_number' => $this->lot_number,
            'vin' => $this->vin,
            'description' => $this->description,
            'location' => $this->location,
            'type' => $this->type,
            'phase' => $this->phase,
            'amount' => $this->amount,
            'max_bid' => $this->max_bid,
            'current_bid' => $this->current_bid,
            'status' => $this->status,
            'sale_date' => $this->sale_date,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id, 'name' => $this->customer->name,
            ] : null),
            'account' => $this->whenLoaded('account', fn () => $this->account ? [
                'id' => $this->account->id, 'username' => $this->account->username, 'provider' => $this->account->provider,
            ] : null),
        ];
    }
}
