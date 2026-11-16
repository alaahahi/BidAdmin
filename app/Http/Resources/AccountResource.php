<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Account */
class AccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'region' => $this->region,
            'type' => $this->type,
            'category' => $this->category,
            'username' => $this->username,
            'company' => $this->company,
            'account_number' => $this->account_number,
            'external_id' => $this->external_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'balance_due' => $this->balance_due,
            'status' => $this->status,
            'action_status' => $this->action_status,
            'tags' => $this->tags,
            'membership_plan' => $this->membership_plan,
            'next_billing_date' => $this->next_billing_date?->toDateString(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'customers' => $this->whenLoaded('customers', fn () => $this->customers->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'code' => $c->code,
            ])),
            'brokers' => $this->whenLoaded('brokers', fn () => $this->brokers->map(fn ($b) => [
                'id' => $b->id, 'name' => $b->name,
            ])),
        ];
    }
}
