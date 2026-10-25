<?php

namespace App\Http\Resources;

use App\Models\SalaryAdvance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SalaryAdvance
 */
class SalaryAdvanceResource extends JsonResource
{
    /**
     * Transform the salary advance into the browser app's `salary-advances` document.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source_key,
            'employeeId' => $this->employee?->source_key,
            'employeeName' => $this->employee?->name ?? '',
            'amount' => (float) $this->amount,
            'requestDate' => $this->request_date?->format('Y-m-d'),
            'salaryMonth' => $this->salary_month,
            'reason' => $this->reason ?? '',
            'status' => $this->status,
            'createdAt' => $this->created_at?->getTimestampMs(),
        ];
    }
}
