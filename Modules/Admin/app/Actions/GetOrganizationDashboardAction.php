<?php

namespace Modules\Admin\Actions;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Admin\DTOs\OrganizationDashboardData;
use Modules\Inventory\Models\InventoryItem;
use Modules\POS\Models\PosOrder;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Models\Branch;
use Modules\Tenancy\Models\Department;
use Modules\Tenancy\Models\TenantStaff;

final class GetOrganizationDashboardAction
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(): OrganizationDashboardData
    {
        $tenant = $this->currentTenant->get();

        $membershipCounts = $tenant->users()
            ->select('tenant_user.status')
            ->get()
            ->groupBy(fn ($user) => $user->pivot->status->value ?? $user->pivot->status)
            ->map(fn ($users) => $users->count());

        $subscription = $this->modelTableExists(TenantSubscription::class)
            ? TenantSubscription::query()
                ->with('plan')
                ->where('tenant_id', $tenant->id)
                ->latest('id')
                ->first()
            : null;

        // 1. Accounting & Financials
        $income = 0.0;
        $expenses = 0.0;
        $receivablesTotal = 0.0;
        $receivablesCount = 0;
        $payablesTotal = 0.0;
        $payablesCount = 0;
        $monthLabels = [];
        $monthlyIncome = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
        $monthlyExpense = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

        for ($i = 5; $i >= 0; $i--) {
            $monthLabels[] = now()->subMonths($i)->format('M');
        }

        $getStatus = fn ($model) => $model->status instanceof \BackedEnum ? $model->status->value : (string) $model->status;

        if ($this->modelTableExists(SalesInvoice::class)) {
            $invoices = SalesInvoice::where('tenant_id', $tenant->id)->get();
            $validInvoices = $invoices->filter(fn ($inv) => ! in_array($getStatus($inv), ['void', 'draft']));
            $income = (float) $validInvoices->sum('grand_total');

            $unpaidInvoices = $invoices->filter(fn ($inv) => in_array($getStatus($inv), ['issued', 'unpaid', 'partially_paid', 'sent', 'overdue'])
            );
            $receivablesTotal = (float) $unpaidInvoices->sum('grand_total');
            $receivablesCount = $unpaidInvoices->count();

            for ($i = 5; $i >= 0; $i--) {
                $monthDate = now()->subMonths($i);
                $monthlyIncome[5 - $i] = (float) $invoices->filter(fn ($inv) => $inv->created_at && $inv->created_at->format('Y-m') === $monthDate->format('Y-m') && ! in_array($getStatus($inv), ['void', 'draft'])
                )->sum('grand_total');
            }
        }

        if ($this->modelTableExists(PurchaseBill::class)) {
            $bills = PurchaseBill::where('tenant_id', $tenant->id)->get();
            $validBills = $bills->filter(fn ($b) => ! in_array($getStatus($b), ['void', 'draft']));
            $expenses = (float) $validBills->sum('grand_total');

            $unpaidBills = $bills->filter(fn ($b) => in_array($getStatus($b), ['issued', 'unpaid', 'partially_paid', 'pending', 'overdue'])
            );
            $payablesTotal = (float) $unpaidBills->sum('grand_total');
            $payablesCount = $unpaidBills->count();

            for ($i = 5; $i >= 0; $i--) {
                $monthDate = now()->subMonths($i);
                $monthlyExpense[5 - $i] = (float) $bills->filter(fn ($b) => $b->created_at && $b->created_at->format('Y-m') === $monthDate->format('Y-m') && ! in_array($getStatus($b), ['void', 'draft'])
                )->sum('grand_total');
            }
        }

        $netProfit = $income - $expenses;
        $profitMargin = $income > 0 ? round(($netProfit / $income) * 100, 1) : 0.0;

        // 2. POS Operations & Sales
        $todaySales = 0.0;
        $todayOrdersCount = 0;
        $monthSales = 0.0;
        $totalOrdersCount = 0;
        $avgOrderValue = 0.0;
        $recentPosOrders = [];
        $paymentMethodsDistribution = ['cash' => 0, 'card' => 0, 'qr' => 0, 'other' => 0];

        if ($this->modelTableExists(PosOrder::class)) {
            $posOrders = PosOrder::where('tenant_id', $tenant->id)->get();
            $todayOrders = $posOrders->filter(fn ($o) => $o->created_at && $o->created_at->isToday());
            $todaySales = (float) $todayOrders->sum('grand_total');
            $todayOrdersCount = $todayOrders->count();

            $monthSales = (float) $posOrders->filter(fn ($o) => $o->created_at && $o->created_at->isCurrentMonth())->sum('grand_total');
            $totalOrdersCount = $posOrders->count();
            $avgOrderValue = $totalOrdersCount > 0 ? round($posOrders->sum('grand_total') / $totalOrdersCount, 2) : 0.0;

            foreach ($posOrders as $order) {
                $method = strtolower((string) $order->payment_method);
                if (str_contains($method, 'cash')) {
                    $paymentMethodsDistribution['cash']++;
                } elseif (str_contains($method, 'card')) {
                    $paymentMethodsDistribution['card']++;
                } elseif (str_contains($method, 'qr') || str_contains($method, 'wallet')) {
                    $paymentMethodsDistribution['qr']++;
                } else {
                    $paymentMethodsDistribution['other']++;
                }
            }

            $recentPosOrders = $posOrders->sortByDesc('created_at')->take(5)->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name ?: 'Walk-in Customer',
                'grand_total' => (float) $o->grand_total,
                'payment_method' => $o->payment_method ?: 'Cash',
                'status' => $o->status ?: 'completed',
                'created_at' => $o->created_at?->format('M d, H:i') ?? '',
            ])->values()->all();
        }

        // 3. Inventory & Stock Status
        $totalSkus = 0;
        $totalStockUnits = 0;
        $totalStockValue = 0.0;
        $outOfStockCount = 0;
        $lowStockCount = 0;
        $urgentStockAlerts = [];

        if ($this->modelTableExists(InventoryItem::class)) {
            $items = InventoryItem::where('tenant_id', $tenant->id)->get();
            $totalSkus = $items->count();
            $totalStockUnits = (int) $items->sum('on_hand_stock');
            $totalStockValue = (float) $items->reduce(fn ($acc, $item) => $acc + ((float) $item->on_hand_stock * (float) $item->cost_price), 0.0);
            $outOfStockCount = $items->filter(fn ($i) => (float) $i->on_hand_stock <= 0)->count();
            $lowStockCount = $items->filter(fn ($i) => (float) $i->on_hand_stock > 0 && (float) $i->on_hand_stock <= (float) $i->reorder_point)->count();

            $urgentStockAlerts = $items->filter(fn ($i) => (float) $i->on_hand_stock <= (float) $i->reorder_point)
                ->sortBy('on_hand_stock')
                ->take(5)
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'sku' => $i->sku,
                    'on_hand_stock' => (float) $i->on_hand_stock,
                    'reorder_point' => (float) $i->reorder_point,
                    'cost_price' => (float) $i->cost_price,
                    'selling_price' => (float) $i->selling_price,
                    'is_out_of_stock' => (float) $i->on_hand_stock <= 0,
                ])->values()->all();
        }

        // 4. Operations (Branches, Departments, Staff)
        $staffCount = $this->modelTableExists(TenantStaff::class)
            ? TenantStaff::where('tenant_id', $tenant->id)->count()
            : 0;
        $branchesCount = $this->modelTableExists(Branch::class)
            ? Branch::where('tenant_id', $tenant->id)->count()
            : 1;
        $departmentsCount = $this->modelTableExists(Department::class)
            ? Department::where('tenant_id', $tenant->id)->count()
            : 0;

        return new OrganizationDashboardData(
            tenant: [
                'public_id' => $tenant->public_id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'industry' => $tenant->industry,
                'status' => $tenant->status instanceof \BackedEnum
                    ? $tenant->status->value
                    : (string) $tenant->status,
                'country_code' => $tenant->country_code,
                'timezone' => $tenant->timezone,
                'locale' => $tenant->locale,
                'currency' => $tenant->currency,
            ],
            members: [
                'total' => $membershipCounts->sum(),
                'active' => $membershipCounts->get(
                    TenantMembershipStatus::Active->value,
                    0,
                ),
                'invited' => $membershipCounts->get(
                    TenantMembershipStatus::Invited->value,
                    0,
                ),
                'suspended' => $membershipCounts->get(
                    TenantMembershipStatus::Suspended->value,
                    0,
                ),
                'revoked' => $membershipCounts->get(
                    TenantMembershipStatus::Revoked->value,
                    0,
                ),
            ],
            subscription: $subscription === null
                ? [
                    'exists' => false,
                    'status' => null,
                    'plan' => null,
                    'current_period_ends_at' => null,
                    'trial_ends_at' => null,
                ]
                : [
                    'exists' => true,
                    'status' => $subscription->status instanceof \BackedEnum
                        ? $subscription->status->value
                        : (string) $subscription->status,
                    'plan' => $subscription->plan?->name,
                    'current_period_ends_at' => $subscription
                        ->current_period_ends_at
                        ?->toIso8601String(),
                    'trial_ends_at' => $subscription->trial_ends_at
                        ?->toIso8601String(),
                ],
            accounting: [
                'total_income' => $income,
                'total_expenses' => $expenses,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
                'receivables_total' => $receivablesTotal,
                'receivables_count' => $receivablesCount,
                'payables_total' => $payablesTotal,
                'payables_count' => $payablesCount,
                'monthly_labels' => $monthLabels,
                'monthly_income' => $monthlyIncome,
                'monthly_expense' => $monthlyExpense,
            ],
            pos: [
                'today_sales' => $todaySales,
                'today_orders_count' => $todayOrdersCount,
                'month_sales' => $monthSales,
                'total_orders' => $totalOrdersCount,
                'average_order_value' => $avgOrderValue,
                'payment_methods' => $paymentMethodsDistribution,
                'recent_orders' => $recentPosOrders,
            ],
            inventory: [
                'total_skus' => $totalSkus,
                'total_stock_units' => $totalStockUnits,
                'total_stock_value' => $totalStockValue,
                'out_of_stock_count' => $outOfStockCount,
                'low_stock_count' => $lowStockCount,
                'urgent_items' => $urgentStockAlerts,
            ],
            operations: [
                'staff_count' => $staffCount,
                'branches_count' => $branchesCount,
                'departments_count' => $departmentsCount,
            ],
        );
    }

    /**
     * @param  class-string  $class
     */
    private function modelTableExists(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $model = new $class;

        if (! $model instanceof Model) {
            return false;
        }

        return Schema::hasTable($model->getTable());
    }
}
