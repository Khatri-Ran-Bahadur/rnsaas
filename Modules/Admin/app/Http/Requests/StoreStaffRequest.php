<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->email)),
            'employee_code' => strtoupper(trim((string) $this->employee_code)),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email:rfc',
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
                Rule::unique('tenant_staff', 'employee_code')->where('tenant_id', $tenantId),
            ],

            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],

            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('tenant_id', $tenantId),
            ],

            'designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')->where('tenant_id', $tenantId),
            ],

            'joining_date' => [
                'nullable',
                'date',
            ],
        ];
    }
}
