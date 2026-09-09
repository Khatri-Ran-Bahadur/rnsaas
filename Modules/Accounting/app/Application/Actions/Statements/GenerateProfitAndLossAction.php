<?php

namespace Modules\Accounting\Application\Actions\Statements;

use Carbon\CarbonImmutable;
use Modules\Accounting\Application\DTOs\Statements\FinancialStatementData;
use Modules\Accounting\Application\Services\Statements\FinancialStatementQuery;
use Modules\Accounting\Domain\Enums\FinancialStatement;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use RuntimeException;

final class GenerateProfitAndLossAction
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
            FinancialStatementSection::REVENUE->value => [],
            FinancialStatementSection::COST_OF_SALES->value => [],
            FinancialStatementSection::OPERATING_EXPENSES->value => [],
            FinancialStatementSection::OTHER_INCOME->value => [],
            FinancialStatementSection::OTHER_EXPENSES->value => [],
        ];

        $sectionTotals = [];

        foreach ($sections as $section => $_) {
            $sectionTotals[$section] = '0';
        }

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
                !== FinancialStatement::PROFIT_AND_LOSS
            ) {
                continue;
            }

            $debit = (string) $row->total_debit;
            $credit = (string) $row->total_credit;

            $amount = match ($sectionEnum) {
                FinancialStatementSection::REVENUE,
                FinancialStatementSection::OTHER_INCOME => bcsub($credit, $debit, 6),

                FinancialStatementSection::COST_OF_SALES,
                FinancialStatementSection::OPERATING_EXPENSES,
                FinancialStatementSection::OTHER_EXPENSES => bcsub($debit, $credit, 6),

                default => '0',
            };

            $sectionTotals[$section] = bcadd(
                $sectionTotals[$section],
                $amount,
                6
            );

            $sections[$section][] = [
                'account_id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'debit' => $debit,
                'credit' => $credit,
                'amount' => $amount,
            ];
        }

        $grossProfit = bcsub(
            bcsub(
                $sectionTotals[
                    FinancialStatementSection::REVENUE->value
                ],
                $sectionTotals[
                    FinancialStatementSection::COST_OF_SALES->value
                ],
                6
            ),
            '0',
            6
        );

        $operatingProfit = bcsub(
            $grossProfit,
            $sectionTotals[
                FinancialStatementSection::OPERATING_EXPENSES->value
            ],
            6
        );

        $netProfit = bcsub(
            bcadd(
                $operatingProfit,
                $sectionTotals[
                    FinancialStatementSection::OTHER_INCOME->value
                ],
                6
            ),
            $sectionTotals[
                FinancialStatementSection::OTHER_EXPENSES->value
            ],
            6
        );

        $sections['gross_profit'] = [
            'amount' => $grossProfit,
        ];

        $sections['operating_profit'] = [
            'amount' => $operatingProfit,
        ];

        $sections['net_profit'] = [
            'amount' => $netProfit,
        ];

        return new FinancialStatementData(
            statement: FinancialStatement::PROFIT_AND_LOSS,
            fromDate: $fromDate,
            toDate: $toDate,
            sections: $sections,
            total: $netProfit,
        );
    }
}
