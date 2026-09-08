<?php

namespace Modules\Accounting\Application\Actions\AccountingPeriod;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\AccountingPeriodStatus;
use Modules\Accounting\Models\AccountingPeriod;
use Modules\Accounting\Models\FiscalYear;

final class GenerateAccountingPeriodsAction
{
    public function execute(
        FiscalYear $fiscalYear,
    ): void {
        if ($fiscalYear->isClosed()) {
            throw new InvalidArgumentException(
                'Cannot generate periods for a closed fiscal year.',
            );
        }

        if ($fiscalYear->periods()->exists()) {
            throw new InvalidArgumentException(
                'Accounting periods have already been generated for this fiscal year.',
            );
        }

        DB::transaction(function () use ($fiscalYear): void {
            $cursor = CarbonImmutable::instance(
                $fiscalYear->start_date,
            );

            $endDate = CarbonImmutable::instance(
                $fiscalYear->end_date,
            );

            $periodNumber = 1;

            while ($cursor->lessThanOrEqualTo($endDate)) {
                $periodEnd = $cursor
                    ->endOfMonth()
                    ->min($endDate);

                AccountingPeriod::create([
                    'public_id' => (string) Str::uuid(),
                    'tenant_id' => $fiscalYear->tenant_id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'name' => $cursor->format('F Y'),
                    'period_number' => $periodNumber,
                    'start_date' => $cursor->toDateString(),
                    'end_date' => $periodEnd->toDateString(),
                    'status' => AccountingPeriodStatus::OPEN,
                ]);

                $cursor = $periodEnd->addDay();
                $periodNumber++;
            }
        });
    }
}
