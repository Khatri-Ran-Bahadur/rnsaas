<?php

namespace Modules\Accounting\Application\Services\Statements;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class FinancialStatementQuery
{
    public function postedAccountTotals(
        int $tenantId,
        string $fromDate,
        string $toDate,
    ): Builder {
        return DB::table('journal_lines')
            ->join(
                'journal_entries',
                'journal_entries.id',
                '=',
                'journal_lines.journal_entry_id'
            )
            ->join(
                'accounting_accounts',
                'accounting_accounts.id',
                '=',
                'journal_lines.account_id'
            )
            ->leftJoin(
                'accounting_account_types',
                'accounting_account_types.id',
                '=',
                'accounting_accounts.account_type_id'
            )
            ->where('journal_lines.tenant_id', $tenantId)
            ->where('journal_entries.tenant_id', $tenantId)
            ->where('accounting_accounts.tenant_id', $tenantId)
            ->where('journal_entries.status', 'posted')
            ->whereDate(
                'journal_entries.entry_date',
                '>=',
                $fromDate
            )
            ->whereDate(
                'journal_entries.entry_date',
                '<=',
                $toDate
            )
            ->select([
                'accounting_accounts.id',
                'accounting_accounts.code',
                'accounting_accounts.name',
                'accounting_accounts.financial_statement_section',
                'accounting_account_types.financial_statement_section as type_financial_statement_section',

                DB::raw(
                    "COALESCE(SUM(
                        CASE
                            WHEN journal_lines.line_type = 'debit'
                            THEN journal_lines.amount
                            ELSE 0
                        END
                    ), 0) AS total_debit"
                ),

                DB::raw(
                    "COALESCE(SUM(
                        CASE
                            WHEN journal_lines.line_type = 'credit'
                            THEN journal_lines.amount
                            ELSE 0
                        END
                    ), 0) AS total_credit"
                ),
            ])
            ->groupBy(
                'accounting_accounts.id',
                'accounting_accounts.code',
                'accounting_accounts.name',
                'accounting_accounts.financial_statement_section',
                'accounting_account_types.financial_statement_section'
            );
    }
}
