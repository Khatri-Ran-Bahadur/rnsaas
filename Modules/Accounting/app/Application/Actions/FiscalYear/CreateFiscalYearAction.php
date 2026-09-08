<?php

namespace Modules\Accounting\Application\Actions\FiscalYear;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Accounting\Domain\Enums\FiscalYearStatus;
use Modules\Accounting\Models\FiscalYear;
use Modules\Tenancy\Models\Tenant;

final class CreateFiscalYearAction
{
    public function execute(
        Tenant $tenant,
        string $code,
        string $name,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        bool $isCurrent = false,
    ): FiscalYear {
        if ($endDate->lessThan($startDate)) {
            throw new InvalidArgumentException(
                'Fiscal year end date must be after or equal to start date.',
            );
        }

        $overlapping = FiscalYear::query()
            ->where('tenant_id', $tenant->id)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();

        if ($overlapping) {
            throw new InvalidArgumentException(
                'Fiscal year dates overlap with an existing fiscal year.',
            );
        }

        return DB::transaction(function () use (
            $tenant,
            $code,
            $name,
            $startDate,
            $endDate,
            $isCurrent,
        ): FiscalYear {
            if ($isCurrent) {
                FiscalYear::query()
                    ->where('tenant_id', $tenant->id)
                    ->update([
                        'is_current' => false,
                    ]);
            }

            return FiscalYear::create([
                'public_id' => (string) Str::uuid(),
                'tenant_id' => $tenant->id,
                'code' => $code,
                'name' => $name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => FiscalYearStatus::OPEN,
                'is_current' => $isCurrent,
            ]);
        });
    }
}
