<?php

namespace Modules\Accounting\Application\Actions\Reports;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class GeneratePayableAgingAction
{
    public function execute(
        CurrentTenant $currentTenant,
        ?string $asOfDate = null,
    ): array {
        $date = $asOfDate
            ? Carbon::parse($asOfDate)
            : now();

        $allocationsSubquery = DB::table('accounting_vendor_payment_allocations as allocations')
            ->join(
                'accounting_vendor_payments as payments',
                'payments.id',
                '=',
                'allocations.vendor_payment_id'
            )
            ->where('allocations.tenant_id', $currentTenant->id())
            ->where('payments.status', 'posted')
            ->groupBy('allocations.purchase_bill_id')
            ->select([
                'allocations.purchase_bill_id',
                DB::raw('SUM(allocations.allocated_amount) as total_allocated'),
            ]);

        $bills = DB::table('accounting_purchase_bills as bills')
            ->join(
                'accounting_vendors as vendors',
                'vendors.id',
                '=',
                'bills.vendor_id'
            )
            ->leftJoinSub($allocationsSubquery, 'alloc_summary', function ($join) {
                $join->on('bills.id', '=', 'alloc_summary.purchase_bill_id');
            })
            ->where('bills.tenant_id', $currentTenant->id())
            ->where('vendors.tenant_id', $currentTenant->id())
            ->whereIn('bills.status', ['issued', 'posted'])
            ->whereDate('bills.bill_date', '<=', $date)
            ->orderBy('vendors.name')
            ->orderBy('bills.due_date')
            ->get([
                'bills.id',
                'bills.public_id',
                'bills.bill_number',
                'bills.due_date',
                'bills.grand_total',
                DB::raw('COALESCE(alloc_summary.total_allocated, 0) as allocated_amount'),
                'vendors.public_id as vendor_public_id',
                'vendors.vendor_code',
                'vendors.name as vendor_name',
            ]);

        $result = [];

        foreach ($bills as $bill) {
            $allocated = (string) $bill->allocated_amount;

            $outstanding = bcsub(
                (string) $bill->grand_total,
                $allocated,
                6
            );

            if (bccomp($outstanding, '0.000000', 6) <= 0) {
                continue;
            }

            $dueDate = Carbon::parse($bill->due_date);
            $daysOverdue = max(
                0,
                $dueDate->diffInDays($date, false)
            );

            $bucket = match (true) {
                $daysOverdue <= 0 => 'current',
                $daysOverdue <= 30 => '1_30',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                default => '90_plus',
            };

            $result[] = [
                'vendor_id' => $bill->vendor_public_id,
                'vendor_code' => $bill->vendor_code,
                'vendor_name' => $bill->vendor_name,
                'bill_id' => $bill->public_id,
                'bill_number' => $bill->bill_number,
                'due_date' => $bill->due_date,
                'original_amount' => number_format((float) $bill->grand_total, 6, '.', ''),
                'paid_amount' => number_format(
                    (float) $allocated,
                    6,
                    '.',
                    ''
                ),
                'outstanding_amount' => number_format((float) $outstanding, 6, '.', ''),
                'bucket' => $bucket,
                'days_overdue' => (int) $daysOverdue,
            ];
        }

        return $result;
    }
}
