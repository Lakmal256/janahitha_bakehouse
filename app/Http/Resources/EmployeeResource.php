<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    /**
     * Transform the employee into the browser app's `employees` document.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source_key,
            'name' => $this->name,
            'phone' => $this->phone,
            'location' => $this->location,
            'createdAt' => $this->created_at?->getTimestampMs(),
        ];
    }
}
