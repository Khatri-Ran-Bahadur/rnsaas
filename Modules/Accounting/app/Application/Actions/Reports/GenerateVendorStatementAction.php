<?php

namespace Modules\Accounting\Application\Actions\Reports;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Vendor;

final class GenerateVendorStatementAction
{
    public function execute(
        Vendor $vendor,
        string $fromDate,
        string $toDate,
        CurrentTenant $currentTenant,
    ): array {
        abort_unless(
            $vendor->tenant_id === $currentTenant->id(),
            404
        );

        $priorBills = DB::table('accounting_purchase_bills')
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['issued', 'posted'])
            ->where('bill_date', '<', $fromDate)
            ->value(DB::raw('COALESCE(SUM(grand_total), 0)'));

        $priorPayments = DB::table('accounting_vendor_payments')
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->where('status', 'posted')
            ->where('payment_date', '<', $fromDate)
            ->value(DB::raw('COALESCE(SUM(amount), 0)'));

        $openingBalance = bcsub((string) $priorBills, (string) $priorPayments, 6);

        $bills = DB::table('accounting_purchase_bills')
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['issued', 'posted'])
            ->whereBetween('bill_date', [$fromDate, $toDate])
            ->orderBy('bill_date')
            ->orderBy('id')
            ->get([
                'bill_number',
                'bill_date',
                'due_date',
                'grand_total',
            ]);

        $payments = DB::table('accounting_vendor_payments')
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->where('status', 'posted')
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get([
                'payment_number',
                'payment_date',
                'amount',
            ]);

        $entries = collect();

        foreach ($bills as $bill) {
            $entries->push([
                'type' => 'bill',
                'date' => $bill->bill_date,
                'reference' => $bill->bill_number,
                'description' => 'Purchase bill',
                'debit' => '0.000000',
                'credit' => $bill->grand_total,
            ]);
        }

        foreach ($payments as $payment) {
            $entries->push([
                'type' => 'payment',
                'date' => $payment->payment_date,
                'reference' => $payment->payment_number,
                'description' => 'Vendor payment',
                'debit' => $payment->amount,
                'credit' => '0.000000',
            ]);
        }

        $entries = $entries
            ->sortBy('date')
            ->values();

        $balance = $openingBalance;

        $entries = $entries->map(function (array $entry) use (&$balance) {
            $balance = bcadd(
                $balance,
                bcsub(
                    (string) $entry['credit'],
                    (string) $entry['debit'],
                    6
                ),
                6
            );

            $entry['balance'] = $balance;

            return $entry;
        });

        return [
            'vendor' => [
                'id' => $vendor->public_id,
                'code' => $vendor->vendor_code,
                'name' => $vendor->name,
            ],
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'opening_balance' => number_format((float) $openingBalance, 6, '.', ''),
            'entries' => $entries->all(),
            'closing_balance' => number_format((float) $balance, 6, '.', ''),
        ];
    }
}
