<?php

namespace Modules\Accounting\Application\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Modules\Accounting\Models\AccountingPeriod;

final class AccountingPeriodResolver
{
    public function resolve(
        int $tenantId,
        CarbonInterface $date,
    ): AccountingPeriod {
        $period = AccountingPeriod::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->with('fiscalYear')
            ->first();

        if ($period === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'No accounting period exists for date %s.',
                    $date->toDateString(),
                ),
            );
        }

        if ($period->isClosed()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Accounting period "%s" is closed.',
                    $period->name,
                ),
            );
        }

        if ($period->fiscalYear->isClosed()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Fiscal year "%s" is closed.',
                    $period->fiscalYear->name,
                ),
            );
        }

        return $period;
    }
}
