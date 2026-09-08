<?php

namespace Modules\Accounting\Application\Actions\FiscalYear;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\AccountingPeriodStatus;
use Modules\Accounting\Domain\Enums\FiscalYearStatus;
use Modules\Accounting\Models\FiscalYear;

final class CloseFiscalYearAction
{
    public function execute(
        FiscalYear $fiscalYear,
        ?int $closedBy = null,
    ): FiscalYear {
        if ($fiscalYear->isClosed()) {
            throw new InvalidArgumentException(
                'Fiscal year is already closed.',
            );
        }

        $openPeriodsExist = $fiscalYear->periods()
            ->where(
                'status',
                AccountingPeriodStatus::OPEN->value,
            )
            ->exists();

        if ($openPeriodsExist) {
            throw new InvalidArgumentException(
                'All accounting periods must be closed before closing the fiscal year.',
            );
        }

        return DB::transaction(function () use (
            $fiscalYear,
            $closedBy,
        ): FiscalYear {
            $fiscalYear->update([
                'status' => FiscalYearStatus::CLOSED,
                'is_current' => false,
                'closed_at' => now(),
                'closed_by' => $closedBy,
            ]);

            return $fiscalYear->refresh();
        });
    }
}
