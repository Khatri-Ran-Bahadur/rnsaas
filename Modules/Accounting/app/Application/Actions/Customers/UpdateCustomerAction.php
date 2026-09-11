<?php

namespace Modules\Accounting\Application\Actions\Customers;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\Customers\UpdateCustomerData;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;

final class UpdateCustomerAction
{
    public function execute(
        Customer $customer,
        UpdateCustomerData $data,
        CurrentTenant $currentTenant,
    ): Customer {
        return DB::transaction(function () use (
            $customer,
            $data,
            $currentTenant,
        ) {
            abort_unless(
                $customer->tenant_id === $currentTenant->id(),
                404
            );

            if ($data->receivableAccountId !== null && $data->receivableAccountId > 0) {
                Account::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->findOrFail($data->receivableAccountId);
            }

            $customer->update([
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
                'receivable_account_id' => ($data->receivableAccountId !== null && $data->receivableAccountId > 0) ? $data->receivableAccountId : null,
                'credit_limit' => $data->creditLimit,
                'payment_terms_days' => $data->paymentTermsDays,
            ]);

            return $customer->fresh();
        });
    }
}
