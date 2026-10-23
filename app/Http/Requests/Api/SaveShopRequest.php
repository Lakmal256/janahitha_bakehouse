<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('shops');
    }

    /**
     * Get the validation rules that apply to the request. A PUT saves the whole shop; a PATCH only the given fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('put') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'routeId' => [$required, 'string', Rule::exists('routes', 'source_key')],
            'items' => [$this->isMethod('put') ? 'present' : 'sometimes', 'array', 'max:500'],
            'items.*' => ['string', 'max:191', 'distinct', Rule::exists('items', 'source_key')],
            'prices' => ['sometimes', 'array', 'max:500'],
            'prices.*' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
