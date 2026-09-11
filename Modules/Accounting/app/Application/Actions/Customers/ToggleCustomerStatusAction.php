<?php

namespace Modules\Accounting\Application\Actions\Customers;

use App\Support\Tenancy\CurrentTenant;
use Modules\Accounting\Models\Customer;

final class ToggleCustomerStatusAction
{
    public function execute(
        Customer $customer,
        CurrentTenant $currentTenant,
    ): Customer {
        abort_unless(
            $customer->tenant_id === $currentTenant->id(),
            404
        );

        $customer->update([
            'status' => $customer->status === 'active'
                ? 'inactive'
                : 'active',
        ]);

        return $customer->fresh();
    }
}
