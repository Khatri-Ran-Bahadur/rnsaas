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

        return $tenant->users()
            ->select([
                'users.id',
                'users.name',
                'users.email',
            ])
            ->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
