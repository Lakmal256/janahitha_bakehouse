<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('orders');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'routeId' => ['sometimes', 'string', 'max:191'],
            'shops' => ['required', 'array', 'min:1', 'max:500'],
            'shops.*' => ['array'],
            'shops.*.q' => ['sometimes', 'array', 'max:500'],
            'shops.*.q.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'shops.*.at' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
