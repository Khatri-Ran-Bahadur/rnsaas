<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Application\DTOs\UpdateCompanyProfileData;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => strtolower(trim((string) $this->slug)),
            'country_code' => $this->country_code ? strtoupper(trim((string) $this->country_code)) : null,
            'currency' => strtoupper(trim((string) $this->currency)),
        ]);
    }

    public function rules(): array
    {
        $tenant = app(CurrentTenant::class)->get();

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
                Rule::unique('tenants', 'slug')->ignore($tenant->id),
            ],

            'industry' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],

            'timezone' => [
                'required',
                'string',
                'timezone',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'locale' => [
                'required',
                'string',
                'max:10',
            ],

            'email' => [
                'nullable',
                'email:rfc',
                'max:150',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'website' => [
                'nullable',
                'string',
                'max:255',
            ],

            'tax_id' => [
                'nullable',
                'string',
                'max:50',
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:50',
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

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function toData(): UpdateCompanyProfileData
    {
        return new UpdateCompanyProfileData(
            name: $this->string('name')->toString(),
            slug: $this->string('slug')->toString(),
            industry: $this->input('industry'),
            countryCode: $this->input('country_code'),
            timezone: $this->string('timezone')->toString(),
            locale: $this->string('locale')->toString(),
            currency: $this->string('currency')->toString(),
            settings: [
                'email' => $this->input('email'),
                'phone' => $this->input('phone'),
                'website' => $this->input('website'),
                'tax_id' => $this->input('tax_id'),
                'registration_number' => $this->input('registration_number'),
                'address_line_1' => $this->input('address_line_1'),
                'address_line_2' => $this->input('address_line_2'),
                'city' => $this->input('city'),
                'state' => $this->input('state'),
                'postal_code' => $this->input('postal_code'),
                'description' => $this->input('description'),
            ],
        );
    }
}
