<?php

namespace Modules\Accounting\Application\Actions\Invoices;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\Actions\Journal\CreateJournalEntryAction;
use Modules\Accounting\Application\Actions\Journal\PostJournalEntryAction;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalEntryData;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalLineData;
use Modules\Accounting\Application\Services\AccountingPeriodResolver;
use Modules\Accounting\Models\SalesInvoice;

final class PostSalesInvoiceAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntry,
        private readonly PostJournalEntryAction $postJournalEntry,
        private readonly AccountingPeriodResolver $periodResolver,
    ) {}

    public function execute(
        SalesInvoice $invoice,
        CurrentTenant $currentTenant,
    ): SalesInvoice {
        return DB::transaction(function () use ($invoice, $currentTenant) {
            $invoice = SalesInvoice::query()
                ->where('tenant_id', $currentTenant->id())
                ->with(['customer', 'lines'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($invoice->status === 'posted' && $invoice->journal_entry_id !== null) {
                return $invoice;
            }

            abort_unless(
                $invoice->status === 'issued',
                422,
                'Only issued sales invoices can be posted.'
            );

            abort_unless(
                $invoice->customer->receivable_account_id !== null,
                422,
                'The customer does not have an accounts receivable account configured.'
            );

            $period = $this->periodResolver->resolve(
                tenantId: $currentTenant->id(),
                date: $invoice->invoice_date,
            );

            $journalLines = [];
            $lineNumber = 1;

            $journalLines[] = new CreateJournalLineData(
                accountId: $invoice->customer->receivable_account_id,
                lineNumber: $lineNumber++,
                lineType: 'debit',
                amount: $invoice->grand_total,
                description: 'Accounts receivable - Invoice '.$invoice->invoice_number,
            );

            foreach ($invoice->lines as $line) {
                $journalLines[] = new CreateJournalLineData(
                    accountId: $line->revenue_account_id,
                    lineNumber: $lineNumber++,
                    lineType: 'credit',
                    amount: $line->total,
                    description: $line->description ?: 'Sales revenue',
                );
            }

            $journal = $this->createJournalEntry->execute(
                new CreateJournalEntryData(
                    tenantId: $currentTenant->id(),
                    fiscalYearId: $period->fiscal_year_id,
                    accountingPeriodId: $period->id,
                    entryNumber: 'INV-'.$invoice->invoice_number,
                    entryDate: $invoice->invoice_date,
                    description: 'Sales invoice '.$invoice->invoice_number,
                    referenceType: SalesInvoice::class,
                    referenceId: $invoice->id,
                    idempotencyKey: 'accounting.sales_invoice.posted:'.$invoice->id,
                    createdBy: auth()->id(),
                    lines: $journalLines,
                )
            );

            if (! $journal->isPosted()) {
                $this->postJournalEntry->execute(
                    journal: $journal,
                    postedBy: auth()->id(),
                );
            }

            $invoice->update([
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
            ]);

            return $invoice->fresh([
                'customer',
                'lines',
                'journalEntry',
            ]);
        });
    }
}
