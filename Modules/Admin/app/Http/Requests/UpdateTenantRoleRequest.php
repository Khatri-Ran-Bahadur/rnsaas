<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Domain\Constants\OrganizationPermissions;
use Modules\Tenancy\Models\TenantRole;

class UpdateTenantRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();
        /** @var TenantRole|null $role */
        $role = $this->route('role');
        $roleId = $role instanceof TenantRole ? $role->id : $role;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tenant_roles', 'name')
                    ->where('tenant_id', $tenantId)
                    ->ignore($roleId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'permissions' => [
                'required',
                'array',
                'min:1',
            ],
            'permissions.*' => [
                'string',
                Rule::in(OrganizationPermissions::all()),
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A role with this name already exists in this organization.',
            'permissions.required' => 'Please select at least one permission for this role.',
            'permissions.min' => 'Please select at least one permission for this role.',
        ];
    }
}
