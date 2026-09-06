<?php

namespace Modules\Admin\Http\Requests;

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantStaff;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email ? strtolower(trim((string) $this->email)) : null,
            'employee_code' => strtoupper(trim((string) $this->employee_code)),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        $rules = [
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

            'employment_status' => [
                'nullable',
                'string',
                Rule::enum(EmploymentStatus::class),
            ],
        ];

        if ($this->filled('user_id')) {
            $rules['user_id'] = [
                'required',
                'integer',
                Rule::exists('tenant_user', 'user_id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', TenantMembershipStatus::Active->value),
                Rule::unique('tenant_staff', 'user_id')
                    ->where('tenant_id', $tenantId),
            ];
            $rules['name'] = ['nullable', 'string', 'max:255'];
            $rules['email'] = ['nullable', 'email:rfc', 'max:255'];
            $rules['phone'] = ['nullable', 'string', 'max:30'];
        } else {
            $rules['name'] = [
                'required',
                'string',
                'max:255',
            ];
            $rules['email'] = [
                'required',
                'email:rfc',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($tenantId): void {
                    $existingUser = User::query()->where('email', $value)->first();
                    if ($existingUser !== null && TenantStaff::query()->where('tenant_id', $tenantId)->where('user_id', $existingUser->id)->exists()) {
                        $fail('This user is already registered as a staff member in this organization.');
                    }
                },
            ];
            $rules['phone'] = [
                'nullable',
                'string',
                'max:30',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'This organization member is already registered as a staff member.',
            'user_id.exists' => 'The selected member must have an active membership in this organization.',
            'employee_code.unique' => 'This employee code is already assigned to another staff member in this organization.',
        ];
    }
}
