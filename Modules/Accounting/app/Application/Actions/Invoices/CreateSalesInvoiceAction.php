<?php

namespace Modules\Accounting\Application\Actions\Invoices;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\Invoices\CreateSalesInvoiceData;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Accounting\Models\SalesInvoiceLine;

final class CreateSalesInvoiceAction
{
    public function execute(
        CreateSalesInvoiceData $data,
        CurrentTenant $currentTenant,
    ): SalesInvoice {
        return DB::transaction(function () use ($data, $currentTenant) {
            $customer = Customer::query()
                ->where('tenant_id', $currentTenant->id())
                ->where('status', 'active')
                ->findOrFail($data->customerId);

            $subtotal = '0.000000';
            $discountTotal = '0.000000';
            $taxTotal = '0.000000';
            $grandTotal = '0.000000';

            $invoice = SalesInvoice::query()->create([
                'tenant_id' => $currentTenant->id(),
                'customer_id' => $customer->id,
                'invoice_number' => $data->invoiceNumber,
                'invoice_date' => $data->invoiceDate,
                'due_date' => $data->dueDate,
                'currency' => $data->currency,
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'status' => 'draft',
                'reference' => $data->reference,
                'notes' => $data->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($data->lines as $line) {
                $account = Account::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->findOrFail($line->revenueAccountId);

                $quantity = number_format((float) $line->quantity, 4, '.', '');
                $unitPrice = number_format((float) $line->unitPrice, 4, '.', '');
                $discountAmount = number_format((float) ($line->discountAmount ?? 0), 4, '.', '');
                $taxRate = number_format((float) ($line->taxRate ?? 0), 2, '.', '');

                $quantitySubtotal = bcmul($quantity, $unitPrice, 4);
                $lineSubtotal = bcsub($quantitySubtotal, $discountAmount, 4);

                if (bccomp($taxRate, '0.00', 2) > 0) {
                    $taxAmount = bcmul($lineSubtotal, bcdiv($taxRate, '100.00', 4), 4);
                } else {
                    $taxAmount = number_format((float) ($line->taxAmount ?? 0), 4, '.', '');
                }

                $lineTotal = bcadd($lineSubtotal, $taxAmount, 4);

                SalesInvoiceLine::query()->create([
                    'tenant_id' => $currentTenant->id(),
                    'sales_invoice_id' => $invoice->id,
                    'line_number' => $line->lineNumber,
                    'description' => $line->description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineTotal,
                    'revenue_account_id' => $account->id,
                ]);

                $subtotal = bcadd($subtotal, $lineSubtotal, 4);
                $discountTotal = bcadd($discountTotal, $discountAmount, 4);
                $taxTotal = bcadd($taxTotal, $taxAmount, 4);
                $grandTotal = bcadd($grandTotal, $lineTotal, 4);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            return $invoice->fresh([
                'customer',
                'lines.revenueAccount',
            ]);
        });
    }
}
