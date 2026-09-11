<?php

namespace Modules\Accounting\Application\Actions\Reports;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class GenerateReceivableAgingAction
{
    public function execute(
        CurrentTenant $currentTenant,
        ?string $asOfDate = null,
    ): array {
        $date = $asOfDate
            ? Carbon::parse($asOfDate)
            : now();

        $allocationsSubquery = DB::table('accounting_customer_payment_allocations as allocations')
            ->join(
                'accounting_customer_payments as payments',
                'payments.id',
                '=',
                'allocations.customer_payment_id'
            )
            ->where('allocations.tenant_id', $currentTenant->id())
            ->where('payments.status', 'posted')
            ->groupBy('allocations.sales_invoice_id')
            ->select([
                'allocations.sales_invoice_id',
                DB::raw('SUM(allocations.allocated_amount) as total_allocated'),
            ]);

        $invoices = DB::table('accounting_sales_invoices as invoices')
            ->join(
                'accounting_customers as customers',
                'customers.id',
                '=',
                'invoices.customer_id'
            )
            ->leftJoinSub($allocationsSubquery, 'alloc_summary', function ($join) {
                $join->on('invoices.id', '=', 'alloc_summary.sales_invoice_id');
            })
            ->where('invoices.tenant_id', $currentTenant->id())
            ->where('customers.tenant_id', $currentTenant->id())
            ->whereIn('invoices.status', ['issued', 'posted'])
            ->whereDate('invoices.invoice_date', '<=', $date)
            ->orderBy('customers.name')
            ->orderBy('invoices.due_date')
            ->get([
                'invoices.id',
                'invoices.public_id',
                'invoices.invoice_number',
                'invoices.due_date',
                'invoices.grand_total',
                DB::raw('COALESCE(alloc_summary.total_allocated, 0) as allocated_amount'),
                'customers.public_id as customer_public_id',
                'customers.customer_code',
                'customers.name as customer_name',
            ]);

        $result = [];

        foreach ($invoices as $invoice) {
            $allocated = (string) $invoice->allocated_amount;

            $outstanding = bcsub(
                (string) $invoice->grand_total,
                $allocated,
                6
            );

            if (bccomp($outstanding, '0.000000', 6) <= 0) {
                continue;
            }

            $dueDate = Carbon::parse($invoice->due_date);
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
                'customer_id' => $invoice->customer_public_id,
                'customer_code' => $invoice->customer_code,
                'customer_name' => $invoice->customer_name,
                'invoice_id' => $invoice->public_id,
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date,
                'original_amount' => number_format((float) $invoice->grand_total, 6, '.', ''),
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
