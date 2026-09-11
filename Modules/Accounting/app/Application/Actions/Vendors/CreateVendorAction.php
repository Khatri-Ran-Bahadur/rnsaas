<?php

namespace Modules\Accounting\Application\Actions\Vendors;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\Vendors\CreateVendorData;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Vendor;

final class CreateVendorAction
{
    public function execute(
        CreateVendorData $data,
        CurrentTenant $currentTenant,
    ): Vendor {
        return DB::transaction(function () use ($data, $currentTenant) {
            if ($data->payableAccountId !== null) {
                Account::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->findOrFail($data->payableAccountId);
            }

            return Vendor::query()->create([
                'tenant_id' => $currentTenant->id(),
                'vendor_code' => $data->vendorCode,
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
                'status' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }
}
