<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', Rule::in(['login', 'lot_view', 'bid_placed', 'prebid', 'buy_now', 'watchlist_add', 'watchlist_remove', 'search'])],
            'events.*.account_id' => ['nullable', 'integer'],
            'events.*.lot_number' => ['nullable', 'string', 'max:40'],
            'events.*.vin' => ['nullable', 'string', 'max:40'],
            'events.*.title' => ['nullable', 'string', 'max:255'],
            'events.*.amount' => ['nullable', 'numeric', 'min:0'],
            'events.*.url' => ['nullable', 'string', 'max:500'],
            'events.*.occurred_at' => ['nullable', 'date'],
            'events.*.raw' => ['nullable'],
            'platform' => ['nullable', 'string', 'max:40'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'app' => ['nullable', 'string', 'max:40'],
        ];
    }
}
