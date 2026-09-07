<?php

namespace Modules\HRM\Application\Actions\Overtimes;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HRM\Models\Overtime;

final class ListOvertimesAction
{
    public function execute(
        int $tenantId,
        ?string $search = null,
        ?int $tenantStaffId = null,
        ?string $type = null,
        ?string $status = null,
        ?bool $active = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Overtime::query()
            ->forTenant($tenantId)
            ->with([
                'staff.user',
                'creator',
                'updater',
                'approver',
                'rejector',
            ])
            ->when(
                $search,
                function ($query, string $search): void {
                    $query->whereHas(
                        'staff.user',
                        function ($query) use ($search): void {
                            $query->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    )->orWhere(
                        'reason',
                        'like',
                        "%{$search}%"
                    );
                }
            )
            ->when(
                $tenantStaffId,
                fn ($query) => $query->where(
                    'tenant_staff_id',
                    $tenantStaffId
                )
            )
            ->when(
                $type,
                fn ($query) => $query->where('type', $type)
            )
            ->when(
                $status,
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                $active !== null,
                fn ($query) => $query->where(
                    'is_active',
                    $active
                )
            )
            ->when(
                $fromDate,
                fn ($query) => $query->where(
                    'date',
                    '>=',
                    $fromDate
                )
            )
            ->when(
                $toDate,
                fn ($query) => $query->where(
                    'date',
                    '<=',
                    $toDate
                )
            )
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->paginate($perPage)
            ->withQueryString();
    }
}
