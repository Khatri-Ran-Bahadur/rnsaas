<?php

namespace Modules\Accounting\Application\Actions\Payments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\Actions\Journal\CreateJournalEntryAction;
use Modules\Accounting\Application\Actions\Journal\PostJournalEntryAction;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalEntryData;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalLineData;
use Modules\Accounting\Application\Services\AccountingPeriodResolver;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\CustomerPayment;

final class PostCustomerPaymentAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntry,
        private readonly PostJournalEntryAction $postJournalEntry,
        private readonly AccountingPeriodResolver $periodResolver,
    ) {}

    public function execute(
        CustomerPayment $payment,
        CurrentTenant $currentTenant,
    ): CustomerPayment {
        return DB::transaction(function () use (
            $payment,
            $currentTenant,
        ) {
            $payment = CustomerPayment::query()
                ->where('tenant_id', $currentTenant->id())
                ->with('customer')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            abort_if(
                $payment->status !== 'draft',
                422,
                'Only draft payments can be posted.'
            );

            abort_unless(
                $payment->customer->receivable_account_id !== null,
                422,
                'The customer does not have a receivable account configured.'
            );

            abort_unless(
                $payment->bank_account_id !== null,
                422,
                'A bank account is required before posting the payment.'
            );

            $period = $this->periodResolver->resolve(
                tenantId: $currentTenant->id(),
                date: $payment->payment_date,
            );

            $bankAccount = Account::query()
                ->where('tenant_id', $currentTenant->id())
                ->findOrFail($payment->bank_account_id);

            $receivableAccount = Account::query()
                ->where('tenant_id', $currentTenant->id())
                ->findOrFail(
                    $payment->customer->receivable_account_id
                );

            $journal = $this->createJournalEntry->execute(
                new CreateJournalEntryData(
                    tenantId: $currentTenant->id(),
                    fiscalYearId: $period->fiscal_year_id,
                    accountingPeriodId: $period->id,
                    entryNumber: 'CP-'.$payment->payment_number,
                    entryDate: $payment->payment_date,
                    description: 'Customer payment '.$payment->payment_number,
                    referenceType: CustomerPayment::class,
                    referenceId: $payment->id,
                    idempotencyKey: 'customer-payment:'.$payment->id,
                    createdBy: auth()->id(),
                    lines: [
                        new CreateJournalLineData(
                            accountId: $bankAccount->id,
                            lineNumber: 1,
                            lineType: 'debit',
                            amount: $payment->amount,
                            description: 'Customer payment received',
                        ),
                        new CreateJournalLineData(
                            accountId: $receivableAccount->id,
                            lineNumber: 2,
                            lineType: 'credit',
                            amount: $payment->amount,
                            description: 'Customer receivable settlement',
                        ),
                    ],
                )
            );

            $this->postJournalEntry->execute(
                journalEntry: $journal,
                currentTenant: $currentTenant,
            );

            $payment->update([
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            return $payment->fresh([
                'customer',
                'allocations.invoice',
                'attachments',
                'journalEntry',
            ]);
        });
    }
}
