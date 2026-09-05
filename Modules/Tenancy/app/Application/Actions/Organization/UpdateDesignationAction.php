<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Application\DTOs\UpdateDesignationData;
use Modules\Tenancy\Models\Designation;

final class UpdateDesignationAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(
        Designation $designation,
        UpdateDesignationData $data,
    ): Designation {
        abort_unless(
            $designation->tenant_id === $this->currentTenant->id(),
            404
        );

        $designation->update([
            'name' => $data->name,
            'code' => strtoupper($data->code),
        ]);

        return $designation->refresh();
    }
}
