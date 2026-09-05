<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('branches', 'code')->where('tenant_id', $tenantId),
            ],

            'address_line_1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name)
                ? trim($this->name)
                : $this->name,

            'code' => is_string($this->code)
                ? strtoupper(trim($this->code))
                : $this->code,

            'country_code' => is_string($this->country_code)
                ? strtoupper(trim($this->country_code))
                : $this->country_code,
        ]);
    }
}
