<?php

namespace Modules\HRM\Http\Requests\Leaves;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\HRM\Application\DTOs\Leaves\CreateLeaveData;
use Modules\HRM\Domain\Enums\LeaveType;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('leave.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_staff_id' => [
                'required',
                'integer',
            ],

            'leave_type' => [
                'required',
                new Enum(LeaveType::class),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function toData(
        int $tenantId,
        int $createdBy,
    ): CreateLeaveData {
        return new CreateLeaveData(
            tenantId: $tenantId,
            tenantStaffId: $this->integer('tenant_staff_id'),
            leaveType: LeaveType::from(
                $this->string('leave_type')->toString(),
            ),
            startDate: $this->string('start_date')->toString(),
            endDate: $this->string('end_date')->toString(),
            reason: $this->input('reason'),
            createdBy: $createdBy,
        );
    }
}
