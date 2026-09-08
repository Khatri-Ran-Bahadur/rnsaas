<?php

namespace Modules\Accounting\Application\Actions\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\Reports\GeneralLedgerData;
use Modules\Accounting\Application\Services\Reports\AccountingReportQuery;
use Modules\Accounting\Models\Account;
use RuntimeException;

final class GenerateGeneralLedgerAction
{
    public function __construct(
        private readonly AccountingReportQuery $query,
    ) {}

    public function execute(
        int $tenantId,
        int $accountId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
    ): GeneralLedgerData {
        $account = Account::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($accountId)
            ->first();

        if ($account === null) {
            throw new RuntimeException('Account not found.');
        }

        $opening = $this->openingBalance(
            tenantId: $tenantId,
            accountId: $accountId,
            beforeDate: $fromDate,
        );

        $lines = $this->query
            ->postedJournalLines(
                tenantId: $tenantId,
                accountId: $accountId,
                fromDate: $fromDate->toDateString(),
                toDate: $toDate->toDateString(),
            )
            ->select([
                'journal_lines.id',
                'journal_lines.line_number',
                'journal_lines.line_type',
                'journal_lines.amount',
                'journal_lines.description as line_description',
                'journal_entries.id as journal_entry_id',
                'journal_entries.public_id as journal_public_id',
                'journal_entries.entry_number',
                'journal_entries.entry_date',
                'journal_entries.description as journal_description',
            ])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.line_number')
            ->get();

        $runningBalance = $opening;

        $resultLines = [];

        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($lines as $line) {
            $debit = $line->line_type === 'debit'
                ? (string) $line->amount
                : '0';

            $credit = $line->line_type === 'credit'
                ? (string) $line->amount
                : '0';

            $totalDebit = bcadd($totalDebit, $debit, 6);
            $totalCredit = bcadd($totalCredit, $credit, 6);

            $runningBalance = bcadd(
                $runningBalance,
                $debit,
                6
            );

            $runningBalance = bcsub(
                $runningBalance,
                $credit,
                6
            );

            $resultLines[] = [
                'journal_entry_id' => (int) $line->journal_entry_id,
                'journal_public_id' => $line->journal_public_id,
                'entry_number' => $line->entry_number,
                'entry_date' => $line->entry_date,
                'description' => $line->line_description
                    ?: $line->journal_description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }

        return new GeneralLedgerData(
            accountId: $account->id,
            accountCode: $account->code,
            accountName: $account->name,
            fromDate: $fromDate,
            toDate: $toDate,
            openingBalance: $opening,
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
            closingBalance: $runningBalance,
            lines: $resultLines,
        );
    }

    private function openingBalance(
        int $tenantId,
        int $accountId,
        CarbonImmutable $beforeDate,
    ): string {
        $row = $this->query
            ->postedJournalLines(
                tenantId: $tenantId,
                accountId: $accountId,
            )
            ->whereDate(
                'journal_entries.entry_date',
                '<',
                $beforeDate->toDateString()
            )
            ->select([
                DB::raw(
                    "COALESCE(SUM(
                        CASE
                            WHEN journal_lines.line_type = 'debit'
                            THEN journal_lines.amount
                            ELSE 0
                        END
                    ), 0) AS debit"
                ),
                DB::raw(
                    "COALESCE(SUM(
                        CASE
                            WHEN journal_lines.line_type = 'credit'
                            THEN journal_lines.amount
                            ELSE 0
                        END
                    ), 0) AS credit"
                ),
            ])
            ->first();

        $debit = (string) ($row->debit ?? '0');
        $credit = (string) ($row->credit ?? '0');

        return bcsub($debit, $credit, 6);
    }
}