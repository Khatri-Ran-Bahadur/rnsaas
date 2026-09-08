<?php

namespace Modules\Accounting\Application\Actions\AccountingPeriod;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\AccountingPeriodStatus;
use Modules\Accounting\Models\AccountingPeriod;

final class ReopenAccountingPeriodAction
{
    public function execute(
        AccountingPeriod $period,
    ): AccountingPeriod {
        if ($period->isOpen()) {
            throw new InvalidArgumentException(
                'Accounting period is already open.',
            );
        }

        if ($period->fiscalYear->isClosed()) {
            throw new InvalidArgumentException(
                'Cannot reopen a period belonging to a closed fiscal year.',
            );
        }

        return DB::transaction(function () use (
            $period,
        ): AccountingPeriod {
            $period->update([
                'status' => AccountingPeriodStatus::OPEN,
                'closed_at' => null,
                'closed_by' => null,
            ]);

            return $period->refresh();
        });
    }
}
