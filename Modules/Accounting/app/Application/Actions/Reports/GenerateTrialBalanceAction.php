<?php

namespace Modules\Accounting\Application\Actions\Reports;

use Carbon\CarbonImmutable;
use Modules\Accounting\Application\DTOs\Reports\TrialBalanceData;
use Modules\Accounting\Application\Services\Reports\AccountingReportQuery;

final class GenerateTrialBalanceAction
{
    public function __construct(
        private readonly AccountingReportQuery $query,
    ) {}

    public function execute(
        int $tenantId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
    ): TrialBalanceData {
        $rows = $this->query
            ->accountTotals(
                tenantId: $tenantId,
                fromDate: $fromDate->toDateString(),
                toDate: $toDate->toDateString(),
            )
            ->orderBy('accounting_accounts.code')
            ->get();

        $accounts = [];

        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($rows as $row) {
            $debit = (string) $row->total_debit;
            $credit = (string) $row->total_credit;

            $totalDebit = bcadd($totalDebit, $debit, 6);
            $totalCredit = bcadd($totalCredit, $credit, 6);

            $accounts[] = [
                'account_id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return new TrialBalanceData(
            fromDate: $fromDate,
            toDate: $toDate,
            accounts: $accounts,
            totalDebit: $totalDebit,
            totalCredit: $totalCredit,
        );
    }
}
