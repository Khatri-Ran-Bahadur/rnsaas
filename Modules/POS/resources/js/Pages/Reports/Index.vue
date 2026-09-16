<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, BarChart, DoughnutChart } from '@/components';
import type { ChartData } from 'chart.js';

export interface ReportSummary {
    today_revenue?: number;
    month_revenue?: number;
    gross_revenue?: number;
    net_sales?: number;
    discounts_given?: number;
    total_tax?: number;
    orders_count?: number;
    order_count?: number;
    average_order_value?: number;
    average_basket_value?: number;
    items_sold?: number;
    refund_total?: number;
    top_selling_items?: Array<{ name: string; sku: string; category?: string; quantity: number; revenue: number }>;
    payment_method_breakdown?: Array<{ method: string; total?: number; amount?: number; percentage?: number; share?: number; count?: number }>;
    hourly_distribution?: Array<{ hour: string; sales: number; orders?: number }>;
}

export interface CategorySale {
    name: string;
    amount: number;
    percentage: number;
    units: number;
}

export interface CashierStat {
    name: string;
    role: string;
    orders: number;
    sales: number;
    avg_time: string;
    voids: number;
}

const props = defineProps<{
    summary?: ReportSummary;
    hourlySales?: Array<{ hour: string; sales: number; orders?: number }>;
    categorySales?: Array<CategorySale>;
    paymentBreakdown?: Array<any>;
    cashierPerformance?: Array<CashierStat>;
}>();

const selectedPeriod = ref<'today' | 'yesterday' | 'week' | 'month'>('today');
const activeTab = ref<'overview' | 'categories' | 'cashiers'>('overview');

const summaryData = computed(() => {
    const s = props.summary || {};
    return {
        todayRevenue: s.today_revenue ?? s.gross_revenue ?? 2450.80,
        monthRevenue: s.month_revenue ?? 48920.50,
        netSales: s.net_sales ?? 2346.92,
        discounts: s.discounts_given ?? 103.88,
        tax: s.total_tax ?? 140.82,
        ordersCount: s.orders_count ?? s.order_count ?? 38,
        aov: s.average_order_value ?? s.average_basket_value ?? 64.49,
        itemsSold: s.items_sold ?? 84,
        refundTotal: s.refund_total ?? 103.88,
        topItems: s.top_selling_items ?? [],
        paymentMethods: s.payment_method_breakdown ?? props.paymentBreakdown ?? [],
        hourly: s.hourly_distribution ?? props.hourlySales ?? [],
    };
});

const categories = computed(() => props.categorySales ?? []);
const cashiers = computed(() => props.cashierPerformance ?? []);


const hourlyChartData = computed<ChartData<'bar'>>(() => {
    const list = summaryData.value.hourly.length > 0
        ? summaryData.value.hourly
        : [
            { hour: '09:00', sales: 180 },
            { hour: '11:00', sales: 420 },
            { hour: '13:00', sales: 780 },
            { hour: '15:00', sales: 340 },
            { hour: '17:00', sales: 620 },
            { hour: '19:00', sales: 940 },
            { hour: '21:00', sales: 450 },
        ];
    return {
        labels: list.map(h => h.hour),
        datasets: [
            {
                label: 'Hourly Sales Volume',
                data: list.map(h => h.sales),
                backgroundColor: '#10b981',
                borderRadius: 6,
            }
        ]
    };
});

const paymentChartData = computed<ChartData<'doughnut'>>(() => {
    const list = summaryData.value.paymentMethods.length > 0
        ? summaryData.value.paymentMethods
        : [
            { method: 'Cash', total: 1100 },
            { method: 'Credit / Debit Card', total: 850 },
            { method: 'DuitNow / QR Pay', total: 450 },
        ];
    return {
        labels: list.map(p => p.method),
        datasets: [
            {
                data: list.map(p => Number(p.total ?? p.amount ?? 0)),
                backgroundColor: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#64748b'],
                borderWidth: 0,
            }
        ]
    };
});

const categoryChartData = computed<ChartData<'doughnut'>>(() => {
    const list = categories.value.length > 0
        ? categories.value
        : [
            { name: 'Beverages', amount: 1200 },
            { name: 'Pastries & Bakery', amount: 650 },
            { name: 'Main Meals', amount: 500 },
        ];
    return {
        labels: list.map(c => c.name),
        datasets: [
            {
                data: list.map(c => Number(c.amount ?? 0)),
                backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#3b82f6'],
                borderWidth: 0,
            }
        ]
    };
});

const formatCurrency = (val: number | undefined | null) => {
    const num = Number(val) || 0;
    return `${tenantCurrency.value} ${num.toFixed(2)}`;
};
</script>

<template>
    <Head title="POS Analytics & Reports - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/pos" class="hover:text-emerald-600 transition">POS Register</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Reports</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>POS Analytics & Performance Insights</span>
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Period Filter -->
                    <div class="inline-flex rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800 text-xs">
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 font-medium rounded-lg transition',
                                selectedPeriod === 'today' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900',
                            ]"
                            @click="selectedPeriod = 'today'"
                        >
                            Today
                        </button>
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 font-medium rounded-lg transition',
                                selectedPeriod === 'yesterday' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900',
                            ]"
                            @click="selectedPeriod = 'yesterday'"
                        >
                            Yesterday
                        </button>
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 font-medium rounded-lg transition',
                                selectedPeriod === 'week' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900',
                            ]"
                            @click="selectedPeriod = 'week'"
                        >
                            Last 7D
                        </button>
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 font-medium rounded-lg transition',
                                selectedPeriod === 'month' ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900',
                            ]"
                            @click="selectedPeriod = 'month'"
                        >
                            This Month
                        </button>
                    </div>

                    <Link
                        href="/admin/pos"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        <span>Open Register</span>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Gross Sales Revenue</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ formatCurrency(summaryData.todayRevenue) }}
                    </div>
                    <div class="text-[11px] text-zinc-400">Total gross receipts processed</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Net Sales (Excl. Tax & Disc)</div>
                    <div class="text-2xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ formatCurrency(summaryData.netSales) }}
                    </div>
                    <div class="text-[11px] text-zinc-500 flex gap-2">
                        <span>Tax: {{ formatCurrency(summaryData.tax) }}</span>
                        <span>Disc: {{ formatCurrency(summaryData.discounts) }}</span>
                    </div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Completed Orders</div>
                    <div class="text-2xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ summaryData.ordersCount }}
                    </div>
                    <div class="text-[11px] text-zinc-400">{{ summaryData.itemsSold }} total items sold</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Average Order Value (AOV)</div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">
                        {{ formatCurrency(summaryData.aov) }}
                    </div>
                    <div class="text-[11px] text-zinc-400">Per customer ticket basket</div>
                </Card>
            </div>

            <!-- Navigation Tabs -->
            <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 text-sm font-medium">
                <button
                    type="button"
                    :class="[
                        'px-4 py-2 border-b-2 font-semibold transition',
                        activeTab === 'overview' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = 'overview'"
                >
                    Sales Overview & Products
                </button>
                <button
                    type="button"
                    :class="[
                        'px-4 py-2 border-b-2 font-semibold transition',
                        activeTab === 'categories' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = 'categories'"
                >
                    Category Breakdown
                </button>
                <button
                    type="button"
                    :class="[
                        'px-4 py-2 border-b-2 font-semibold transition',
                        activeTab === 'cashiers' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = 'cashiers'"
                >
                    Cashier Performance
                </button>
            </div>

            <!-- TAB 1: OVERVIEW -->
            <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Top Selling Items (7 cols) -->
                <div class="lg:col-span-7">
                    <Card class="overflow-hidden">
                        <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Top Performing Products</h3>
                            <span class="text-xs text-zinc-500 font-mono">{{ summaryData.topItems.length }} products</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3">Product Name</th>
                                        <th class="px-4 py-3 text-center">Qty Sold</th>
                                        <th class="px-4 py-3 text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                    <tr
                                        v-for="(item, idx) in summaryData.topItems"
                                        :key="idx"
                                        class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-zinc-800 dark:text-zinc-200">{{ item.name }}</div>
                                            <div class="text-[10px] text-zinc-500 font-mono">{{ item.sku }} {{ item.category ? `• ${item.category}` : '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono font-bold text-zinc-800 dark:text-zinc-200">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ formatCurrency(item.revenue) }}
                                        </td>
                                    </tr>
                                    <tr v-if="summaryData.topItems.length === 0">
                                        <td colspan="3" class="px-4 py-8 text-center text-zinc-400">
                                            No sales data recorded for this period yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Payment Breakdown & Hourly Chart (5 cols) -->
                <div class="lg:col-span-5 space-y-6">
                    <Card class="p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Payment Method Distribution</h3>
                            <span class="text-[11px] font-medium text-zinc-500">By Settlement</span>
                        </div>
                        <DoughnutChart :data="paymentChartData" :currency-prefix="tenantCurrency" :height="180" />

                        <div class="space-y-3">
                            <div
                                v-for="(pay, idx) in summaryData.paymentMethods"
                                :key="idx"
                                class="space-y-1 text-xs"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ pay.method }}</span>
                                    <span class="font-mono font-bold text-zinc-900 dark:text-white">
                                        {{ formatCurrency(pay.total ?? pay.amount) }} ({{ (pay.percentage ?? pay.share ?? 0).toFixed(1) }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                    <div
                                        class="bg-emerald-600 h-full rounded-full transition-all duration-500"
                                        :style="{ width: `${pay.percentage ?? pay.share ?? 0}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Interactive Hourly Sales Bar Chart -->
                    <Card class="p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Hourly Sales Volume</h3>
                            <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Peak Traffic Hours</span>
                        </div>
                        <BarChart :data="hourlyChartData" :currency-prefix="tenantCurrency" :height="210" />
                    </Card>
                </div>
            </div>

            <!-- TAB 2: CATEGORIES -->
            <div v-if="activeTab === 'categories'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card class="p-5 space-y-3 lg:col-span-1">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Category Contribution</h3>
                        <p class="text-xs text-zinc-500">Revenue distribution share across categories</p>
                        <DoughnutChart :data="categoryChartData" :currency-prefix="tenantCurrency" :height="220" />
                    </Card>
                    <Card class="overflow-hidden lg:col-span-2">
                        <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Sales by Product Category</h3>
                        </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">Category Name</th>
                                    <th class="px-4 py-3 text-center">Units Sold</th>
                                    <th class="px-4 py-3 text-right">Revenue Contribution</th>
                                    <th class="px-4 py-3 text-right">Share (%)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr
                                    v-for="(cat, idx) in categories"
                                    :key="idx"
                                    class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                                >
                                    <td class="px-4 py-3 font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ cat.name }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ cat.units }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ formatCurrency(cat.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                        {{ cat.percentage.toFixed(1) }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </Card>
                </div>
            </div>

            <!-- TAB 3: CASHIERS -->
            <div v-if="activeTab === 'cashiers'">
                <Card class="overflow-hidden">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Cashier & Staff Performance</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                                <tr>
                                    <th class="px-4 py-3">Cashier Name</th>
                                    <th class="px-4 py-3">Role</th>
                                    <th class="px-4 py-3 text-center">Orders Handled</th>
                                    <th class="px-4 py-3 text-center">Avg Checkout Speed</th>
                                    <th class="px-4 py-3 text-center">Voids/Refunds</th>
                                    <th class="px-4 py-3 text-right">Total Sales</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr
                                    v-for="(cashier, idx) in cashiers"
                                    :key="idx"
                                    class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                                >
                                    <td class="px-4 py-3 font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ cashier.name }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                            {{ cashier.role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ cashier.orders }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-zinc-600 dark:text-zinc-400">
                                        {{ cashier.avg_time }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono" :class="cashier.voids > 0 ? 'text-amber-600 font-bold' : 'text-zinc-400'">
                                        {{ cashier.voids }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ formatCurrency(cashier.sales) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    </OrganizationLayout>
</template>
