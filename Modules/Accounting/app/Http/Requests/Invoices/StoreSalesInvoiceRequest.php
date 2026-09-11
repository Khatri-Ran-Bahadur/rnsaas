<?php

namespace Modules\Accounting\Http\Requests\Invoices;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('accounting_customers', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active'),
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounting_sales_invoices', 'invoice_number')
                    ->where('tenant_id', $tenantId),
            ],

            'invoice_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'required',
                'date',
                'after_or_equal:invoice_date',
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

            'lines.*.subtotal' => [
                'required',
                'numeric',
                'min:0',
            ],

            'lines.*.total' => [
                'required',
                'numeric',
                'min:0',
            ],

            'lines.*.revenue_account_id' => [
                'required',
                'integer',
                Rule::exists('accounting_accounts', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ];
    }
}
