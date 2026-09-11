<?php

namespace Modules\Accounting\Application\Actions\Reports;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Customer;

final class GenerateCustomerStatementAction
{
    public function execute(
        Customer $customer,
        string $fromDate,
        string $toDate,
        CurrentTenant $currentTenant,
    ): array {
        abort_unless(
            $customer->tenant_id === $currentTenant->id(),
            404
        );

        $invoices = DB::table('accounting_sales_invoices')
            ->where('tenant_id', $currentTenant->id())
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'posted'])
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get([
                'id',
                'public_id',
                'invoice_number',
                'invoice_date',
                'due_date',
                'grand_total',
            ]);

        $payments = DB::table('accounting_customer_payments')
            ->where('tenant_id', $currentTenant->id())
            ->where('customer_id', $customer->id)
            ->where('status', 'posted')
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get([
                'id',
                'public_id',
                'payment_number',
                'payment_date',
                'amount',
            ]);

        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'type' => 'invoice',
                'date' => $invoice->invoice_date,
                'reference' => $invoice->invoice_number,
                'description' => 'Sales invoice',
                'debit' => $invoice->grand_total,
                'credit' => '0.000000',
            ]);
        }

        foreach ($payments as $payment) {
            $entries->push([
                'type' => 'payment',
                'date' => $payment->payment_date,
                'reference' => $payment->payment_number,
                'description' => 'Customer payment',
                'debit' => '0.000000',
                'credit' => $payment->amount,
            ]);
        }

        $entries = $entries
            ->sortBy([
                ['date', 'asc'],
            ])
            ->values();

        $balance = '0.000000';

        $entries = $entries->map(function (array $entry) use (&$balance) {
            $balance = bcadd(
                $balance,
                bcsub(
                    (string) $entry['debit'],
                    (string) $entry['credit'],
                    6
                ),
                6
            );

            $entry['balance'] = $balance;

            return $entry;
        });

        return [
            'customer' => [
                'id' => $customer->public_id,
                'name' => $customer->name,
                'code' => $customer->customer_code,
            ],
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'entries' => $entries->values()->all(),
            'closing_balance' => $balance,
        ];
    }
}
