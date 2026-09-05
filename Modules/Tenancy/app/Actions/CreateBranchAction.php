<?php

namespace Modules\Tenancy\Actions;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Data\CreateBranchData;
use Modules\Tenancy\Domain\Enums\BranchStatus;
use Modules\Tenancy\Models\Branch;

final class CreateBranchAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(CreateBranchData $data): Branch
    {
        $tenant = $this->currentTenant->get();

        return DB::transaction(function () use ($data, $tenant): Branch {
            return Branch::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data->name,
                'code' => strtoupper($data->code),
                'status' => BranchStatus::Active,
                'address_line_1' => $data->addressLine1,
                'address_line_2' => $data->addressLine2,
                'city' => $data->city,
                'state' => $data->state,
                'postal_code' => $data->postalCode,
                'country_code' => $data->countryCode,
            ]);
        });
    }
}
