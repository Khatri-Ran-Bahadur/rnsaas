<?php

namespace Modules\Accounting\Application\Actions\VendorPayments;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\DTOs\VendorPayments\CreateVendorPaymentData;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Vendor;
use Modules\Accounting\Models\VendorPayment;

final class CreateVendorPaymentAction
{
    public function execute(
        CreateVendorPaymentData $data,
        CurrentTenant $currentTenant,
    ): VendorPayment {
        return DB::transaction(function () use ($data, $currentTenant) {
            $vendor = Vendor::query()
                ->where('tenant_id', $currentTenant->id())
                ->where('status', 'active')
                ->findOrFail($data->vendorId);

            $bankAccount = Account::query()
                ->where('tenant_id', $currentTenant->id())
                ->findOrFail($data->bankAccountId);

            abort_unless(
                $data->amount > 0,
                422,
                'Payment amount must be greater than zero.'
            );

            return VendorPayment::query()->create([
                'tenant_id' => $currentTenant->id(),
                'vendor_id' => $vendor->id,
                'payment_number' => $data->paymentNumber,
                'payment_date' => $data->paymentDate,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'payment_method' => $data->paymentMethod,
                'bank_account_id' => $bankAccount->id,
                'reference' => $data->reference,
                'notes' => $data->notes,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
        });
    }
}
