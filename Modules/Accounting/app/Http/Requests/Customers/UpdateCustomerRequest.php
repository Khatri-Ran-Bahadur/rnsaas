<?php

namespace Modules\Accounting\Http\Requests\Customers;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        $customerId = $this->route('customer')?->id
            ?? $this->route('customer');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'tax_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'billing_address_line_1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'billing_address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'billing_city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'billing_state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'billing_postcode' => [
                'nullable',
                'string',
                'max:30',
            ],

            'billing_country' => [
                'nullable',
                'string',
                'size:2',
            ],

            'receivable_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounting_accounts', 'id')
                    ->where('tenant_id', $tenantId),
            ],

            'credit_limit' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payment_terms_days' => [
                'required',
                'integer',
                'min:0',
                'max:3650',
            ],
        ];
    }
}
