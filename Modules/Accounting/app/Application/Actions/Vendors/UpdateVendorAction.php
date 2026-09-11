<?php

namespace Modules\Accounting\Application\Actions\Vendors;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\Vendors\UpdateVendorData;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Vendor;

final class UpdateVendorAction
{
    public function execute(
        Vendor $vendor,
        UpdateVendorData $data,
        CurrentTenant $currentTenant,
    ): Vendor {
        return DB::transaction(function () use (
            $vendor,
            $data,
            $currentTenant,
        ) {
            abort_unless(
                $vendor->tenant_id === $currentTenant->id(),
                404
            );

            if ($data->payableAccountId !== null) {
                Account::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->findOrFail($data->payableAccountId);
            }

            $vendor->update([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'tax_number' => $data->taxNumber,
                'billing_address_line_1' => $data->billingAddressLine1,
                'billing_address_line_2' => $data->billingAddressLine2,
                'billing_city' => $data->billingCity,
                'billing_state' => $data->billingState,
                'billing_postcode' => $data->billingPostcode,
                'billing_country' => $data->billingCountry,
                'payable_account_id' => $data->payableAccountId,
                'credit_limit' => $data->creditLimit,
                'payment_terms_days' => $data->paymentTermsDays,
                'updated_by' => auth()->id(),
            ]);

            return $vendor->fresh();
        });
    }
}
