<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
