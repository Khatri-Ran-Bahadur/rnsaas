<?php

namespace Modules\HRM\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\HRM\Application\DTOs\UpdateOvertimeData;
use Modules\HRM\Domain\Enums\OvertimeType;

class UpdateOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('overtime.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'tenant_staff_id' => [
                'required',
                'integer',
                'exists:tenant_staff,id',
            ],

            'date' => [
                'required',
                'date',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],

            'type' => [
                'required',
                'string',
                'in:'.implode(
                    ',',
                    array_column(
                        OvertimeType::cases(),
                        'value'
                    )
                ),
            ],

            'rate_multiplier' => [
                'required',
                'numeric',
                'min:0.01',
                'max:10',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {
                $start = $this->input('start_time');
                $end = $this->input('end_time');

                if (! $start || ! $end) {
                    return;
                }

                if ($start === $end) {
                    $validator->errors()->add(
                        'end_time',
                        'Start time and end time cannot be the same.'
                    );
                }
            }
        );
    }

    public function toData(
        int $updatedBy
    ): UpdateOvertimeData {
        $start = Carbon::createFromFormat(
            'H:i',
            $this->string('start_time')->toString()
        );

        $end = Carbon::createFromFormat(
            'H:i',
            $this->string('end_time')->toString()
        );

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return new UpdateOvertimeData(
            tenantStaffId: $this->integer('tenant_staff_id'),
            date: $this->date('date')->format('Y-m-d'),
            startTime: $this->string('start_time')->toString(),
            endTime: $this->string('end_time')->toString(),
            totalMinutes: $start->diffInMinutes($end),
            type: OvertimeType::from(
                $this->string('type')->toString()
            ),
            rateMultiplier: $this->float('rate_multiplier'),
            reason: $this->input('reason'),
            isActive: $this->boolean('is_active'),
            updatedBy: $updatedBy,
        );
    }
}
