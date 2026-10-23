<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The controller checks the
     * bill permissions that the specific change needs.
     */
    public function authorize(): bool
    {
        return true;
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
            'shops.*.lines' => ['sometimes', 'array', 'max:500'],
            'shops.*.lines.*.id' => ['required', 'string', 'max:191'],
            'shops.*.lines.*.name' => ['required', 'string', 'max:255'],
            'shops.*.lines.*.qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'shops.*.lines.*.ret' => ['nullable', 'integer', 'min:0', 'lte:shops.*.lines.*.qty'],
            'shops.*.lines.*.price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'shops.*.shopName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shops.*.at' => ['sometimes', 'nullable', 'numeric'],
            'shops.*.editedAt' => ['sometimes', 'nullable', 'numeric'],
            'shops.*.pay' => ['sometimes', 'array'],
            'shops.*.pay.method' => ['required_with:shops.*.pay', 'in:cash,credit,partial'],
            'shops.*.pay.paid' => ['sometimes', 'numeric', 'min:0', 'max:1000000000'],
            'shops.*.pay.at' => ['sometimes', 'nullable', 'numeric'],
            'shops.*.pay.log' => ['sometimes', 'array', 'max:500'],
            'shops.*.pay.log.*.kind' => ['required', 'in:cash,credit,partial,collect'],
            'shops.*.pay.log.*.amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'shops.*.pay.log.*.at' => ['required', 'numeric'],
        ];
    }
}
