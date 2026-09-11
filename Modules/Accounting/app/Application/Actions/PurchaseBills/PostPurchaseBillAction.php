<?php

namespace Modules\Accounting\Application\Actions\PurchaseBills;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\Actions\Journal\CreateJournalEntryAction;
use Modules\Accounting\Application\Actions\Journal\PostJournalEntryAction;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalEntryData;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalLineData;
use Modules\Accounting\Application\Services\AccountingPeriodResolver;
use Modules\Accounting\Models\PurchaseBill;

final class PostPurchaseBillAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntry,
        private readonly PostJournalEntryAction $postJournalEntry,
        private readonly AccountingPeriodResolver $periodResolver,
    ) {}

    public function execute(
        PurchaseBill $bill,
        CurrentTenant $currentTenant,
    ): PurchaseBill {
        return DB::transaction(function () use ($bill, $currentTenant) {
            $bill = PurchaseBill::query()
                ->where('tenant_id', $currentTenant->id())
                ->with(['vendor', 'lines'])
                ->lockForUpdate()
                ->findOrFail($bill->id);

            if ($bill->status->value === 'posted' && $bill->journal_entry_id !== null) {
                return $bill;
            }

            abort_unless(
                $bill->status->value === 'issued',
                422,
                'Only issued purchase bills can be posted.'
            );

            abort_unless(
                $bill->vendor->payable_account_id !== null,
                422,
                'The vendor does not have a payable account configured.'
            );

            $period = $this->periodResolver->resolve(
                tenantId: $currentTenant->id(),
                date: $bill->bill_date,
            );

            $journalLines = [];
            $lineNumber = 1;

            foreach ($bill->lines as $line) {
                $journalLines[] = new CreateJournalLineData(
                    accountId: $line->debit_account_id,
                    lineNumber: $lineNumber++,
                    lineType: 'debit',
                    amount: $line->subtotal,
                    description: $line->description,
                );

                if (bccomp($line->tax_amount, '0.000000', 6) > 0) {
                    abort_unless(
                        $line->tax_account_id !== null,
                        422,
                        'A tax account is required for taxable purchase lines.'
                    );

                    $journalLines[] = new CreateJournalLineData(
                        accountId: $line->tax_account_id,
                        lineNumber: $lineNumber++,
                        lineType: 'debit',
                        amount: $line->tax_amount,
                        description: 'Input tax',
                    );
                }
            }

            $journalLines[] = new CreateJournalLineData(
                accountId: $bill->vendor->payable_account_id,
                lineNumber: $lineNumber,
                lineType: 'credit',
                amount: $bill->grand_total,
                description: 'Accounts payable',
            );

            $journal = $this->createJournalEntry->execute(
                new CreateJournalEntryData(
                    tenantId: $currentTenant->id(),
                    fiscalYearId: $period->fiscal_year_id,
                    accountingPeriodId: $period->id,
                    entryNumber: 'PB-'.$bill->bill_number,
                    entryDate: $bill->bill_date,
                    description: 'Purchase bill '.$bill->bill_number,
                    referenceType: PurchaseBill::class,
                    referenceId: $bill->id,
                    idempotencyKey: 'accounting.purchase_bill.posted:'.$bill->id,
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

            $bill->update([
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            return $bill->fresh([
                'vendor',
                'lines',
                'journalEntry',
                'attachments',
            ]);
        });
    }
}
