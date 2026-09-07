<?php

namespace Modules\HRM\Application\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HRM\Models\WorkSchedule;

final class ListWorkSchedulesAction
{
    public function execute(
        int $tenantId,
        ?string $search = null,
        ?bool $isActive = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return WorkSchedule::query()
            ->forTenant($tenantId)
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
            )
            ->when(
                $isActive !== null,
                fn ($query) => $query->where('is_active', $isActive)
            )
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
