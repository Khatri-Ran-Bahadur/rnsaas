<?php

namespace Modules\Accounting\Application\Actions\Journal;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Accounting\Models\JournalEntry;

final class ReverseJournalEntryAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournal,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(
        JournalEntry $journal,
        CarbonInterface $reversalDate,
        ?int $createdBy = null,
        ?string $description = null,
    ): JournalEntry {
        return DB::transaction(function () use (
            $journal,
            $reversalDate,
            $createdBy,
            $description,
        ): JournalEntry {
            $journal = JournalEntry::query()
                ->whereKey($journal->id)
                ->lockForUpdate()
                ->with('lines')
                ->firstOrFail();

            if (! $journal->isPosted()) {
                throw new InvalidArgumentException(
                    'Only posted journal entries can be reversed.',
                );
            }

            if ($journal->reversal()->exists()) {
                throw new InvalidArgumentException(
                    'This journal entry has already been reversed.',
                );
            }

            $reversalLines = $journal->lines
                ->map(function ($line): array {
                    return [
                        'account_id' => $line->account_id,
                        'line_type' => $line->isDebit()
                            ? 'credit'
                            : 'debit',
                        'amount' => (string) $line->amount,
                        'description' => $line->description,
                    ];
                })
                ->all();

            $reversal = $this->createJournal->execute(
                tenantId: $journal->tenant_id,
                entryNumber: $journal->entry_number.'-REV',
                entryDate: $reversalDate,
                description: $description
                    ?? 'Reversal of '.$journal->entry_number,
                lines: $reversalLines,
                createdBy: $createdBy,
                referenceType: 'journal_reversal',
                referenceId: (string) $journal->id,
                idempotencyKey: 'reversal:'.$journal->id,
            );

            $reversal->update([
                'reverses_journal_entry_id' => $journal->id,
            ]);

            $reversal = $this->postJournal->execute(
                $reversal,
                $createdBy,
            );

            $journal->update([
                'status' => JournalEntryStatus::REVERSED,
                'reversed_by' => $reversal->id,
            ]);

            return $reversal->refresh();
        });
    }
}
