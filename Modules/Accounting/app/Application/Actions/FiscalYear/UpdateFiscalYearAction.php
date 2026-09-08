<?php

namespace Modules\Accounting\Application\Actions\FiscalYear;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Models\FiscalYear;

final class UpdateFiscalYearAction
{
    public function execute(
        FiscalYear $fiscalYear,
        string $name,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): FiscalYear {
        if ($fiscalYear->isClosed()) {
            throw new InvalidArgumentException(
                'A closed fiscal year cannot be modified.',
            );
        }

        if ($endDate->lessThan($startDate)) {
            throw new InvalidArgumentException(
                'Fiscal year end date must be after or equal to start date.',
            );
        }

        $overlap = FiscalYear::query()
            ->where('tenant_id', $fiscalYear->tenant_id)
            ->whereKeyNot($fiscalYear->id)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();

        if ($overlap) {
            throw new InvalidArgumentException(
                'Fiscal year dates overlap with another fiscal year.',
            );
        }

        return DB::transaction(function () use (
            $fiscalYear,
            $name,
            $startDate,
            $endDate,
        ): FiscalYear {
            $fiscalYear->update([
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return $fiscalYear->refresh();
        });
    }
}
