<?php

namespace App\Http\Requests\Super;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunMigrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('super') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::in(['central', 'tenant'])],
        ];
    }
}
