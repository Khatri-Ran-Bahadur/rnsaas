<?php

namespace Modules\Accounting\Application\Actions\Vendors;

use App\Support\Tenancy\CurrentTenant;
use Modules\Accounting\Domain\Enums\VendorStatus;
use Modules\Accounting\Models\Vendor;

final class ToggleVendorStatusAction
{
    public function execute(
        Vendor $vendor,
        CurrentTenant $currentTenant,
    ): Vendor {
        abort_unless(
            $vendor->tenant_id === $currentTenant->id(),
            404
        );

        $vendor->update([
            'status' => $vendor->status === VendorStatus::ACTIVE
                ? VendorStatus::INACTIVE
                : VendorStatus::ACTIVE,
            'updated_by' => auth()->id(),
        ]);

        return $vendor->fresh();
    }
}
