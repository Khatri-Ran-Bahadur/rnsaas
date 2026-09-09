<?php

namespace Modules\Accounting\Application\Actions\Statements;

use Carbon\CarbonImmutable;
use Modules\Accounting\Application\DTOs\Statements\FinancialStatementData;
use Modules\Accounting\Application\Services\Statements\FinancialStatementQuery;
use Modules\Accounting\Domain\Enums\FinancialStatement;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use RuntimeException;

final class GenerateBalanceSheetAction
{
    public function __construct(
        private readonly FinancialStatementQuery $query,
    ) {}

    public function execute(
        int $tenantId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
    ): FinancialStatementData {
        if ($fromDate->greaterThan($toDate)) {
            throw new RuntimeException(
                'The start date cannot be after the end date.'
            );
        }

        $rows = $this->query
            ->postedAccountTotals(
                tenantId: $tenantId,
                fromDate: $fromDate->toDateString(),
                toDate: $toDate->toDateString(),
            )
            ->get();

        $sections = [
            FinancialStatementSection::ASSETS->value => [],
            FinancialStatementSection::LIABILITIES->value => [],
            FinancialStatementSection::EQUITY->value => [],
        ];

        $totals = [
            FinancialStatementSection::ASSETS->value => '0',
            FinancialStatementSection::LIABILITIES->value => '0',
            FinancialStatementSection::EQUITY->value => '0',
        ];

        foreach ($rows as $row) {
            $section = $row->financial_statement_section
                ?: $row->type_financial_statement_section;

            if ($section === null) {
                continue;
            }

            $sectionEnum = FinancialStatementSection::tryFrom($section);

            if ($sectionEnum === null) {
                continue;
            }

            if (
                $sectionEnum->statement()
                !== FinancialStatement::BALANCE_SHEET
            ) {
                continue;
            }

            $debit = (string) $row->total_debit;
            $credit = (string) $row->total_credit;

            $amount = match ($sectionEnum) {
                FinancialStatementSection::ASSETS => bcsub($debit, $credit, 6),

                FinancialStatementSection::LIABILITIES,
                FinancialStatementSection::EQUITY => bcsub($credit, $debit, 6),

                default => '0',
            };

            $totals[$sectionEnum->value] = bcadd(
                $totals[$sectionEnum->value],
                $amount,
                6
            );

            $sections[$sectionEnum->value][] = [
                'account_id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'debit' => $debit,
                'credit' => $credit,
                'amount' => $amount,
            ];
        }

        /*
         * Net profit is part of equity on the balance sheet.
         *
         * It is calculated from the same posted journal data.
         */

        $profitAndLoss = app(
            GenerateProfitAndLossAction::class
        )->execute(
            tenantId: $tenantId,
            fromDate: $fromDate,
            toDate: $toDate,
        );

        $netProfit = $profitAndLoss->total;

        $equityWithProfit = bcadd(
            $totals[FinancialStatementSection::EQUITY->value],
            $netProfit,
            6
        );

        $totals[
            FinancialStatementSection::EQUITY->value
        ] = $equityWithProfit;

        $sections['net_profit'] = [
            'amount' => $netProfit,
        ];

        $totalAssets = $totals[
            FinancialStatementSection::ASSETS->value
        ];

        $totalLiabilitiesAndEquity = bcadd(
            $totals[
                FinancialStatementSection::LIABILITIES->value
            ],
            $equityWithProfit,
            6
        );

        $sections['total_assets'] = [
            'amount' => $totalAssets,
        ];

        $sections['total_liabilities_and_equity'] = [
            'amount' => $totalLiabilitiesAndEquity,
        ];

        $sections['is_balanced'] = bccomp(
            $totalAssets,
            $totalLiabilitiesAndEquity,
            6
        ) === 0;

        return new FinancialStatementData(
            statement: FinancialStatement::BALANCE_SHEET,
            fromDate: $fromDate,
            toDate: $toDate,
            sections: $sections,
            total: $totalAssets,
        );
    }
}
