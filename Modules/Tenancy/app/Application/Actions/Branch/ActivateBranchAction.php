<?php

namespace Modules\Tenancy\Application\Actions\Branch;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Models\Branch;

final class ActivateBranchAction
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
            'status' => BranchStatus::Active,
        ]);

        return $branch->refresh();
    }
}
