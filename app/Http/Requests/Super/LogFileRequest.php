<?php

namespace App\Http\Requests\Super;

use Illuminate\Foundation\Http\FormRequest;

class LogFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('super') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._-]+\.log$/'],
            'lines' => ['nullable', 'integer', 'min:50', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('file') && is_string($this->input('file'))) {
            $this->merge(['file' => basename(str_replace('\\', '/', $this->input('file')))]);
        }
    }
}
