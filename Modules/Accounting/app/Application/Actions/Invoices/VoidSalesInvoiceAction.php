<?php

namespace Modules\Accounting\Application\Actions\Invoices;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\SalesInvoice;

final class VoidSalesInvoiceAction
{
    public function execute(
        SalesInvoice $invoice,
        CurrentTenant $currentTenant,
    ): SalesInvoice {
        return DB::transaction(function () use ($invoice, $currentTenant) {
            $invoice = SalesInvoice::query()
                ->where('tenant_id', $currentTenant->id())
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            abort_if(
                $invoice->status === 'void',
                422,
                'Sales invoice is already void.'
            );

            abort_if(
                $invoice->status === 'draft',
                422,
                'Draft invoices should be deleted or edited, not voided.'
            );

            abort_if(
                $invoice->status === 'posted',
                422,
                'Posted sales invoices must be reversed through the accounting reversal workflow.'
            );

            $invoice->update([
                'status' => 'void',
            ]);

            return $invoice->fresh();
        });
    }
}
