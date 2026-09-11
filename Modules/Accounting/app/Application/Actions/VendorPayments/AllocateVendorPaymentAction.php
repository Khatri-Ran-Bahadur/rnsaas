<?php

namespace Modules\Accounting\Application\Actions\VendorPayments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\VendorPayment;
use Modules\Accounting\Models\VendorPaymentAllocation;

final class AllocateVendorPaymentAction
{
    public function execute(
        VendorPayment $payment,
        array $allocations,
        CurrentTenant $currentTenant,
    ): VendorPayment {
        return DB::transaction(function () use (
            $payment,
            $allocations,
            $currentTenant,
        ) {
            $payment = VendorPayment::query()
                ->where('tenant_id', $currentTenant->id())
                ->lockForUpdate()
                ->findOrFail($payment->id);

            abort_unless(
                $payment->status->value === 'draft',
                422,
                'Only draft payments can be allocated.'
            );

            $processedBillIds = [];
            $totalAllocated = '0.000000';

            foreach ($allocations as $allocation) {
                $bill = PurchaseBill::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->where('vendor_id', $payment->vendor_id)
                    ->whereIn('status', ['issued', 'posted'])
                    ->lockForUpdate()
                    ->findOrFail($allocation['purchase_bill_id']);

                $rawAmount = $allocation['amount'] ?? null;
                abort_if(
                    $rawAmount === null || ! is_numeric($rawAmount),
                    422,
                    'Allocation amount must be numeric.'
                );

                $amount = number_format(
                    (float) $rawAmount,
                    6,
                    '.',
                    ''
                );

                abort_unless(
                    bccomp($amount, '0.000000', 6) > 0,
                    422,
                    'Allocation amount must be greater than zero.'
                );

                $alreadyAllocated = VendorPaymentAllocation::query()
                    ->where('purchase_bill_id', $bill->id)
                    ->where('vendor_payment_id', '!=', $payment->id)
                    ->sum('allocated_amount');

                $outstanding = bcsub(
                    (string) $bill->grand_total,
                    (string) $alreadyAllocated,
                    6
                );

                abort_if(
                    bccomp($amount, $outstanding, 6) > 0,
                    422,
                    "Allocation exceeds the outstanding amount for bill {$bill->bill_number}."
                );

                VendorPaymentAllocation::query()->updateOrCreate(
                    [
                        'vendor_payment_id' => $payment->id,
                        'purchase_bill_id' => $bill->id,
                    ],
                    [
                        'tenant_id' => $currentTenant->id(),
                        'allocated_amount' => $amount,
                    ]
                );

                $processedBillIds[] = $bill->id;
                $totalAllocated = bcadd(
                    $totalAllocated,
                    $amount,
                    6
                );
            }

            $otherAllocated = VendorPaymentAllocation::query()
                ->where('vendor_payment_id', $payment->id)
                ->whereNotIn('purchase_bill_id', $processedBillIds)
                ->sum('allocated_amount');

            $allPaymentAllocations = bcadd($totalAllocated, (string) $otherAllocated, 6);

            abort_if(
                bccomp(
                    $allPaymentAllocations,
                    (string) $payment->amount,
                    6
                ) > 0,
                422,
                'Total allocation cannot exceed the payment amount.'
            );

            return $payment->fresh([
                'vendor',
                'allocations.purchaseBill',
                'attachments',
            ]);
        });
    }
}
