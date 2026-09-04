<?php

namespace Modules\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.update') ?? false;
    }

    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9][A-Za-z0-9 _-]*$/',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'web'))
                    ->ignore($role?->id),
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where(
                    fn ($query) => $query->where('guard_name', 'web'),
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The role name may only contain letters, numbers, spaces, underscores, and hyphens.',
            'permissions.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }
}
