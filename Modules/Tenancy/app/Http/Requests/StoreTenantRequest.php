<?php

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Application\DTOs\CreateTenantData;
use Modules\Tenancy\Domain\Enums\TenantStatus;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'slug' => [
                'required',
                'string',
                'max:150',
                'alpha_dash',
                Rule::unique('tenants', 'slug'),
            ],

            'industry' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'sometimes',
                Rule::enum(TenantStatus::class),
            ],

            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],

            'timezone' => [
                'sometimes',
                'string',
                'timezone',
            ],

            'locale' => [
                'sometimes',
                'string',
                'max:10',
            ],

            'currency' => [
                'sometimes',
                'string',
                'size:3',
            ],

            'settings' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function toData(): CreateTenantData
    {
        $status = $this->validated('status');

        return new CreateTenantData(
            name: (string) $this->validated('name'),
            slug: (string) $this->validated('slug'),
            industry: $this->validated('industry'),
            status: $status instanceof TenantStatus
                ? $status
                : ($status ? TenantStatus::from($status) : TenantStatus::Pending),
            countryCode: $this->validated('country_code'),
            timezone: (string) ($this->validated('timezone') ?? 'UTC'),
            locale: (string) ($this->validated('locale') ?? 'en'),
            currency: (string) ($this->validated('currency') ?? 'USD'),
            settings: (array) ($this->validated('settings') ?? []),
        );
    }
}
