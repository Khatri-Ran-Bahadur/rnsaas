<?php

namespace Modules\Accounting\Http\Requests\VendorPayments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'vendor_id' => [
                'required',
                'integer',
                Rule::exists('accounting_vendors', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active'),
            ],

            'payment_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounting_vendor_payments', 'payment_number')
                    ->where('tenant_id', $tenantId),
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'bank_account_id' => [
                'required',
                'integer',
                Rule::exists('accounting_accounts', 'id')
                    ->where('tenant_id', $tenantId),
            ],

            'payment_method' => [
                'required',
                'string',
                'max:50',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
