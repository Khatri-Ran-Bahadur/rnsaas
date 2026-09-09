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
        if ($fromDate->greaterThan($toDate)) {
            throw new RuntimeException(
                'The start date cannot be after the end date.'
            );
        }

        $account = Account::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($accountId)
            ->first();

        if ($account === null) {
            throw new RuntimeException('Account not found.');
        }

        $normalBalance = $this->resolveNormalBalance($account);

        $openingBalance = $this->calculateBalance(
            tenantId: $tenantId,
            accountId: $accountId,
            beforeDate: $fromDate,
            normalBalance: $normalBalance,
        );

        $rows = $this->query
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

        $runningBalance = $openingBalance;

        $totalDebit = '0';
        $totalCredit = '0';

        $lines = [];

        foreach ($rows as $row) {
            $debit = $row->line_type === 'debit'
                ? (string) $row->amount
                : '0';

            $credit = $row->line_type === 'credit'
                ? (string) $row->amount
                : '0';

            $totalDebit = bcadd($totalDebit, $debit, 6);
            $totalCredit = bcadd($totalCredit, $credit, 6);

            $movement = $this->movement(
                debit: $debit,
                credit: $credit,
                normalBalance: $normalBalance,
            );

            $runningBalance = bcadd(
                $runningBalance,
                $movement,
                6
            );

            $lines[] = [
                'journal_entry_id' => (int) $row->journal_entry_id,
                'journal_public_id' => $row->journal_public_id,
                'entry_number' => $row->entry_number,
                'entry_date' => $row->entry_date,
                'description' => $row->line_description
                    ?: $row->journal_description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }

        return new GeneralLedgerData(
            accountId: $account->id,
            accountCode: $account->code,
            accountName: $account->name,
            normalBalance: $normalBalance,
            fromDate: $fromDate,
            toDate: $toDate,
            openingBalance: $openingBalance,
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
            closingBalance: $runningBalance,
            lines: $lines,
        );
    }

    private function calculateBalance(
        int $tenantId,
        int $accountId,
        CarbonImmutable $beforeDate,
        string $normalBalance,
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

        return $normalBalance === 'debit'
            ? bcsub($debit, $credit, 6)
            : bcsub($credit, $debit, 6);
    }

    private function movement(
        string $debit,
        string $credit,
        string $normalBalance,
    ): string {
        return $normalBalance === 'debit'
            ? bcsub($debit, $credit, 6)
            : bcsub($credit, $debit, 6);
    }

    private function resolveNormalBalance(Account $account): string
    {
        /*
         * Use the account-level normal balance override when your
         * Account model exposes one. Otherwise fall back to the
         * Account Type's normal balance.
         *
         * This keeps contra accounts such as accumulated depreciation
         * correctly represented.
         */

        if (
            isset($account->normal_balance)
            && $account->normal_balance !== null
        ) {
            return $account->normal_balance->value
                ?? $account->normal_balance;
        }

        $accountType = $account->accountType;

        if ($accountType === null) {
            throw new RuntimeException(
                'Account type is missing.'
            );
        }

        return $accountType->normal_balance->value
            ?? $accountType->normal_balance;
    }
}
