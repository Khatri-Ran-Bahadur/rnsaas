<?php

namespace Modules\Accounting\Application\Actions\PurchaseBills;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\PurchaseBills\CreatePurchaseBillData;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\PurchaseBillLine;
use Modules\Accounting\Models\Vendor;

final class UpdatePurchaseBillAction
{
    public function execute(
        PurchaseBill $bill,
        CreatePurchaseBillData $data,
        CurrentTenant $currentTenant,
    ): PurchaseBill {
        abort_unless(
            $bill->tenant_id === $currentTenant->id(),
            404
        );

        abort_unless(
            $bill->status->value === 'draft',
            422,
            'Only draft purchase bills can be edited.'
        );

        return DB::transaction(function () use (
            $bill,
            $data,
            $currentTenant,
        ) {
            $vendor = Vendor::query()
                ->where('tenant_id', $currentTenant->id())
                ->where('status', 'active')
                ->findOrFail($data->vendorId);

            $bill->lines()->delete();

            $bill->update([
                'vendor_id' => $vendor->id,
                'bill_number' => $data->billNumber,
                'bill_date' => $data->billDate,
                'due_date' => $data->dueDate,
                'currency' => $data->currency,
                'reference' => $data->reference,
                'notes' => $data->notes,
            ]);

            $subtotal = '0.000000';
            $discountTotal = '0.000000';
            $taxTotal = '0.000000';
            $grandTotal = '0.000000';

            foreach ($data->lines as $line) {
                $account = Account::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->findOrFail($line->debitAccountId);

                if ($line->taxAccountId !== null) {
                    Account::query()
                        ->where('tenant_id', $currentTenant->id())
                        ->findOrFail($line->taxAccountId);
                }

                $quantity = number_format((float) $line->quantity, 6, '.', '');
                $unitPrice = number_format((float) $line->unitPrice, 6, '.', '');
                $discountAmount = number_format((float) ($line->discountAmount ?? 0), 6, '.', '');
                $taxRate = number_format((float) ($line->taxRate ?? 0), 6, '.', '');

                $quantitySubtotal = bcmul($quantity, $unitPrice, 6);
                $lineSubtotal = bcsub($quantitySubtotal, $discountAmount, 6);

                if (bccomp($taxRate, '0.000000', 6) > 0) {
                    $taxAmount = bcmul($lineSubtotal, bcdiv($taxRate, '100.000000', 6), 6);
                } else {
                    $taxAmount = number_format((float) ($line->taxAmount ?? 0), 6, '.', '');
                }

                if (bccomp($taxAmount, '0.000000', 6) > 0) {
                    abort_if(
                        $line->taxAccountId === null,
                        422,
                        'A tax account is required for taxable purchase bill lines.'
                    );
                }

                $lineTotal = bcadd($lineSubtotal, $taxAmount, 6);

                PurchaseBillLine::query()->create([
                    'purchase_bill_id' => $bill->id,
                    'line_number' => $line->lineNumber,
                    'description' => $line->description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineTotal,
                    'debit_account_id' => $account->id,
                    'tax_account_id' => $line->taxAccountId,
                ]);

                $subtotal = bcadd($subtotal, $lineSubtotal, 6);
                $discountTotal = bcadd($discountTotal, $discountAmount, 6);
                $taxTotal = bcadd($taxTotal, $taxAmount, 6);
                $grandTotal = bcadd($grandTotal, $lineTotal, 6);
            }

            $bill->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            return $bill->fresh([
                'vendor',
                'lines',
            ]);
        });
    }
}
