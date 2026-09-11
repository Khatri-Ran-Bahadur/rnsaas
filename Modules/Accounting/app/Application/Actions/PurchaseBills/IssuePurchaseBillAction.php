<?php

namespace Modules\Accounting\Application\Actions\PurchaseBills;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\PurchaseBill;

final class IssuePurchaseBillAction
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

            abort_unless(
                $bill->status->value === 'draft',
                422,
                'Only draft purchase bills can be issued.'
            );

            abort_if(
                $bill->lines()->count() === 0,
                422,
                'A purchase bill must contain at least one line.'
            );

            $bill->update([
                'status' => 'issued',
                'issued_by' => auth()->id(),
                'issued_at' => now(),
            ]);

            return $bill->fresh();
        });
    }
}
