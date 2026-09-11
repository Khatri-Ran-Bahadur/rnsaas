<?php

namespace Modules\Accounting\Application\Actions\Payments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\Payments\CreateCustomerPaymentData;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\CustomerPayment;

final class CreateCustomerPaymentAction
{
    public function execute(
        CreateCustomerPaymentData $data,
        CurrentTenant $currentTenant,
    ): CustomerPayment {
        return DB::transaction(function () use ($data, $currentTenant) {
            $customer = Customer::query()
                ->where('tenant_id', $currentTenant->id())
                ->findOrFail($data->customerId);

            abort_unless(
                $customer->is_active,
                422,
                'The customer is inactive.'
            );

            return CustomerPayment::query()->create([
                'tenant_id' => $currentTenant->id(),
                'customer_id' => $customer->id,
                'payment_number' => $data->paymentNumber,
                'payment_date' => $data->paymentDate,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'bank_account_id' => $data->bankAccountId,
                'payment_method' => $data->paymentMethod,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
        });
    }
}
