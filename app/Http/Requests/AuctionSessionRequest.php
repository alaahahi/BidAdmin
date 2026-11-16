<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuctionSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cookies' => ['nullable'],
            'local_storage' => ['nullable'],
            'user_agent' => ['nullable', 'string', 'max:500'],
            'captured_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
