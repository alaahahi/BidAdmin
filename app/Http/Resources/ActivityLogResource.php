<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ActivityLog */
class ActivityLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'customer_id' => $this->customer_id,
            'account_id' => $this->account_id,
            'user_id' => $this->user_id,
            'provider' => $this->provider,
            'target' => $this->target,
            'description' => $this->description,
            'amount' => $this->amount,
            'ip_address' => $this->ip_address,
            'app' => $this->app,
            'source' => $this->source,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id, 'name' => $this->customer->name,
            ] : null),
            'account' => $this->whenLoaded('account', fn () => $this->account ? [
                'id' => $this->account->id, 'username' => $this->account->username,
            ] : null),
        ];
    }
}
