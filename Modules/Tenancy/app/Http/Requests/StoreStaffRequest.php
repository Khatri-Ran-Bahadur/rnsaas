<?php

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'employee_code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
            ],

            'designation' => [
                'required',
                'string',
                'max:100',
            ],

            'base_salary' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'joining_date' => [
                'nullable',
                'date',
            ],

            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name)
                ? trim($this->name)
                : $this->name,

            'email' => is_string($this->email)
                ? strtolower(trim($this->email))
                : $this->email,

            'employee_code' => is_string($this->employee_code)
                ? strtoupper(trim($this->employee_code))
                : $this->employee_code,

            'designation' => is_string($this->designation)
                ? trim($this->designation)
                : $this->designation,
        ]);
    }
}
