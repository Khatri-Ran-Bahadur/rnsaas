<?php

namespace Modules\Accounting\Application\Actions\Payments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\CustomerPayment;
use Modules\Accounting\Models\CustomerPaymentAllocation;
use Modules\Accounting\Models\SalesInvoice;

final class AllocateCustomerPaymentAction
{
    public function execute(
        CustomerPayment $payment,
        array $allocations,
        CurrentTenant $currentTenant,
    ): CustomerPayment {
        return DB::transaction(function () use (
            $payment,
            $allocations,
            $currentTenant,
        ) {
            $payment = CustomerPayment::query()
                ->where('tenant_id', $currentTenant->id())
                ->lockForUpdate()
                ->findOrFail($payment->id);

            abort_if(
                $payment->status !== 'draft',
                422,
                'Only draft payments can be allocated.'
            );

            $totalAllocated = '0.000000';

            foreach ($allocations as $allocation) {
                $invoice = SalesInvoice::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->where('customer_id', $payment->customer_id)
                    ->lockForUpdate()
                    ->findOrFail($allocation['invoice_id']);

                abort_if(
                    ! in_array($invoice->status, ['issued', 'posted'], true),
                    422,
                    "Invoice {$invoice->invoice_number} cannot receive payment."
                );

                $amount = number_format(
                    (float) $allocation['amount'],
                    6,
                    '.',
                    ''
                );

                $alreadyAllocated = CustomerPaymentAllocation::query()
                    ->where('sales_invoice_id', $invoice->id)
                    ->sum('allocated_amount');

                $outstanding = bcsub(
                    (string) $invoice->grand_total,
                    (string) $alreadyAllocated,
                    6
                );

                abort_if(
                    bccomp($amount, $outstanding, 6) > 0,
                    422,
                    "Allocation exceeds the outstanding amount for invoice {$invoice->invoice_number}."
                );

                $existing = CustomerPaymentAllocation::query()
                    ->where('customer_payment_id', $payment->id)
                    ->where('sales_invoice_id', $invoice->id)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'allocated_amount' => $amount,
                    ]);
                } else {
                    CustomerPaymentAllocation::query()->create([
                        'customer_payment_id' => $payment->id,
                        'sales_invoice_id' => $invoice->id,
                        'allocated_amount' => $amount,
                    ]);
                }

                $totalAllocated = bcadd(
                    $totalAllocated,
                    $amount,
                    6
                );
            }

            abort_if(
                bccomp(
                    $totalAllocated,
                    (string) $payment->amount,
                    6
                ) > 0,
                422,
                'Total allocation cannot exceed the payment amount.'
            );

            return $payment->fresh([
                'customer',
                'allocations.invoice',
                'attachments',
            ]);
        });
    }
}
