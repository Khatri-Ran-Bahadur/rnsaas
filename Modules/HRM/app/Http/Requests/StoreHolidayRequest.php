<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\HRM\Application\DTOs\CreateHolidayData;
use Modules\HRM\Domain\Enums\HolidayType;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('holidays.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'type' => [
                'required',
                'string',
                'in:'.implode(',', array_column(HolidayType::cases(), 'value')),
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'is_recurring' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $this->boolean('is_recurring')
                && $this->filled('end_date')
            ) {
                $validator->errors()->add(
                    'end_date',
                    'Recurring holidays cannot have an end date.'
                );
            }
        });
    }

    public function toData(int $tenantId): CreateHolidayData
    {
        return new CreateHolidayData(
            tenantId: $tenantId,
            name: $this->string('name')->toString(),
            startDate: $this->date('start_date')->format('Y-m-d'),
            endDate: $this->filled('end_date')
                ? $this->date('end_date')->format('Y-m-d')
                : null,
            type: HolidayType::from(
                $this->string('type')->toString()
            ),
            description: $this->input('description'),
            isRecurring: $this->boolean('is_recurring'),
        );
    }
}
