<?php

namespace Modules\Accounting\Application\Actions\Reports;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Vendor;

final class GenerateVendorBalanceAction
{
    public function execute(
        Vendor $vendor,
        CurrentTenant $currentTenant,
    ): array {
        abort_unless(
            $vendor->tenant_id === $currentTenant->id(),
            404
        );

        $totalBills = DB::table('accounting_purchase_bills')
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->where('status', 'posted')
            ->value(DB::raw('COALESCE(SUM(grand_total), 0)'));

        $totalPayments = DB::table('accounting_vendor_payments')
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->where('status', 'posted')
            ->value(DB::raw('COALESCE(SUM(amount), 0)'));

        $balance = bcsub((string) $totalBills, (string) $totalPayments, 6);

        return [
            'vendor_id' => $vendor->public_id,
            'vendor_name' => $vendor->name,
            'balance' => number_format(
                (float) $balance,
                6,
                '.',
                ''
            ),
        ];
    }
}
