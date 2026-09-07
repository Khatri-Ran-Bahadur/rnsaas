<?php

namespace Modules\Admin\Http\Requests;

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\TenantMembership;
use Modules\Tenancy\Models\TenantRole;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->email ? strtolower(trim((string) $this->email)) : null,
            'name' => $this->name ? trim((string) $this->name) : null,
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($tenantId): void {
                    $user = User::query()->where('email', $value)->first();
                    if ($user) {
                        $membership = TenantMembership::query()
                            ->where('tenant_id', $tenantId)
                            ->where('user_id', $user->id)
                            ->first();

                        if ($membership) {
                            if ($membership->status === TenantMembershipStatus::Active) {
                                $fail('This user is already an active member of this organization.');
                            } elseif ($membership->status === TenantMembershipStatus::Invited) {
                                $fail('An invitation has already been sent to this user.');
                            }
                        }
                    }
                },
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $userExists = User::query()->where('email', $this->email)->exists();
                    if (! $userExists && empty($value)) {
                        $fail('Name is required for new users.');
                    }
                },
            ],
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
                Rule::in(['active', 'invited']),
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
