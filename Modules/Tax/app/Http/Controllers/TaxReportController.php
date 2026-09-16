<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Tax\Models\TaxRate;

class TaxReportController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $reportType = $request->query('report_type', 'summary');

        $invoices = SalesInvoice::where('tenant_id', $tenantId)->with('customer')->get();
        $bills = PurchaseBill::where('tenant_id', $tenantId)->with('vendor')->get();

        $grossSales = (float) $invoices->sum('subtotal');
        $outputTax = (float) $invoices->sum('tax_total');
        $grossPurchases = (float) $bills->sum('subtotal');
        $inputTax = (float) $bills->sum('tax_total');
        $netTax = $outputTax - $inputTax;

        $summaryData = [
            'gross_sales_taxable' => $grossSales,
            'output_tax_collected' => $outputTax,
            'gross_purchases_taxable' => $grossPurchases,
            'input_tax_claimable' => $inputTax,
            'net_tax_payable' => $netTax,
            'zero_rated_sales' => 0.00,
            'exempt_sales' => 0.00,
            'total_gross_turnover' => (float) $invoices->sum('grand_total'),
        ];

        $taxRates = TaxRate::where('tenant_id', $tenantId)->get();
        $byRateBreakdown = $taxRates->map(function ($tr) use ($grossSales, $outputTax, $grossPurchases, $inputTax) {
            $isStandard = (float) $tr->rate > 0;

            return [
                'rate_name' => $tr->name,
                'rate_percentage' => (float) $tr->rate,
                'sales_taxable_amount' => $isStandard ? $grossSales : 0.00,
                'output_tax' => $isStandard ? $outputTax : 0.00,
                'purchases_taxable_amount' => $isStandard ? $grossPurchases : 0.00,
                'input_tax' => $isStandard ? $inputTax : 0.00,
                'net_tax' => $isStandard ? ($outputTax - $inputTax) : 0.00,
            ];
        })->toArray();

        $outputTransactions = $invoices->map(function ($inv) {
            return [
                'id' => $inv->id,
                'date' => $inv->invoice_date?->toDateString() ?? $inv->created_at?->toDateString() ?? now()->toDateString(),
                'document_number' => $inv->invoice_number,
                'customer_name' => $inv->customer?->name ?? 'Walk-in Customer',
                'customer_tax_id' => $inv->customer?->tax_number ?? '—',
                'channel' => 'Commercial Invoice',
                'taxable_base' => (float) $inv->subtotal,
                'tax_rate_applied' => (float) $inv->subtotal > 0 ? number_format(((float) $inv->tax_total / (float) $inv->subtotal) * 100, 2).'%' : '0.00%',
                'tax_amount' => (float) $inv->tax_total,
                'total_amount' => (float) $inv->grand_total,
                'tax_category' => 'Standard Rateable',
            ];
        })->toArray();

        $inputTransactions = $bills->map(function ($bill) {
            return [
                'id' => $bill->id,
                'date' => $bill->bill_date?->toDateString() ?? $bill->created_at?->toDateString() ?? now()->toDateString(),
                'document_number' => $bill->bill_number,
                'vendor_name' => $bill->vendor?->name ?? 'Vendor Partner',
                'vendor_tax_id' => $bill->vendor?->tax_number ?? '—',
                'taxable_base' => (float) $bill->subtotal,
                'tax_rate_applied' => (float) $bill->subtotal > 0 ? number_format(((float) $bill->tax_total / (float) $bill->subtotal) * 100, 2).'%' : '0.00%',
                'tax_amount' => (float) $bill->tax_total,
                'is_claimable' => true,
                'total_amount' => (float) $bill->grand_total,
            ];
        })->toArray();

        return Inertia::render('Tax/Reports/Index', [
            'reportType' => $reportType,
            'summaryData' => $summaryData,
            'byRateBreakdown' => $byRateBreakdown,
            'outputTransactions' => $outputTransactions,
            'inputTransactions' => $inputTransactions,
            'filters' => $request->only(['from_date', 'to_date', 'branch_id', 'tax_rate_id', 'report_type']),
        ]);
    }
}
