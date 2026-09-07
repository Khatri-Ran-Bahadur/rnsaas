<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\HRM\Application\DTOs\UpdateWorkScheduleData;

class UpdateWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('work_schedules.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'timezone' => [
                'required',
                'string',
                'timezone',
            ],

            'default_start_time' => [
                'required',
                'date_format:H:i',
            ],

            'default_end_time' => [
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

            'is_active' => [
                'required',
                'boolean',
            ],

            'days' => [
                'required',
                'array',
                'size:7',
            ],

            'days.*.day_of_week' => [
                'required',
                'integer',
                'between:1,7',
                'distinct',
            ],

            'days.*.is_working_day' => [
                'required',
                'boolean',
            ],

            'days.*.start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'days.*.end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'days.*.break_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $days = $this->input('days', []);

            if (! is_array($days)) {
                return;
            }

            foreach ($days as $index => $day) {
                if (! is_array($day)) {
                    continue;
                }

                $isWorkingDay = filter_var(
                    $day['is_working_day'] ?? false,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                $startTime = $day['start_time'] ?? null;
                $endTime = $day['end_time'] ?? null;

                if ($isWorkingDay === true) {
                    if (blank($startTime)) {
                        $validator->errors()->add(
                            "days.$index.start_time",
                            'Start time is required for a working day.'
                        );
                    }

                    if (blank($endTime)) {
                        $validator->errors()->add(
                            "days.$index.end_time",
                            'End time is required for a working day.'
                        );
                    }
                }

                if ($isWorkingDay === false) {
                    if (filled($startTime)) {
                        $validator->errors()->add(
                            "days.$index.start_time",
                            'Start time must be empty for a non-working day.'
                        );
                    }

                    if (filled($endTime)) {
                        $validator->errors()->add(
                            "days.$index.end_time",
                            'End time must be empty for a non-working day.'
                        );
                    }
                }
            }
        });
    }

    public function toData(
        int $tenantId,
        string $publicId,
    ): UpdateWorkScheduleData {
        return new UpdateWorkScheduleData(
            tenantId: $tenantId,
            publicId: $publicId,
            name: $this->string('name')->toString(),
            description: $this->input('description'),
            timezone: $this->string('timezone')->toString(),
            defaultStartTime: $this->string('default_start_time')->toString(),
            defaultEndTime: $this->string('default_end_time')->toString(),
            breakMinutes: $this->integer('break_minutes'),
            lateGraceMinutes: $this->integer('late_grace_minutes'),
            earlyLeaveGraceMinutes: $this->integer('early_leave_grace_minutes'),
            days: collect($this->input('days', []))
                ->map(fn (array $day): array => [
                    'day_of_week' => (int) $day['day_of_week'],
                    'is_working_day' => (bool) $day['is_working_day'],
                    'start_time' => $day['start_time'] ?? null,
                    'end_time' => $day['end_time'] ?? null,
                    'break_minutes' => (int) $day['break_minutes'],
                ])
                ->values()
                ->all(),
        );
    }
}
