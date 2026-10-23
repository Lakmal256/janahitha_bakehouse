<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportBrowserDataRequest extends FormRequest
{
    /**
     * Only the owner may import a browser backup, because it can add accounts.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()->is_owner;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.config' => ['sometimes', 'array'],
            'data.routes' => ['sometimes', 'array'],
            'data.shops' => ['sometimes', 'array'],
            'data.users' => ['sometimes', 'array'],
            'data.days' => ['sometimes', 'array'],
            'data.bills' => ['sometimes', 'array'],
        ];
    }
}
