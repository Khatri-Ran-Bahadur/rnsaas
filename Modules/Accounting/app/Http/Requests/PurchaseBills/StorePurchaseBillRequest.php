<?php

namespace Modules\Accounting\Http\Requests\PurchaseBills;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();
        $billId = $this->route('bill')?->id;

        return [
            'vendor_id' => [
                'required',
                'integer',
                Rule::exists('accounting_vendors', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active'),
            ],

            'bill_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounting_purchase_bills', 'bill_number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($billId),
            ],

            'bill_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'required',
                'date',
                'after_or_equal:bill_date',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
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

            'lines' => [
                'required',
                'array',
                'min:1',
            ],

            'lines.*.line_number' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'lines.*.description' => [
                'required',
                'string',
                'max:500',
            ],

            'lines.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'lines.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'lines.*.discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'lines.*.tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'lines.*.tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'lines.*.debit_account_id' => [
                'required',
                'integer',
                Rule::exists('accounting_accounts', 'id')
                    ->where('tenant_id', $tenantId),
            ],

            'lines.*.tax_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounting_accounts', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ];
    }
}
