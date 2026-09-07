<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Models\TenantRole;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('tenant_roles', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true),
                function (string $attribute, mixed $value, \Closure $fail) use ($tenantId): void {
                    $role = TenantRole::find($value);
                    if ($role && $role->slug === 'admin') {
                        $authService = app(OrganizationAuthorizationService::class);
                        if (! $authService->isAdmin($this->user(), $tenantId)) {
                            $fail('Only an organization administrator can assign the Admin role.');
                        }
                    }
                },
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['active', 'suspended', 'revoked']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'Please select an organization role.',
            'role_id.exists' => 'The selected role is invalid or inactive.',
        ];
    }
}
