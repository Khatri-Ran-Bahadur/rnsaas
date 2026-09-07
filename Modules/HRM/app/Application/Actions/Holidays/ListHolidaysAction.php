<?php

namespace Modules\HRM\Application\Actions\Holidays;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HRM\Models\Holiday;

final class ListHolidaysAction
{
    public function execute(
        int $tenantId,
        ?string $search = null,
        ?string $type = null,
        ?bool $active = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Holiday::query()
            ->forTenant($tenantId)
            ->when(
                $search,
                function ($query, string $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $type,
                fn ($query) => $query->where('type', $type)
            )
            ->when(
                $active !== null,
                fn ($query) => $query->where('is_active', $active)
            )
            ->when(
                $fromDate,
                fn ($query) => $query->where(
                    'end_date',
                    '>=',
                    $fromDate
                )->orWhere(function ($query) use ($fromDate): void {
                    $query->whereNull('end_date')
                        ->where('start_date', '>=', $fromDate);
                })
            )
            ->when(
                $toDate,
                fn ($query) => $query->where(
                    'start_date',
                    '<=',
                    $toDate
                )
            )
            ->orderBy('start_date')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
