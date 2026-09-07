<?php

namespace Modules\HRM\Application\Actions\EmployeeDocuments;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HRM\Domain\Enums\EmployeeDocumentStatus;
use Modules\HRM\Models\EmployeeDocument;

final class ListEmployeeDocumentsAction
{
    public function execute(
        int $tenantId,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = EmployeeDocument::query()
            ->forTenant($tenantId)
            ->with([
                'staff.user',
                'creator',
                'updater',
                'verifier',
            ]);

        $query->when(
            $filters['search'] ?? null,
            function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere(
                            'document_number',
                            'like',
                            "%{$search}%",
                        )
                        ->orWhereHas(
                            'staff.user',
                            function ($query) use ($search): void {
                                $query->where(
                                    'name',
                                    'like',
                                    "%{$search}%",
                                );
                            },
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
            $filters['type'] ?? null,
            fn ($query, $type) => $query->where('type', $type),
        );

        $query->when(
            $filters['status'] ?? null,
            fn ($query, $status) => $query->where('status', $status),
        );

        if (array_key_exists('active', $filters)) {
            $query->where(
                'is_active',
                (bool) $filters['active'],
            );
        }

        if (! empty($filters['expiring_within_days'])) {
            $query
                ->whereNotNull('expiry_date')
                ->whereDate(
                    'expiry_date',
                    '>=',
                    now()->toDateString(),
                )
                ->whereDate(
                    'expiry_date',
                    '<=',
                    now()
                        ->addDays((int) $filters['expiring_within_days'])
                        ->toDateString(),
                )
                ->where(
                    'status',
                    '!=',
                    EmployeeDocumentStatus::EXPIRED,
                );
        }

        return $query
            ->latest('id')
            ->paginate(
                (int) ($filters['per_page'] ?? 20),
            )
            ->withQueryString();
    }
}
