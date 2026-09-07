<?php

namespace Modules\HRM\Application\Actions\Leaves;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HRM\Models\LeaveRequest;

final class ListLeavesAction
{
    public function execute(
        int $tenantId,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = LeaveRequest::query()
            ->forTenant($tenantId)
            ->with([
                'staff.user',
                'creator',
                'updater',
                'approver',
                'rejector',
            ]);

        $query->when(
            $filters['search'] ?? null,
            function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereHas(
                            'staff.user',
                            function ($query) use ($search): void {
                                $query->where(
                                    'name',
                                    'like',
                                    "%{$search}%",
                                );
                            },
                        )
                        ->orWhereHas(
                            'staff',
                            function ($query) use ($search): void {
                                $query->where(
                                    'employee_code',
                                    'like',
                                    "%{$search}%",
                                );
                            },
                        )
                        ->orWhere(
                            'reason',
                            'like',
                            "%{$search}%",
                        );
                });
            },
        );

        $query->when(
            $filters['tenant_staff_id'] ?? null,
            fn ($query, $staffId) => $query->where(
                'tenant_staff_id',
                $staffId,
            ),
        );

        $query->when(
            $filters['leave_type'] ?? null,
            fn ($query, $type) => $query->where(
                'leave_type',
                $type,
            ),
        );

        $query->when(
            $filters['status'] ?? null,
            fn ($query, $status) => $query->where(
                'status',
                $status,
            ),
        );

        $query->when(
            $filters['from_date'] ?? null,
            fn ($query, $date) => $query->whereDate(
                'end_date',
                '>=',
                $date,
            ),
        );

        $query->when(
            $filters['to_date'] ?? null,
            fn ($query, $date) => $query->whereDate(
                'start_date',
                '<=',
                $date,
            ),
        );

        if (array_key_exists('active', $filters)) {
            $query->where(
                'is_active',
                (bool) $filters['active'],
            );
        }

        return $query
            ->latest('start_date')
            ->latest('id')
            ->paginate(
                (int) ($filters['per_page'] ?? 20),
            )
            ->withQueryString();
    }
}
