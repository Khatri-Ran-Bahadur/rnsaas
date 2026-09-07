<?php

namespace Modules\HRM\Http\Requests\Attendances;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\HRM\Application\DTOs\Attendances\CreateAttendanceData;
use Modules\HRM\Domain\Enums\AttendanceSource;
use Modules\HRM\Domain\Enums\AttendanceStatus;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_staff_id' => [
                'required',
                'integer',
            ],

            'attendance_date' => [
                'required',
                'date',
            ],

            'check_in' => [
                'nullable',
                'date_format:H:i',
            ],

            'check_out' => [
                'nullable',
                'date_format:H:i',
            ],

            'late_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'early_leave_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'overtime_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'status' => [
                'required',
                new Enum(AttendanceStatus::class),
            ],

            'source' => [
                'required',
                new Enum(AttendanceSource::class),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function toData(
        int $tenantId,
        int $createdBy,
    ): CreateAttendanceData {
        return new CreateAttendanceData(
            tenantId: $tenantId,
            tenantStaffId: $this->integer('tenant_staff_id'),
            attendanceDate: $this->string('attendance_date')->toString(),
            checkIn: $this->input('check_in'),
            checkOut: $this->input('check_out'),
            lateMinutes: $this->integer('late_minutes'),
            earlyLeaveMinutes: $this->integer('early_leave_minutes'),
            overtimeMinutes: $this->integer('overtime_minutes'),
            status: AttendanceStatus::from(
                $this->string('status')->toString(),
            ),
            source: AttendanceSource::from(
                $this->string('source')->toString(),
            ),
            notes: $this->input('notes'),
            createdBy: $createdBy,
        );
    }
}
