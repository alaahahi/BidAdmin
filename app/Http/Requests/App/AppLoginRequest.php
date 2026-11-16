<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class AppLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:8'],
            'device_name' => ['required', 'string', 'max:80'],
            'platform' => ['required', 'string', 'max:40'],
            'app_version' => ['required', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }
}
