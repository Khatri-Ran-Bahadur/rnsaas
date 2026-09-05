<?php

namespace Modules\Tenancy\Actions;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Data\UpdateBranchData;
use Modules\Tenancy\Models\Branch;

final class UpdateBranchAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(Branch $branch, UpdateBranchData $data): Branch
    {
        $tenantId = $this->currentTenant->id();
        if ($branch->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use ($branch, $data): Branch {
            $branch->update([
                'name' => $data->name,
                'code' => strtoupper($data->code),
                'status' => $data->status,
                'address_line_1' => $data->addressLine1,
                'address_line_2' => $data->addressLine2,
                'city' => $data->city,
                'state' => $data->state,
                'postal_code' => $data->postalCode,
                'country_code' => $data->countryCode,
            ]);

            return $branch->refresh();
        });
    }
}
