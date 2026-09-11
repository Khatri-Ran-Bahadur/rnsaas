<?php

namespace Modules\Accounting\Application\Actions\Invoices;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\SalesInvoice;

final class IssueSalesInvoiceAction
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

            abort_unless(
                $invoice->status === 'draft',
                422,
                'Only draft sales invoices can be issued.'
            );

            abort_if(
                $invoice->lines()->count() === 0,
                422,
                'A sales invoice must contain at least one line.'
            );

            $invoice->update([
                'status' => 'issued',
            ]);

            return $invoice->fresh();
        });
    }
}
