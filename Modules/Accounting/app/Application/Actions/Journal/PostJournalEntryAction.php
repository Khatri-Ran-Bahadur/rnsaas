<?php

namespace Modules\Accounting\Application\Actions\Journal;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Accounting\Domain\Exceptions\JournalPostingException;
use Modules\Accounting\Models\JournalEntry;

final class PostJournalEntryAction
{
    public function execute(
        JournalEntry $journal,
        ?int $postedBy = null,
    ): JournalEntry {
        return DB::transaction(function () use (
            $journal,
            $postedBy,
        ): JournalEntry {
            $journal = JournalEntry::query()
                ->whereKey($journal->id)
                ->lockForUpdate()
                ->with([
                    'lines.account',
                    'accountingPeriod',
                    'fiscalYear',
                ])
                ->firstOrFail();

            if (! $journal->isDraft()) {
                throw new JournalPostingException(
                    'Only draft journal entries can be posted.',
                );
            }

            if (! $journal->accountingPeriod->isOpen()) {
                throw new JournalPostingException(
                    'Cannot post journal into a closed accounting period.',
                );
            }

            if (! $journal->fiscalYear->isOpen()) {
                throw new JournalPostingException(
                    'Cannot post journal into a closed fiscal year.',
                );
            }

            if ($journal->lines->count() < 2) {
                throw new InvalidArgumentException(
                    'A journal must contain at least two lines.',
                );
            }

            $debitTotal = '0.000000';
            $creditTotal = '0.000000';

            foreach ($journal->lines as $line) {
                if (! $line->account->is_active) {
                    throw new JournalPostingException(
                        sprintf(
                            'Account "%s" is inactive.',
                            $line->account->code,
                        ),
                    );
                }

                if (! $line->account->is_postable) {
                    throw new JournalPostingException(
                        sprintf(
                            'Account "%s" is not postable.',
                            $line->account->code,
                        ),
                    );
                }

                if ($line->isDebit()) {
                    $debitTotal = bcadd(
                        $debitTotal,
                        (string) $line->amount,
                        6,
                    );
                } else {
                    $creditTotal = bcadd(
                        $creditTotal,
                        (string) $line->amount,
                        6,
                    );
                }
            }

            if (bccomp($debitTotal, $creditTotal, 6) !== 0) {
                throw new JournalPostingException(
                    sprintf(
                        'Journal is not balanced. Debit: %s, Credit: %s.',
                        $debitTotal,
                        $creditTotal,
                    ),
                );
            }

            $journal->update([
                'status' => JournalEntryStatus::POSTED,
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ]);

            return $journal->refresh();
        });
    }
}
