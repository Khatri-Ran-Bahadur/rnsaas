<?php

namespace Modules\Tenancy\Application\Actions\Organization;

use App\Support\Tenancy\CurrentTenant;
use Modules\Tenancy\Application\DTOs\CreateDesignationData;
use Modules\Tenancy\Domain\Enums\DesignationStatus;
use Modules\Tenancy\Models\Designation;

final class CreateDesignationAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(CreateDesignationData $data): Designation
    {
        return Designation::query()->create([
            'tenant_id' => $this->currentTenant->id(),
            'name' => $data->name,
            'code' => strtoupper($data->code),
            'status' => DesignationStatus::Active,
        ]);
    }
}
