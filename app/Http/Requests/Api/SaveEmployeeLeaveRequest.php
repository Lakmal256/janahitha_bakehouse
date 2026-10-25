<?php

namespace App\Http\Requests\Api;

use App\Models\EmployeeLeave;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEmployeeLeaveRequest extends FormRequest
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
            'leaveType' => [$required, 'string', 'max:100'],
            'startDate' => [$required, 'date_format:Y-m-d'],
            'endDate' => [$required, 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::in(EmployeeLeave::STATUSES)],
        ];
    }
}
