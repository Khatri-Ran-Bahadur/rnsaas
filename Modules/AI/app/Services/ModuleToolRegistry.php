<?php

namespace Modules\AI\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Inventory\Models\InventoryItem;
use Modules\POS\Models\PosOrder;
use Modules\Tenancy\Models\Tenant;

class ModuleToolRegistry
{
    /**
     * Get the JSON schema declarations of all available tools for LLM Function Calling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolDeclarations(): array
    {
        return [
            [
                'name' => 'get_sales_overview',
                'description' => 'Get a summary of sales revenue, invoice totals, paid vs unpaid amounts, and recent invoices for the current tenant company.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Number of recent days to summarize (e.g. 7, 30, 90). Default is 30 days.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_unpaid_invoices',
                'description' => 'Get a list of unpaid or overdue sales invoices with customer details, amounts, and due dates.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of overdue/unpaid invoices to fetch (default 10).',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_inventory_status',
                'description' => 'Get current inventory stock status including low-stock alerts, out-of-stock items, and inventory valuation.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'filter' => [
                            'type' => 'string',
                            'description' => 'Filter options: "low_stock", "out_of_stock", or "all". Default is "low_stock".',
                            'enum' => ['low_stock', 'out_of_stock', 'all'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_pos_sales_summary',
                'description' => 'Get POS (Point of Sale) orders, today\'s cash and card/online collections, and ticket counts.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => [
                            'type' => 'string',
                            'description' => 'Date in YYYY-MM-DD format (leave empty for today).',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_financial_summary',
                'description' => 'Get financial profit and loss summary comparing sales revenue against purchase bill expenses.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => [
                            'type' => 'string',
                            'description' => 'Period: "this_month", "last_month", or "this_year". Default is "this_month".',
                            'enum' => ['this_month', 'last_month', 'this_year'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Execute a tool safely scoped to the current tenant.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function execute(string $toolName, array $parameters, Tenant $tenant): array
    {
        return match ($toolName) {
            'get_sales_overview' => $this->getSalesOverview($tenant, $parameters),
            'get_unpaid_invoices' => $this->getUnpaidInvoices($tenant, $parameters),
            'get_inventory_status' => $this->getInventoryStatus($tenant, $parameters),
            'get_pos_sales_summary' => $this->getPosSalesSummary($tenant, $parameters),
            'get_financial_summary' => $this->getFinancialSummary($tenant, $parameters),
            default => [
                'error' => "Unknown tool '{$toolName}'",
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function getSalesOverview(Tenant $tenant, array $params): array
    {
        $days = (int) ($params['days'] ?? 30);
        $since = Carbon::now()->subDays($days)->startOfDay();

        $invoicesQuery = SalesInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $since);

        $totalInvoices = (clone $invoicesQuery)->count();
        $totalRevenue = (float) (clone $invoicesQuery)->whereIn('status', ['paid', 'partially_paid'])->sum('grand_total');
        $unpaidAmount = (float) (clone $invoicesQuery)->whereIn('status', ['unpaid', 'sent', 'overdue'])->sum('grand_total');

        $recentInvoices = (clone $invoicesQuery)
            ->with(['customer:id,name,email'])
            ->latest()
            ->limit(5)
            ->get(['id', 'customer_id', 'invoice_number', 'grand_total', 'status', 'due_date'])
            ->map(fn ($inv) => [
                'invoice_number' => $inv->invoice_number,
                'customer' => $inv->customer?->name ?? 'Guest',
                'amount' => (float) $inv->grand_total,
                'status' => $inv->status,
                'due_date' => $inv->due_date?->toDateString(),
            ])
            ->toArray();

        return [
            'period_days' => $days,
            'currency' => $tenant->currency ?? 'NPR',
            'total_invoices_count' => $totalInvoices,
            'total_paid_revenue' => $totalRevenue,
            'total_unpaid_amount' => $unpaidAmount,
            'recent_invoices' => $recentInvoices,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function getUnpaidInvoices(Tenant $tenant, array $params): array
    {
        $limit = (int) ($params['limit'] ?? 10);

        $unpaid = SalesInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['unpaid', 'sent', 'overdue', 'partially_paid'])
            ->with(['customer:id,name,phone,email'])
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get(['id', 'customer_id', 'invoice_number', 'grand_total', 'status', 'due_date'])
            ->map(fn ($inv) => [
                'invoice_number' => $inv->invoice_number,
                'customer' => $inv->customer?->name ?? 'Unknown',
                'phone' => $inv->customer?->phone ?? 'N/A',
                'amount' => (float) $inv->grand_total,
                'status' => $inv->status,
                'due_date' => $inv->due_date?->toDateString(),
                'is_overdue' => $inv->due_date && $inv->due_date->isPast(),
            ])
            ->toArray();

        return [
            'currency' => $tenant->currency ?? 'NPR',
            'unpaid_invoices_count' => count($unpaid),
            'invoices' => $unpaid,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function getInventoryStatus(Tenant $tenant, array $params): array
    {
        $filter = $params['filter'] ?? 'low_stock';

        $baseQuery = InventoryItem::query()->where('tenant_id', $tenant->id);

        $totalItems = (clone $baseQuery)->count();
        $totalValue = (float) (clone $baseQuery)->sum(DB::raw('on_hand_stock * cost_price'));

        $query = clone $baseQuery;

        if ($filter === 'low_stock') {
            $query->whereColumn('on_hand_stock', '<=', 'reorder_point')->where('on_hand_stock', '>', 0);
        } elseif ($filter === 'out_of_stock') {
            $query->where('on_hand_stock', '<=', 0);
        }

        $items = $query->limit(15)->get([
            'name', 'sku', 'on_hand_stock', 'reorder_point', 'cost_price', 'selling_price',
        ])->map(fn ($item) => [
            'name' => $item->name,
            'sku' => $item->sku,
            'stock_on_hand' => (float) $item->on_hand_stock,
            'reorder_point' => (float) $item->reorder_point,
            'cost_price' => (float) $item->cost_price,
            'selling_price' => (float) $item->selling_price,
        ])->toArray();

        return [
            'total_catalog_items' => $totalItems,
            'total_inventory_valuation' => $totalValue,
            'filter_applied' => $filter,
            'items_count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function getPosSalesSummary(Tenant $tenant, array $params): array
    {
        $dateStr = $params['date'] ?? Carbon::today()->toDateString();
        $date = Carbon::parse($dateStr);

        $orders = PosOrder::query()
            ->where('tenant_id', $tenant->id)
            ->whereDate('created_at', $date)
            ->get(['id', 'order_number', 'grand_total', 'paid_amount', 'payment_method', 'status', 'created_at']);

        $totalSales = (float) $orders->sum('grand_total');
        $cashSales = (float) $orders->where('payment_method', 'cash')->sum('grand_total');
        $cardOrOnlineSales = (float) $orders->where('payment_method', '!=', 'cash')->sum('grand_total');

        return [
            'date' => $dateStr,
            'currency' => $tenant->currency ?? 'NPR',
            'total_orders' => $orders->count(),
            'total_sales' => $totalSales,
            'cash_sales' => $cashSales,
            'card_or_online_sales' => $cardOrOnlineSales,
            'recent_orders' => $orders->take(5)->map(fn ($o) => [
                'order_number' => $o->order_number,
                'amount' => (float) $o->grand_total,
                'method' => $o->payment_method,
                'status' => $o->status,
            ])->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function getFinancialSummary(Tenant $tenant, array $params): array
    {
        $period = $params['period'] ?? 'this_month';

        $start = match ($period) {
            'last_month' => Carbon::now()->subMonth()->startOfMonth(),
            'this_year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $end = match ($period) {
            'last_month' => Carbon::now()->subMonth()->endOfMonth(),
            default => Carbon::now()->endOfMonth(),
        };

        $salesRevenue = (float) SalesInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'partially_paid', 'sent', 'unpaid'])
            ->sum('grand_total');

        $purchaseExpenses = (float) PurchaseBill::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('grand_total');

        $netProfit = $salesRevenue - $purchaseExpenses;

        return [
            'period' => $period,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'currency' => $tenant->currency ?? 'NPR',
            'total_sales_revenue' => $salesRevenue,
            'total_purchase_expenses' => $purchaseExpenses,
            'net_profit_or_loss' => $netProfit,
            'margin_percentage' => $salesRevenue > 0 ? round(($netProfit / $salesRevenue) * 100, 2) : 0,
        ];
    }
}
