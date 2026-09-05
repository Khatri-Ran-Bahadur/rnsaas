<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Domain\Enums\DesignationStatus;
use Modules\Tenancy\Models\Designation;

final class ActivateDesignationAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(Designation $designation): Designation
    {
        abort_unless(
            $designation->tenant_id === $this->currentTenant->id(),
            404
        );

        $designation->update([
            'status' => DesignationStatus::Active,
        ]);

        return $designation->refresh();
    }
}
