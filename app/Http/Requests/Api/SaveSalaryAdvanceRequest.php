<?php

namespace App\Http\Requests\Api;

use App\Models\SalaryAdvance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSalaryAdvanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('employees') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('put') ? 'required' : 'sometimes';

        return [
            'employeeId' => [$required, 'string', 'exists:employees,source_key'],
            'amount' => [$required, 'numeric', 'gt:0', 'max:99999999.99'],
            'requestDate' => [$required, 'date_format:Y-m-d'],
            'salaryMonth' => [$required, 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'status' => ['sometimes', 'string', Rule::in(SalaryAdvance::STATUSES)],
        ];
    }
}
