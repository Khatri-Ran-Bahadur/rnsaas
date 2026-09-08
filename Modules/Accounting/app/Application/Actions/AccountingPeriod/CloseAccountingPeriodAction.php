<?php

namespace Modules\Accounting\Application\Actions\AccountingPeriod;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\AccountingPeriodStatus;
use Modules\Accounting\Models\AccountingPeriod;

final class CloseAccountingPeriodAction
{
    public function execute(
        AccountingPeriod $period,
        ?int $closedBy = null,
    ): AccountingPeriod {
        if ($period->isClosed()) {
            throw new InvalidArgumentException(
                'Accounting period is already closed.',
            );
        }

        return DB::transaction(function () use (
            $period,
            $closedBy,
        ): AccountingPeriod {
            $period->update([
                'status' => AccountingPeriodStatus::CLOSED,
                'closed_at' => now(),
                'closed_by' => $closedBy,
            ]);

            return $period->refresh();
        });
    }
}
