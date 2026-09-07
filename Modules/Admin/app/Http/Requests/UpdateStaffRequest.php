<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Models\TenantStaff;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = $this->route('staff');
        if ($staff instanceof TenantStaff && $staff->tenant_id !== app(CurrentTenant::class)->id()) {
            abort(404);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_code' => strtoupper(trim((string) $this->employee_code)),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();
        $staff = $this->route('staff');
        $staffId = $staff instanceof TenantStaff ? $staff->id : $staff;

        return [
            'name' => [
                'required',
                'string',
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
                Rule::unique('tenant_staff', 'employee_code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($staffId),
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

            'employment_status' => [
                'required',
                'string',
                Rule::enum(EmploymentStatus::class),
            ],
        ];
    }
}
