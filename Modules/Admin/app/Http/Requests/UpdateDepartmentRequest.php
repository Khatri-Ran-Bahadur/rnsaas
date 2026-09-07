<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Models\Department;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = $this->route('department');
        if ($department instanceof Department && $department->tenant_id !== app(CurrentTenant::class)->id()) {
            abort(404);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'code' => strtoupper(trim((string) $this->code)),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();
        $department = $this->route('department');
        $departmentId = $department instanceof Department ? $department->id : $department;

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
                Rule::unique('departments', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($departmentId),
            ],
        ];
    }
}
