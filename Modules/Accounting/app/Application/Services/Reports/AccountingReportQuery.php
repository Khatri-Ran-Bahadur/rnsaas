<?php

namespace Modules\Accounting\Application\Services\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class AccountingReportQuery
{
    public function postedJournalLines(
        int $tenantId,
        ?int $accountId = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): Builder {
        return DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'journal_lines.account_id'
            )
            ->where('journal_lines.tenant_id', $tenantId)
            ->where('journal_entries.tenant_id', $tenantId)
            ->where('accounts.tenant_id', $tenantId)
            ->where('journal_entries.status', 'posted')
            ->when(
                $accountId !== null,
                fn (Builder $query) => $query->where(
                    'journal_lines.account_id',
                    $accountId
                )
            )
            ->when(
                $fromDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'journal_entries.entry_date',
                    '>=',
                    $fromDate
                )
            )
            ->when(
                $toDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'journal_entries.entry_date',
                    '<=',
                    $toDate
                )
            );
    }

    public function accountTotals(
        int $tenantId,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): Builder {
        return $this->postedJournalLines(
            tenantId: $tenantId,
            fromDate: $fromDate,
            toDate: $toDate,
        )
            ->select([
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.account_type_id',
                DB::raw(
                    "SUM(CASE WHEN journal_lines.line_type = 'debit'
                        THEN journal_lines.amount ELSE 0 END) AS total_debit"
                ),
                DB::raw(
                    "SUM(CASE WHEN journal_lines.line_type = 'credit'
                        THEN journal_lines.amount ELSE 0 END) AS total_credit"
                ),
            ])
            ->groupBy(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.account_type_id'
            );
    }
}