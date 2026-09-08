<?php

namespace Modules\Accounting\Application\Actions\FiscalYear;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\FiscalYearStatus;
use Modules\Accounting\Models\FiscalYear;

final class ReopenFiscalYearAction
{
    public function execute(
        FiscalYear $fiscalYear,
    ): FiscalYear {
        if ($fiscalYear->isOpen()) {
            throw new InvalidArgumentException(
                'Fiscal year is already open.',
            );
        }

        return DB::transaction(function () use (
            $fiscalYear,
        ): FiscalYear {
            $fiscalYear->update([
                'status' => FiscalYearStatus::OPEN,
                'closed_at' => null,
                'closed_by' => null,
            ]);

            return $fiscalYear->refresh();
        });
    }
}
