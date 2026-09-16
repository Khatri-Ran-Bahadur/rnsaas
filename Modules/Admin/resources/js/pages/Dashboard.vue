<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import BarChart from '@/components/charts/BarChart.vue';
import DoughnutChart from '@/components/charts/DoughnutChart.vue';
import type { ChartData } from 'chart.js';

interface Tenant {
    public_id: string;
    name: string;
    slug: string;
    industry: string | null;
    status: string;
    country_code: string | null;
    timezone: string;
    locale: string;
    currency: string;
}

interface Members {
    total: number;
    active: number;
    invited: number;
    suspended: number;
    revoked: number;
}

interface Subscription {
    exists: boolean;
    status: string | null;
    plan: string | null;
    current_period_ends_at: string | null;
    trial_ends_at: string | null;
}

interface AccountingData {
    total_income: number;
    total_expenses: number;
    net_profit: number;
    profit_margin: number;
    receivables_total: number;
    receivables_count: number;
    payables_total: number;
    payables_count: number;
    monthly_labels: string[];
    monthly_income: number[];
    monthly_expense: number[];
}

interface PosOrderItem {
    id: number;
    order_number: string;
    customer_name: string;
    grand_total: number;
    payment_method: string;
    status: string;
    created_at: string;
}

interface PosData {
    today_sales: number;
    today_orders_count: number;
    month_sales: number;
    total_orders: number;
    average_order_value: number;
    payment_methods: {
        cash: number;
        card: number;
        qr: number;
        other: number;
    };
    recent_orders: PosOrderItem[];
}

interface UrgentStockItem {
    id: number;
    name: string;
    sku: string;
    on_hand_stock: number;
    reorder_point: number;
    cost_price: number;
    selling_price: number;
    is_out_of_stock: boolean;
}

interface InventoryData {
    total_skus: number;
    total_stock_units: number;
    total_stock_value: number;
    out_of_stock_count: number;
    low_stock_count: number;
    urgent_items: UrgentStockItem[];
}

interface OperationsData {
    staff_count: number;
    branches_count: number;
    departments_count: number;
}

interface OrganizationOption {
    id: number;
    public_id: string;
    name: string;
    slug: string;
}

const props = defineProps<{
    tenant: Tenant;
    members: Members;
    subscription: Subscription;
    accounting: AccountingData;
    pos: PosData;
    inventory: InventoryData;
    operations: OperationsData;
    organizations?: OrganizationOption[];
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const formatCurrency = (amount: number | null | undefined) => {
    const val = Number(amount || 0);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.tenant?.currency || 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(val);
};

const formatNumber = (num: number | null | undefined) => {
    return new Intl.NumberFormat('en-US').format(Number(num || 0));
};

const currencySymbol = computed(() => {
    try {
        return (0).toLocaleString('en-US', {
            style: 'currency',
            currency: props.tenant?.currency || 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).replace(/\d/g, '').trim();
    } catch {
        return props.tenant?.currency || '$';
    }
});

const statusColor = computed(() => {
    switch (props.tenant.status.toLowerCase()) {
        case 'active':
            return 'success';
        case 'suspended':
            return 'danger';
        case 'pending':
            return 'warning';
        default:
            return 'neutral';
    }
});

const subscriptionStatusColor = computed(() => {
    switch (props.subscription.status?.toLowerCase()) {
        case 'active':
            return 'success';
        case 'trialing':
            return 'info';
        case 'past_due':
        case 'cancelled':
            return 'danger';
        default:
            return 'neutral';
    }
});

// Chart 1: 6-Month Income vs Expense
const incomeVsExpenseChartData = computed<ChartData<'bar'>>(() => {
    return {
        labels: props.accounting?.monthly_labels?.length ? props.accounting.monthly_labels : ['M-5', 'M-4', 'M-3', 'M-2', 'M-1', 'Current'],
        datasets: [
            {
                label: 'Income / Revenue',
                data: props.accounting?.monthly_income || [0, 0, 0, 0, 0, 0],
                backgroundColor: '#10b981',
                borderRadius: 6,
                maxBarThickness: 28,
            },
            {
                label: 'Expenses / Bills',
                data: props.accounting?.monthly_expense || [0, 0, 0, 0, 0, 0],
                backgroundColor: '#f43f5e',
                borderRadius: 6,
                maxBarThickness: 28,
            }
        ]
    };
});

// Chart 2: POS Payment Methods Distribution
const paymentMethodsChartData = computed<ChartData<'doughnut'>>(() => {
    const methods = props.pos?.payment_methods || { cash: 0, card: 0, qr: 0, other: 0 };
    const totalTransactions = methods.cash + methods.card + methods.qr + methods.other;

    return {
        labels: ['Cash', 'Credit / Debit Card', 'Digital QR / Wallet', 'Other Methods'],
        datasets: [
            {
                data: totalTransactions > 0 
                    ? [methods.cash, methods.card, methods.qr, methods.other] 
                    : [1, 0, 0, 0],
                backgroundColor: ['#10b981', '#6366f1', '#f59e0b', '#a855f7'],
                borderWidth: 0,
            }
        ]
    };
});
</script>

<template>
    <OrganizationLayout
        :title="`${tenant.name} - Executive Dashboard`"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Executive Dashboard' },
        ]"
    >
        <Head :title="`${tenant.name} - Executive Dashboard`" />

        <div class="space-y-7">
            <!-- Organization Header & Quick Launch Bar -->
            <div class="flex flex-col justify-between gap-5 rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:flex-row lg:items-center">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-700 text-2xl font-black text-white shadow-lg shadow-indigo-500/25">
                        {{ tenant.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                                {{ tenant.name }}
                            </h1>
                            <Badge :variant="statusColor" class="capitalize font-semibold text-[11px]">
                                {{ tenant.status }}
                            </Badge>
                            <span v-if="subscription.plan" class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-900">
                                {{ subscription.plan }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Workspace: <span class="font-mono font-medium text-zinc-700 dark:text-zinc-300">{{ tenant.slug }}</span>
                            <span class="mx-2 text-zinc-300 dark:text-zinc-700">&bull;</span>
                            Currency: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ tenant.currency }}</span>
                            <span class="mx-2 text-zinc-300 dark:text-zinc-700">&bull;</span>
                            Timezone: <span class="text-zinc-600 dark:text-zinc-400">{{ tenant.timezone }}</span>
                        </p>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link
                        href="/admin/pos"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm shadow-indigo-500/30 hover:bg-indigo-700 transition-all hover:-translate-y-0.5"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>Open POS Terminal</span>
                    </Link>

                    <Link
                        href="/admin/accounting/invoices/create"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm shadow-emerald-500/20 hover:bg-emerald-700 transition-all hover:-translate-y-0.5"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ New Invoice</span>
                    </Link>

                    <Link
                        href="/admin/accounting/invoices"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50/80 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition-colors dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    >
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Invoices</span>
                    </Link>

                    <Link
                        href="/admin/accounting/purchase-bills"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50/80 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition-colors dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    >
                        <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Bills</span>
                    </Link>

                    <Link
                        href="/admin/inventory/items"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50/80 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition-colors dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    >
                        <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Inventory</span>
                    </Link>
                </div>
            </div>

            <!-- SECTION 1: FINANCIAL & EXECUTIVE KPI CARDS -->
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Financial Overview & Performance
                    </h2>
                    <div class="flex items-center gap-3">
                        <Link
                            href="/admin/accounting/invoices/create"
                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 hover:underline"
                        >
                            + New Sales Invoice
                        </Link>
                        <span class="text-zinc-300 dark:text-zinc-700">&bull;</span>
                        <Link
                            href="/admin/accounting/statements/profit-and-loss"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 hover:underline"
                        >
                            View Full P&amp;L Statement &rarr;
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Net Profit / Loss Card -->
                    <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Net Profit / (Loss)</span>
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold',
                                    accounting.net_profit >= 0
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'
                                ]"
                            >
                                Margin {{ accounting.profit_margin }}%
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                            {{ formatCurrency(accounting.net_profit) }}
                        </p>
                        <div class="mt-3 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 border-t border-zinc-100 pt-2.5 dark:border-zinc-800/80">
                            <span>Income: <strong class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(accounting.total_income) }}</strong></span>
                            <span>Exp: <strong class="text-rose-600 dark:text-rose-400">{{ formatCurrency(accounting.total_expenses) }}</strong></span>
                        </div>
                    </div>

                    <!-- Accounts Receivable Card -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Accounts Receivable</span>
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-3 text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                            {{ formatCurrency(accounting.receivables_total) }}
                        </p>
                        <div class="mt-3 flex items-center justify-between text-xs border-t border-zinc-100 pt-2.5 dark:border-zinc-800/80">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ accounting.receivables_count }} pending invoices</span>
                            <Link href="/admin/accounting/reports/receivable-aging" class="font-medium text-sky-600 hover:underline dark:text-sky-400">
                                Aging Report &rarr;
                            </Link>
                        </div>
                    </div>

                    <!-- Accounts Payable Card -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Accounts Payable</span>
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-3 text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                            {{ formatCurrency(accounting.payables_total) }}
                        </p>
                        <div class="mt-3 flex items-center justify-between text-xs border-t border-zinc-100 pt-2.5 dark:border-zinc-800/80">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ accounting.payables_count }} unpaid bills</span>
                            <Link href="/admin/accounting/reports/payable-aging" class="font-medium text-rose-600 hover:underline dark:text-rose-400">
                                Aging Report &rarr;
                            </Link>
                        </div>
                    </div>

                    <!-- Inventory Valuation Card -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Stock Valuation (Cost)</span>
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-3 text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                            {{ formatCurrency(inventory.total_stock_value) }}
                        </p>
                        <div class="mt-3 flex items-center justify-between text-xs border-t border-zinc-100 pt-2.5 dark:border-zinc-800/80">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ formatNumber(inventory.total_stock_units) }} total units</span>
                            <Link href="/admin/inventory/items" class="font-medium text-amber-600 hover:underline dark:text-amber-400">
                                Stock List &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: POS SALES & INVENTORY METRIC ROW -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Today's POS Sales -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Today's POS Sales</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ formatCurrency(pos.today_sales) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            <strong>{{ pos.today_orders_count }}</strong> orders completed today
                        </p>
                    </div>
                </div>

                <!-- Monthly POS Sales -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Monthly Sales (POS)</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ formatCurrency(pos.month_sales) }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Avg Order Value: <strong>{{ formatCurrency(pos.average_order_value) }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Out of Stock Alert -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-rose-500 dark:text-rose-400">Out of Stock Items</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400">
                            {{ inventory.out_of_stock_count }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            SKUs with zero inventory on hand
                        </p>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-amber-500 dark:text-amber-400">Low Stock Warnings</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                            {{ inventory.low_stock_count }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Items below configured reorder point
                        </p>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: VISUAL INTELLIGENCE CHARTS (INCOME VS EXPENSES & POS PAYMENT DISTRIBUTION) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Bar Chart: 6-Month Income vs Expenses -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                    <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">Financial Trends: Income vs. Expenses</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">6-Month operational trajectory based on approved invoices and bills</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="h-3 w-3 rounded-sm bg-emerald-500"></span>
                                <span class="font-medium text-zinc-600 dark:text-zinc-300">Income</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="h-3 w-3 rounded-sm bg-rose-500"></span>
                                <span class="font-medium text-zinc-600 dark:text-zinc-300">Expenses</span>
                            </div>
                        </div>
                    </div>
                    <div class="h-72">
                        <BarChart
                            :data="incomeVsExpenseChartData"
                            :height="280"
                            :currency-prefix="currencySymbol"
                        />
                    </div>
                </div>

                <!-- Doughnut Chart: POS Payment Method Distribution -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">POS Payment Mix</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Distribution of checkout methods</p>
                        </div>
                    </div>
                    <div class="h-56">
                        <DoughnutChart
                            :data="paymentMethodsChartData"
                            :height="220"
                            :center-text="String(pos.total_orders)"
                            center-subtext="Orders"
                        />
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 border-t border-zinc-100 pt-3 text-xs dark:border-zinc-800">
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/60">
                            <span class="text-zinc-500 dark:text-zinc-400">Cash:</span>
                            <span class="font-bold text-zinc-900 dark:text-white">{{ pos.payment_methods.cash }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/60">
                            <span class="text-zinc-500 dark:text-zinc-400">Card:</span>
                            <span class="font-bold text-zinc-900 dark:text-white">{{ pos.payment_methods.card }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/60">
                            <span class="text-zinc-500 dark:text-zinc-400">QR / Wallet:</span>
                            <span class="font-bold text-zinc-900 dark:text-white">{{ pos.payment_methods.qr }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/60">
                            <span class="text-zinc-500 dark:text-zinc-400">Other:</span>
                            <span class="font-bold text-zinc-900 dark:text-white">{{ pos.payment_methods.other }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: OPERATIONAL TABLES (RECENT POS ORDERS & STOCK ALERTS) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Recent POS Orders Table -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">Recent POS Transactions</h2>
                        </div>
                        <Link
                            href="/admin/pos/orders"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 hover:underline"
                        >
                            View All Orders &rarr;
                        </Link>
                    </div>

                    <div v-if="pos.recent_orders && pos.recent_orders.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-zinc-100 text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                                <tr>
                                    <th class="pb-2.5 font-semibold">Order</th>
                                    <th class="pb-2.5 font-semibold">Customer</th>
                                    <th class="pb-2.5 font-semibold">Method</th>
                                    <th class="pb-2.5 font-semibold text-right">Amount</th>
                                    <th class="pb-2.5 font-semibold text-right">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                <tr v-for="order in pos.recent_orders" :key="order.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40">
                                    <td class="py-2.5 font-mono font-bold text-zinc-900 dark:text-white">
                                        #{{ order.order_number }}
                                    </td>
                                    <td class="py-2.5 text-zinc-600 dark:text-zinc-300">
                                        {{ order.customer_name }}
                                    </td>
                                    <td class="py-2.5">
                                        <span class="rounded bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ order.payment_method }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right font-bold text-zinc-900 dark:text-white">
                                        {{ formatCurrency(order.grand_total) }}
                                    </td>
                                    <td class="py-2.5 text-right text-zinc-400">
                                        {{ order.created_at }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <p class="mt-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">No POS orders recorded yet</p>
                    </div>
                </div>

                <!-- Urgent Stock Reorder Alerts Table -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">Stock Reorder Warnings</h2>
                        </div>
                        <Link
                            href="/admin/inventory/items"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 hover:underline"
                        >
                            Manage Inventory &rarr;
                        </Link>
                    </div>

                    <div v-if="inventory.urgent_items && inventory.urgent_items.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-zinc-100 text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                                <tr>
                                    <th class="pb-2.5 font-semibold">SKU / Item</th>
                                    <th class="pb-2.5 font-semibold text-center">On Hand</th>
                                    <th class="pb-2.5 font-semibold text-center">Reorder Pt</th>
                                    <th class="pb-2.5 font-semibold text-right">Cost</th>
                                    <th class="pb-2.5 font-semibold text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                <tr v-for="item in inventory.urgent_items" :key="item.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40">
                                    <td class="py-2.5">
                                        <div class="font-semibold text-zinc-900 dark:text-white">{{ item.name }}</div>
                                        <div class="font-mono text-[10px] text-zinc-400">{{ item.sku }}</div>
                                    </td>
                                    <td class="py-2.5 text-center font-bold" :class="item.is_out_of_stock ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400'">
                                        {{ item.on_hand_stock }}
                                    </td>
                                    <td class="py-2.5 text-center text-zinc-500 dark:text-zinc-400">
                                        {{ item.reorder_point }}
                                    </td>
                                    <td class="py-2.5 text-right font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ formatCurrency(item.cost_price) }}
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <span
                                            v-if="item.is_out_of_stock"
                                            class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-950/60 dark:text-rose-400"
                                        >
                                            Out of Stock
                                        </span>
                                        <span
                                            v-else
                                            class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-950/60 dark:text-amber-400"
                                        >
                                            Low Stock
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400">Stock levels healthy</p>
                        <p class="mt-0.5 text-[11px] text-zinc-400">No items currently below reorder thresholds</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: ORGANIZATION DETAILS & SUBSCRIPTION STATUS -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Organization Profile Card -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Workspace Operations</h2>
                        </div>
                        <Badge :variant="statusColor" class="capitalize">{{ tenant.status }}</Badge>
                    </div>

                    <dl class="mt-5 grid grid-cols-1 gap-3.5 sm:grid-cols-3 text-xs">
                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Team Seats</dt>
                            <dd class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">{{ members.total }}</dd>
                            <dd class="text-[11px] text-emerald-600 dark:text-emerald-400">{{ members.active }} active</dd>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Branches</dt>
                            <dd class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">{{ operations.branches_count }}</dd>
                            <dd class="text-[11px] text-zinc-500">Operational units</dd>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Payroll Staff</dt>
                            <dd class="mt-1 text-lg font-bold text-zinc-900 dark:text-white">{{ operations.staff_count }}</dd>
                            <dd class="text-[11px] text-zinc-500">Registered staff</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex items-center justify-between rounded-xl bg-zinc-50/70 p-3 text-xs dark:bg-zinc-950/40">
                        <span class="text-zinc-500 dark:text-zinc-400">Industry: <strong class="text-zinc-700 dark:text-zinc-200">{{ tenant.industry || 'General Business' }}</strong></span>
                        <Link href="/admin/members" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                            Manage Members &rarr;
                        </Link>
                    </div>
                </div>

                <!-- Subscription Status Card -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Active Subscription</h2>
                        </div>
                        <Badge
                            v-if="subscription.exists && subscription.status"
                            :variant="subscriptionStatusColor"
                            class="capitalize"
                        >
                            {{ subscription.status }}
                        </Badge>
                        <Badge v-else variant="neutral">No Subscription</Badge>
                    </div>

                    <div v-if="subscription.exists" class="mt-5 space-y-4">
                        <div class="flex items-center justify-between rounded-xl bg-indigo-50/60 p-4 border border-indigo-100 dark:bg-indigo-950/40 dark:border-indigo-900/60">
                            <div>
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Assigned Package</span>
                                <p class="mt-0.5 text-lg font-bold text-zinc-900 dark:text-white">
                                    {{ subscription.plan || 'Active Package' }}
                                </p>
                            </div>
                            <Link
                                href="/admin/subscription"
                                class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 shadow-xs hover:bg-zinc-50 dark:bg-zinc-800 dark:text-indigo-400 dark:hover:bg-zinc-700"
                            >
                                Upgrade Plan
                            </Link>
                        </div>

                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
                            <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Current Period Renewal</dt>
                                <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">
                                    {{ subscription.current_period_ends_at ? new Date(subscription.current_period_ends_at).toLocaleDateString() : 'Continuous / Lifetime' }}
                                </dd>
                            </div>

                            <div v-if="subscription.trial_ends_at" class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Trial Period Ends</dt>
                                <dd class="mt-1 font-semibold text-amber-600 dark:text-amber-400">
                                    {{ new Date(subscription.trial_ends_at).toLocaleDateString() }}
                                </dd>
                            </div>
                            <div v-else class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Billing Tier</dt>
                                <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">
                                    Standard Multi-Tenant
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div v-else class="mt-8 flex flex-col items-center justify-center py-6 text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">No active plan assigned</p>
                        <Link
                            href="/admin/subscription"
                            class="mt-3 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                        >
                            Select a Package
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
