<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\HRM\Application\DTOs\UpdateShiftData;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('shifts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],

            'break_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'late_grace_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'early_leave_grace_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'is_overnight' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            $isOvernight = $this->boolean('is_overnight');

            if (! $startTime || ! $endTime) {
                return;
            }

            if ($startTime === $endTime) {
                $validator->errors()->add(
                    'end_time',
                    'Start time and end time cannot be the same.'
                );

                return;
            }

            if (! $isOvernight && $endTime <= $startTime) {
                $validator->errors()->add(
                    'end_time',
                    'End time must be after start time for a non-overnight shift.'
                );
            }

            if ($isOvernight && $endTime > $startTime) {
                $validator->errors()->add(
                    'end_time',
                    'An overnight shift must cross midnight.'
                );
            }
        });
    }

    public function toData(
        int $tenantId,
        string $publicId,
    ): UpdateShiftData {
        return new UpdateShiftData(
            tenantId: $tenantId,
            publicId: $publicId,
            name: $this->string('name')->toString(),
            code: strtoupper(
                $this->string('code')->toString()
            ),
            description: $this->input('description'),
            startTime: $this->string('start_time')->toString(),
            endTime: $this->string('end_time')->toString(),
            breakMinutes: $this->integer('break_minutes'),
            lateGraceMinutes: $this->integer('late_grace_minutes'),
            earlyLeaveGraceMinutes: $this->integer('early_leave_grace_minutes'),
            isOvernight: $this->boolean('is_overnight'),
            isActive: $this->boolean('is_active'),
        );
    }
}
