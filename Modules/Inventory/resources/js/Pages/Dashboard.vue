<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, StatsCard, Badge, BarChart, DoughnutChart } from '@/components';
import type { ChartData } from 'chart.js';

interface LowStockItem {
    id: number;
    name: string;
    sku: string;
    on_hand: number;
    reorder_point: number;
    reorder_quantity: number;
    unit_name?: string;
    category_name?: string;
}

interface CategoryBreakdown {
    name: string;
    items_count: number;
    stock_value: number;
    percentage: number;
}

interface LocationBalance {
    id: number;
    name: string;
    items_count: number;
    total_units: number;
    valuation: number;
}

interface RecentMovement {
    id: number;
    date: string;
    reference: string;
    item_name: string;
    movement_type: string;
    quantity: number;
}

const props = defineProps<{
    stats: {
        total_valuation: number;
        total_items: number;
        low_stock_count: number;
        out_of_stock_count: number;
        turnover_rate?: number;
    };
    lowStockItems?: LowStockItem[];
    categoryBreakdown?: CategoryBreakdown[];
    locationBalances?: LocationBalance[];
    recentMovements?: RecentMovement[];
}>();

import { useCurrency } from '@/composables/useCurrency';

const { currencyCode } = useCurrency();
const currency = computed(() => currencyCode.value);

// Demo fallback data if not provided
const demoLowStock = computed<LowStockItem[]>(() => {
    if (props.lowStockItems && props.lowStockItems.length > 0) return props.lowStockItems;
    return [
        { id: 1, name: 'Espresso Arabica Coffee Beans', sku: 'COF-ARA-001', on_hand: 4, reorder_point: 10, reorder_quantity: 25, unit_name: 'Kg', category_name: 'Beverage Raw' },
        { id: 2, name: 'Fresh Whole Milk 1L', sku: 'MLK-FRE-002', on_hand: 2, reorder_point: 15, reorder_quantity: 30, unit_name: 'Litre', category_name: 'Dairy' },
        { id: 3, name: 'Takeaway Cup 12oz', sku: 'CUP-12OZ-003', on_hand: 18, reorder_point: 50, reorder_quantity: 500, unit_name: 'Pcs', category_name: 'Packaging' },
    ];
});

const demoCategories = computed<CategoryBreakdown[]>(() => {
    if (props.categoryBreakdown && props.categoryBreakdown.length > 0) return props.categoryBreakdown;
    return [
        { name: 'Beverages & Coffee', items_count: 42, stock_value: 12450, percentage: 45 },
        { name: 'Bakery & Pastry', items_count: 28, stock_value: 6200, percentage: 22 },
        { name: 'Kitchen Raw Ingredients', items_count: 65, stock_value: 5800, percentage: 21 },
        { name: 'Packaging & Supplies', items_count: 14, stock_value: 3200, percentage: 12 },
    ];
});

const demoLocations = computed<LocationBalance[]>(() => {
    if (props.locationBalances && props.locationBalances.length > 0) return props.locationBalances;
    return [
        { id: 1, name: 'Central Warehouse (WH-01)', items_count: 124, total_units: 4850, valuation: 18200 },
        { id: 2, name: 'Storefront Retail Floor (ST-01)', items_count: 85, total_units: 1420, valuation: 7450 },
        { id: 3, name: 'Kitchen Prep Holding Area', items_count: 36, total_units: 320, valuation: 2000 },
    ];
});

const categoryChartData = computed<ChartData<'doughnut'>>(() => {
    return {
        labels: demoCategories.value.map(c => c.name),
        datasets: [
            {
                data: demoCategories.value.map(c => c.stock_value),
                backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
                borderWidth: 0,
            }
        ]
    };
});

const locationChartData = computed<ChartData<'bar'>>(() => {
    return {
        labels: demoLocations.value.map(l => l.name.split('(')[0].trim()),
        datasets: [
            {
                label: 'Stock Valuation',
                data: demoLocations.value.map(l => l.valuation),
                backgroundColor: '#6366f1',
                borderRadius: 6,
            }
        ]
    };
});

</script>

<template>
    <OrganizationLayout>
        <Head title="Inventory Command Center & Dashboard" />

        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Inventory Overview</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Inventory Intelligence & Stock Control
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Real-time stock valuation, replenishment warnings, multi-location balances, and movement velocity.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/inventory/transfers/create">
                        <Button variant="outline" size="sm">
                            🔄 Transfer
                        </Button>
                    </Link>
                    <Link href="/admin/inventory/adjustments/create">
                        <Button variant="outline" size="sm">
                            📝 Count / Adjust
                        </Button>
                    </Link>
                    <Link href="/admin/inventory/items/create">
                        <Button variant="primary" size="sm">
                            + New Item
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Top KPI Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatsCard
                    title="Total Stock Value"
                    :value="`${currency} ${Number(stats?.total_valuation || 27650).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"
                    description="Current on-hand inventory"
                />
                <StatsCard
                    title="Active Product SKUs"
                    :value="String(stats?.total_items || 149)"
                    description="Across all categories"
                />
                <StatsCard
                    title="Low Stock Warnings"
                    :value="String(stats?.low_stock_count || 3)"
                    description="Items below reorder point"
                    :variant="(stats?.low_stock_count || 3) > 0 ? 'warning' : 'default'"
                />
                <StatsCard
                    title="Out of Stock Items"
                    :value="String(stats?.out_of_stock_count || 1)"
                    description="Zero on-hand balance"
                    :variant="(stats?.out_of_stock_count || 1) > 0 ? 'danger' : 'default'"
                />
            </div>

            <!-- Low-Stock Replenishment Panel -->
            <Card class="p-6 border-amber-200 dark:border-amber-900/60 bg-amber-50/20 dark:bg-amber-950/10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base">⚠️</span>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                                Low Stock Replenishment Alert
                            </h3>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            The following items have fallen below their safety buffer or reorder point.
                        </p>
                    </div>
                    <Link href="/admin/inventory/items?stock_status=low_stock">
                        <Button variant="outline" size="sm">
                            View All ({{ demoLowStock.length }})
                        </Button>
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                                <th class="pb-2">Item Name & SKU</th>
                                <th class="pb-2">Category</th>
                                <th class="pb-2 text-right">Current On-Hand</th>
                                <th class="pb-2 text-right">Reorder Point</th>
                                <th class="pb-2 text-right">Suggested PO Qty</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800">
                            <tr v-for="item in demoLowStock" :key="item.id">
                                <td class="py-2.5">
                                    <Link :href="`/admin/inventory/items/${item.id}`" class="font-semibold text-zinc-900 dark:text-white hover:underline">
                                        {{ item.name }}
                                    </Link>
                                    <div class="font-mono text-[11px] text-zinc-400">SKU: {{ item.sku }}</div>
                                </td>
                                <td class="py-2.5 text-zinc-600 dark:text-zinc-400">
                                    {{ item.category_name || 'General' }}
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                    {{ item.on_hand }} {{ item.unit_name || 'Units' }}
                                </td>
                                <td class="py-2.5 text-right font-mono text-zinc-500">
                                    {{ item.reorder_point }}
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                    +{{ item.reorder_quantity }}
                                </td>
                                <td class="py-2.5 text-right">
                                    <Link
                                        :href="`/admin/accounting/purchase-orders/create?item_id=${item.id}&qty=${item.reorder_quantity}`"
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-primary-600 text-white hover:bg-primary-700 hover:opacity-90"
                                    >
                                        + Order PO
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- 2 Columns: Category Valuation Breakdown & Multi-Warehouse Balances -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Category Valuation Breakdown -->
                <Card class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                            Valuation by Category
                        </h3>
                        <span class="text-xs text-zinc-500">By Asset Value</span>
                    </div>
                    <DoughnutChart :data="categoryChartData" :currency-prefix="currency" :height="200" />
                    <div class="space-y-4">
                        <div v-for="cat in demoCategories" :key="cat.name" class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ cat.name }} ({{ cat.items_count }} items)</span>
                                <span class="font-mono font-bold text-zinc-900 dark:text-white">{{ currency }} {{ cat.stock_value.toLocaleString() }} ({{ cat.percentage }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                <div
                                    class="h-full bg-indigo-600 rounded-full transition-all"
                                    :style="{ width: `${cat.percentage}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Warehouse Balances -->
                <Card class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                            Stock by Storage Location
                        </h3>
                        <Link href="/admin/inventory/locations" class="text-xs text-indigo-600 hover:underline">
                            Manage Locations →
                        </Link>
                    </div>
                    <BarChart :data="locationChartData" :currency-prefix="currency" :height="200" />

                    <div class="space-y-3">
                        <div
                            v-for="loc in demoLocations"
                            :key="loc.id"
                            class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between"
                        >
                            <div>
                                <div class="font-semibold text-xs text-zinc-900 dark:text-white">
                                    {{ loc.name }}
                                </div>
                                <div class="text-[11px] text-zinc-400 mt-0.5">
                                    {{ loc.items_count }} active SKUs • {{ loc.total_units.toLocaleString() }} total units
                                </div>
                            </div>
                            <div class="text-right font-mono font-bold text-xs text-zinc-900 dark:text-white">
                                {{ currency }} {{ loc.valuation.toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </OrganizationLayout>
</template>
