<?php

namespace Modules\Accounting\Application\Actions\VendorPayments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\Actions\Journal\CreateJournalEntryAction;
use Modules\Accounting\Application\Actions\Journal\PostJournalEntryAction;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalEntryData;
use Modules\Accounting\Application\DTOs\Journal\CreateJournalLineData;
use Modules\Accounting\Application\Services\AccountingPeriodResolver;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\VendorPayment;

final class PostVendorPaymentAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntry,
        private readonly PostJournalEntryAction $postJournalEntry,
        private readonly AccountingPeriodResolver $periodResolver,
    ) {}

    public function execute(
        VendorPayment $payment,
        CurrentTenant $currentTenant,
    ): VendorPayment {
        return DB::transaction(function () use ($payment, $currentTenant) {
            $payment = VendorPayment::query()
                ->where('tenant_id', $currentTenant->id())
                ->with('vendor')
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status->value === 'posted' && $payment->journal_entry_id !== null) {
                return $payment->fresh([
                    'vendor',
                    'allocations.purchaseBill',
                    'attachments',
                    'journalEntry',
                ]);
            }

            abort_unless(
                $payment->status->value === 'draft',
                422,
                'Only draft vendor payments can be posted.'
            );

            abort_unless(
                $payment->vendor->payable_account_id !== null,
                422,
                'The vendor does not have a payable account configured.'
            );

            $period = $this->periodResolver->resolve(
                tenantId: $currentTenant->id(),
                date: $payment->payment_date,
            );

            $bankAccount = Account::query()
                ->where('tenant_id', $currentTenant->id())
                ->findOrFail($payment->bank_account_id);

            $payableAccount = Account::query()
                ->where('tenant_id', $currentTenant->id())
                ->findOrFail(
                    $payment->vendor->payable_account_id
                );

            /*
             * Current accounting behavior posts Dr Accounts Payable / Cr Bank for the full amount.
             * FUTURE EXTENSION POINT:
             * When unallocated advance payments are introduced:
             * - Allocated portion: Dr Accounts Payable (settlement)
             * - Unallocated portion: Dr Vendor Advance / Prepayment
             * - Total: Cr Bank Account
             */
            $journal = $this->createJournalEntry->execute(
                new CreateJournalEntryData(
                    tenantId: $currentTenant->id(),
                    fiscalYearId: $period->fiscal_year_id,
                    accountingPeriodId: $period->id,
                    entryNumber: 'VP-'.$payment->payment_number,
                    entryDate: $payment->payment_date,
                    description: 'Vendor payment '.$payment->payment_number,
                    referenceType: VendorPayment::class,
                    referenceId: $payment->id,
                    idempotencyKey: 'accounting.vendor_payment.posted:'.$payment->id,
                    createdBy: auth()->id(),
                    lines: [
                        new CreateJournalLineData(
                            accountId: $payableAccount->id,
                            lineNumber: 1,
                            lineType: 'debit',
                            amount: $payment->amount,
                            description: 'Accounts payable settlement',
                        ),
                        new CreateJournalLineData(
                            accountId: $bankAccount->id,
                            lineNumber: 2,
                            lineType: 'credit',
                            amount: $payment->amount,
                            description: 'Vendor payment',
                        ),
                    ],
                )
            );

            if (! $journal->isPosted()) {
                $this->postJournalEntry->execute(
                    journal: $journal,
                    postedBy: auth()->id(),
                );
            }

            $payment->update([
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            return $payment->fresh([
                'vendor',
                'allocations.purchaseBill',
                'attachments',
                'journalEntry',
            ]);
        });
    }
}
