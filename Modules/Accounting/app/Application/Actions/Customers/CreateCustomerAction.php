<?php

namespace Modules\Accounting\Application\Actions\Customers;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\Customers\CreateCustomerData;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;

final class CreateCustomerAction
{
    public function execute(
        CreateCustomerData $data,
        CurrentTenant $currentTenant,
    ): Customer {
        return DB::transaction(function () use ($data, $currentTenant) {
            if ($data->receivableAccountId !== null && $data->receivableAccountId > 0) {
                Account::query()
                    ->where('tenant_id', $currentTenant->id())
                    ->findOrFail($data->receivableAccountId);
            }

            return Customer::query()->create([
                'tenant_id' => $currentTenant->id(),
                'customer_code' => $data->customerCode,
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
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
        });
    }
}
