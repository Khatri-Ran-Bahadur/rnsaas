<?php

namespace Modules\Admin\Actions;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetOrganizationMembersAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(
        int $perPage = 20,
    ): LengthAwarePaginator {
        $tenant = $this->currentTenant->get();
        $tenantId = $tenant->id;

        return $tenant->users()
            ->select([
                'users.id',
                'users.name',
                'users.email',
            ])
            ->selectRaw('EXISTS(SELECT 1 FROM tenant_staff WHERE tenant_staff.user_id = users.id AND tenant_staff.tenant_id = ?) as is_staff', [$tenantId])
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
