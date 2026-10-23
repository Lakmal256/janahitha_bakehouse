<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveCatalogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('items');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:1000'],
            'items.*.id' => ['required', 'string', 'max:191', 'distinct'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ];
    }
}
