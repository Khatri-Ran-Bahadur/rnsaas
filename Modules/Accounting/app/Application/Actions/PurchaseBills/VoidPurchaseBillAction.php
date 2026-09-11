<?php

namespace Modules\Accounting\Application\Actions\PurchaseBills;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\PurchaseBill;

final class VoidPurchaseBillAction
{
    public function execute(
        PurchaseBill $bill,
        CurrentTenant $currentTenant,
    ): PurchaseBill {
        return DB::transaction(function () use ($bill, $currentTenant) {
            $bill = PurchaseBill::query()
                ->where('tenant_id', $currentTenant->id())
                ->lockForUpdate()
                ->findOrFail($bill->id);

            abort_if(
                $bill->status->value === 'void',
                422,
                'Purchase bill is already void.'
            );

            abort_if(
                $bill->status->value === 'draft',
                422,
                'Draft bills should be deleted or edited, not voided.'
            );

            /*
             * Posted bills should later use the Journal Engine's
             * reversal workflow instead of simply changing status.
             *
             * This prevents accounting history from being destroyed.
             */

            abort_if(
                $bill->status->value === 'posted',
                422,
                'Posted purchase bills must be reversed through the accounting reversal workflow.'
            );

            $bill->update([
                'status' => 'void',
                'voided_by' => auth()->id(),
                'voided_at' => now(),
            ]);

            return $bill->fresh();
        });
    }
}
