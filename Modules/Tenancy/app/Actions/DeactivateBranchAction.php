<?php

namespace Modules\Tenancy\Actions;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Models\Branch;

final class DeactivateBranchAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(Branch $branch): Branch
    {
        $tenantId = $this->currentTenant->id();
        if ($branch->tenant_id !== $tenantId) {
            abort(404);
        }

        $branch->update([
            'status' => BranchStatus::Inactive,
        ]);

        return $branch->refresh();
    }
}
