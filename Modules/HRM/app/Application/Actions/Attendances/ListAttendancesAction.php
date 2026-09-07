<?php

namespace Modules\HRM\Application\Actions\Attendances;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HRM\Models\Attendance;

final class ListAttendancesAction
{
    public function execute(
        int $tenantId,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = Attendance::query()
            ->forTenant($tenantId)
            ->with([
                'staff.user',
                'creator',
                'updater',
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
                            'notes',
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
            $filters['status'] ?? null,
            fn ($query, $status) => $query->where(
                'status',
                $status,
            ),
        );

        $query->when(
            $filters['source'] ?? null,
            fn ($query, $source) => $query->where(
                'source',
                $source,
            ),
        );

        $query->when(
            $filters['date'] ?? null,
            fn ($query, $date) => $query->whereDate(
                'attendance_date',
                $date,
            ),
        );

        $query->when(
            $filters['from_date'] ?? null,
            fn ($query, $date) => $query->whereDate(
                'attendance_date',
                '>=',
                $date,
            ),
        );

        $query->when(
            $filters['to_date'] ?? null,
            fn ($query, $date) => $query->whereDate(
                'attendance_date',
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
            ->latest('attendance_date')
            ->latest('id')
            ->paginate(
                (int) ($filters['per_page'] ?? 20),
            )
            ->withQueryString();
    }
}
