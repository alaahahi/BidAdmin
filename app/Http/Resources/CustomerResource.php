<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Customer */
class CustomerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'login_code' => $this->login_code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_code' => $this->phone_code,
            'address' => $this->address,
            'bid_limit' => $this->bid_limit,
            'car_limit' => $this->car_limit,
            'credit_limit' => $this->credit_limit,
            'can_bid' => $this->can_bid,
            'tags' => $this->tags,
        ];
    }
}
