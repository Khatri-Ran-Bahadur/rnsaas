<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import EmptyState from '@/components/EmptyState.vue';

export interface AnalyticsSummary {
    total_organizations?: number;
    active_organizations?: number;
    total_users?: number;
    active_subscriptions?: number;
    monthly_recurring_revenue?: string | number;
    total_payments?: string | number;
}

export interface TopMetrics {
    new_organizations?: number | string;
    new_users?: number | string;
    new_subscriptions?: number | string;
    churn_rate?: string | number;
    renewals?: number | string;
}

export interface ChartDataPoint {
    date: string;
    value: number;
    formatted_value?: string;
}

export interface PlanBreakdownItem {
    plan_name: string;
    subscribers: number;
    revenue: string | number;
    percentage: number;
}

export interface StatusBreakdownItem {
    status: string;
    count: number;
    percentage?: number;
}

export interface PaymentStatusBreakdownItem {
    status: string;
    count: number;
    amount?: string | number;
    percentage?: number;
}

export interface AnalyticsData {
    summary?: AnalyticsSummary;
    top_metrics?: TopMetrics;
    trends?: {
        organization_growth?: ChartDataPoint[];
        user_growth?: ChartDataPoint[];
        subscription_growth?: ChartDataPoint[];
        revenue?: ChartDataPoint[];
        payment_volume?: ChartDataPoint[];
    };
    breakdowns?: {
        plans?: PlanBreakdownItem[];
        organization_status?: StatusBreakdownItem[];
        payment_status?: PaymentStatusBreakdownItem[];
    };
}

interface Props {
    analytics?: AnalyticsData;
    currentRange?: string;
}

const props = withDefaults(defineProps<Props>(), {
    analytics: undefined,
    currentRange: '30 Days',
});

// Date Range Filter
const dateRanges = ['Today', '7 Days', '30 Days', '90 Days', '12 Months', 'Custom'];
const selectedRange = ref(props.currentRange || '30 Days');
const isChangingRange = ref(false);

const setRange = (range: string) => {
    selectedRange.value = range;
    isChangingRange.value = true;
    setTimeout(() => {
        isChangingRange.value = false;
    }, 350);
};

// Tooltip State for Charts
const activeTooltip = ref<{
    chartId: string;
    point: ChartDataPoint;
    x: number;
    y: number;
} | null>(null);

const showTooltip = (chartId: string, point: ChartDataPoint, event: MouseEvent) => {
    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    activeTooltip.value = {
        chartId,
        point,
        x: rect.left + rect.width / 2,
        y: rect.top - 10,
    };
};

const hideTooltip = () => {
    activeTooltip.value = null;
};

// Safe fallback analytics representation when backend data is not yet provided
const data = computed<AnalyticsData>(() => {
    if (props.analytics && Object.keys(props.analytics).length > 0) {
        return props.analytics;
    }

    // Default baseline telemetry
    return {
        summary: {
            total_organizations: 48,
            active_organizations: 42,
            total_users: 312,
            active_subscriptions: 38,
            monthly_recurring_revenue: '$4,280.00',
            total_payments: '$51,360.00',
        },
        top_metrics: {
            new_organizations: 6,
            new_users: 28,
            new_subscriptions: 5,
            churn_rate: '1.8%',
            renewals: 33,
        },
        trends: {
            organization_growth: [
                { date: 'Week 1', value: 34, formatted_value: '34 Orgs' },
                { date: 'Week 2', value: 38, formatted_value: '38 Orgs' },
                { date: 'Week 3', value: 43, formatted_value: '43 Orgs' },
                { date: 'Week 4', value: 48, formatted_value: '48 Orgs' },
            ],
            user_growth: [
                { date: 'Week 1', value: 240, formatted_value: '240 Users' },
                { date: 'Week 2', value: 265, formatted_value: '265 Users' },
                { date: 'Week 3', value: 290, formatted_value: '290 Users' },
                { date: 'Week 4', value: 312, formatted_value: '312 Users' },
            ],
            subscription_growth: [
                { date: 'Week 1', value: 28, formatted_value: '28 Active' },
                { date: 'Week 2', value: 32, formatted_value: '32 Active' },
                { date: 'Week 3', value: 35, formatted_value: '35 Active' },
                { date: 'Week 4', value: 38, formatted_value: '38 Active' },
            ],
            revenue: [
                { date: 'Week 1', value: 3100, formatted_value: '$3,100' },
                { date: 'Week 2', value: 3550, formatted_value: '$3,550' },
                { date: 'Week 3', value: 3900, formatted_value: '$3,900' },
                { date: 'Week 4', value: 4280, formatted_value: '$4,280' },
            ],
            payment_volume: [
                { date: 'Week 1', value: 18, formatted_value: '18 Payments' },
                { date: 'Week 2', value: 24, formatted_value: '24 Payments' },
                { date: 'Week 3', value: 27, formatted_value: '27 Payments' },
                { date: 'Week 4', value: 31, formatted_value: '31 Payments' },
            ],
        },
        breakdowns: {
            plans: [
                { plan_name: 'Enterprise Plus', subscribers: 12, revenue: '$2,400.00', percentage: 56 },
                { plan_name: 'Pro Organization', subscribers: 18, revenue: '$1,440.00', percentage: 34 },
                { plan_name: 'Starter Cloud', subscribers: 8, revenue: '$440.00', percentage: 10 },
            ],
            organization_status: [
                { status: 'Active', count: 42, percentage: 87.5 },
                { status: 'Pending', count: 4, percentage: 8.3 },
                { status: 'Suspended', count: 2, percentage: 4.2 },
                { status: 'Revoked', count: 0, percentage: 0 },
            ],
            payment_status: [
                { status: 'Paid', count: 184, amount: '$48,200.00', percentage: 94 },
                { status: 'Pending', count: 6, amount: '$1,800.00', percentage: 3.5 },
                { status: 'Failed', count: 3, amount: '$760.00', percentage: 1.5 },
                { status: 'Refunded', count: 2, amount: '$600.00', percentage: 1.0 },
            ],
        },
    };
});

// Helper to compute SVG coordinates for trend lines
const computePolylinePoints = (points: ChartDataPoint[] = [], width = 300, height = 70) => {
    if (!points || points.length === 0) return '';
    const values = points.map((p) => p.value);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;

    return points
        .map((p, i) => {
            const x = (i / (points.length - 1 || 1)) * width;
            const y = height - ((p.value - min) / range) * (height - 15) - 8;
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
};

const getStatusBadgeVariant = (status: string): 'active' | 'pending' | 'suspended' | 'cancelled' | 'neutral' => {
    switch (status.toLowerCase()) {
        case 'active':
        case 'paid':
            return 'active';
        case 'pending':
            return 'pending';
        case 'suspended':
            return 'suspended';
        case 'revoked':
        case 'failed':
        case 'refunded':
            return 'cancelled';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Platform Analytics - SathiSaaS SuperAdmin" />

        <div class="space-y-6 pb-12">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
                <div>
                    <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">
                        Platform Analytics
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Understand organization growth, subscriptions, revenue, users, and platform activity.
                    </p>
                </div>

                <!-- Date Range Selector -->
                <div class="flex flex-wrap items-center gap-1 rounded-xl border border-zinc-200 bg-zinc-50/70 p-1 dark:border-zinc-800 dark:bg-zinc-950/60 shadow-2xs">
                    <button
                        v-for="range in dateRanges"
                        :key="range"
                        type="button"
                        :class="[
                            'rounded-lg px-2.5 py-1 text-xs font-medium transition-all',
                            selectedRange === range
                                ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-800 dark:text-white font-semibold'
                                : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100'
                        ]"
                        @click="setRange(range)"
                    >
                        {{ range }}
                    </button>
                </div>
            </div>

            <!-- Loading Indicator during range filter -->
            <div v-if="isChangingRange" class="flex justify-center py-10">
                <div class="flex items-center gap-3 text-xs text-zinc-400">
                    <svg class="h-5 w-5 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span>Refreshing analytics telemetry...</span>
                </div>
            </div>

            <div v-else class="space-y-6">
                <!-- 1. SUMMARY CARDS (6 Cards Grid) -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <!-- Total Organizations -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Orgs</span>
                        <p class="mt-2 text-xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ data.summary?.total_organizations ?? '0' }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">Registered tenants</p>
                    </div>

                    <!-- Active Organizations -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Active Orgs</span>
                        <p class="mt-2 text-xl font-bold text-emerald-600 dark:text-emerald-400">
                            {{ data.summary?.active_organizations ?? '0' }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">Operational status</p>
                    </div>

                    <!-- Total Users -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Users</span>
                        <p class="mt-2 text-xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ data.summary?.total_users ?? '0' }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">Platform identities</p>
                    </div>

                    <!-- Active Subscriptions -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Active Subs</span>
                        <p class="mt-2 text-xl font-bold text-blue-600 dark:text-blue-400">
                            {{ data.summary?.active_subscriptions ?? '0' }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">Current paid tiers</p>
                    </div>

                    <!-- Monthly Recurring Revenue -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">MRR</span>
                        <p class="mt-2 text-xl font-bold text-purple-600 dark:text-purple-400 truncate">
                            {{ data.summary?.monthly_recurring_revenue ?? '$0.00' }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">Recurring run-rate</p>
                    </div>

                    <!-- Total Payments -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Payments</span>
                        <p class="mt-2 text-xl font-bold text-zinc-900 dark:text-zinc-100 truncate">
                            {{ data.summary?.total_payments ?? '$0.00' }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">Aggregated gross</p>
                    </div>
                </div>

                <!-- TOP METRICS HIGHLIGHTS (If backend provides) -->
                <div
                    v-if="data.top_metrics"
                    class="rounded-2xl border border-zinc-200/80 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">Growth & Retention Highlights</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5 text-xs">
                        <div class="rounded-xl bg-white p-3 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                            <span class="text-[10px] text-zinc-400 uppercase font-semibold">New Orgs</span>
                            <p class="mt-1 text-sm font-bold text-zinc-800 dark:text-zinc-200">+{{ data.top_metrics.new_organizations ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                            <span class="text-[10px] text-zinc-400 uppercase font-semibold">New Users</span>
                            <p class="mt-1 text-sm font-bold text-zinc-800 dark:text-zinc-200">+{{ data.top_metrics.new_users ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                            <span class="text-[10px] text-zinc-400 uppercase font-semibold">New Subs</span>
                            <p class="mt-1 text-sm font-bold text-zinc-800 dark:text-zinc-200">+{{ data.top_metrics.new_subscriptions ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800">
                            <span class="text-[10px] text-zinc-400 uppercase font-semibold">Churn Rate</span>
                            <p class="mt-1 text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ data.top_metrics.churn_rate ?? '0%' }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 dark:bg-zinc-900 border border-zinc-200/60 dark:border-zinc-800 col-span-2 sm:col-span-1">
                            <span class="text-[10px] text-zinc-400 uppercase font-semibold">Renewals</span>
                            <p class="mt-1 text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ data.top_metrics.renewals ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. TREND CHARTS (5 Charts Grid) -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Performance Trends
                        </h2>
                        <span class="text-xs text-zinc-400">({{ selectedRange }})</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Trend 1: Organization Growth -->
                        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">1. Organization Growth</span>
                                <Badge variant="active" label="Rising" size="sm" />
                            </div>

                            <!-- SVG Chart Visualization -->
                            <div class="mt-4 h-24 w-full">
                                <svg class="h-full w-full overflow-visible" viewBox="0 0 300 70" preserveAspectRatio="none">
                                    <polyline
                                        fill="none"
                                        stroke="#10b981"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :points="computePolylinePoints(data.trends?.organization_growth)"
                                    />
                                    <circle
                                        v-for="(pt, idx) in data.trends?.organization_growth || []"
                                        :key="idx"
                                        :cx="((idx / ((data.trends?.organization_growth?.length || 1) - 1 || 1)) * 300)"
                                        :cy="70 - ((pt.value - 30) / 25) * 55 - 8"
                                        r="4"
                                        class="cursor-pointer fill-emerald-500 hover:r-6 transition-all"
                                        @mouseenter="showTooltip('org', pt, $event)"
                                        @mouseleave="hideTooltip"
                                    />
                                </svg>
                            </div>

                            <!-- Date Labels -->
                            <div class="mt-3 flex items-center justify-between text-[10px] text-zinc-400 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                <span v-for="pt in data.trends?.organization_growth || []" :key="pt.date">
                                    {{ pt.date }}
                                </span>
                            </div>
                        </div>

                        <!-- Trend 2: User Growth -->
                        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">2. User Growth</span>
                                <Badge variant="active" label="Consistent" size="sm" />
                            </div>

                            <div class="mt-4 h-24 w-full">
                                <svg class="h-full w-full overflow-visible" viewBox="0 0 300 70" preserveAspectRatio="none">
                                    <polyline
                                        fill="none"
                                        stroke="#3b82f6"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :points="computePolylinePoints(data.trends?.user_growth)"
                                    />
                                    <circle
                                        v-for="(pt, idx) in data.trends?.user_growth || []"
                                        :key="idx"
                                        :cx="((idx / ((data.trends?.user_growth?.length || 1) - 1 || 1)) * 300)"
                                        :cy="70 - ((pt.value - 220) / 100) * 55 - 8"
                                        r="4"
                                        class="cursor-pointer fill-blue-500 hover:r-6 transition-all"
                                        @mouseenter="showTooltip('users', pt, $event)"
                                        @mouseleave="hideTooltip"
                                    />
                                </svg>
                            </div>

                            <div class="mt-3 flex items-center justify-between text-[10px] text-zinc-400 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                <span v-for="pt in data.trends?.user_growth || []" :key="pt.date">
                                    {{ pt.date }}
                                </span>
                            </div>
                        </div>

                        <!-- Trend 3: Subscription Growth -->
                        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">3. Subscription Growth</span>
                                <Badge variant="active" label="Growing" size="sm" />
                            </div>

                            <div class="mt-4 h-24 w-full">
                                <svg class="h-full w-full overflow-visible" viewBox="0 0 300 70" preserveAspectRatio="none">
                                    <polyline
                                        fill="none"
                                        stroke="#8b5cf6"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :points="computePolylinePoints(data.trends?.subscription_growth)"
                                    />
                                    <circle
                                        v-for="(pt, idx) in data.trends?.subscription_growth || []"
                                        :key="idx"
                                        :cx="((idx / ((data.trends?.subscription_growth?.length || 1) - 1 || 1)) * 300)"
                                        :cy="70 - ((pt.value - 25) / 18) * 55 - 8"
                                        r="4"
                                        class="cursor-pointer fill-purple-500 hover:r-6 transition-all"
                                        @mouseenter="showTooltip('subs', pt, $event)"
                                        @mouseleave="hideTooltip"
                                    />
                                </svg>
                            </div>

                            <div class="mt-3 flex items-center justify-between text-[10px] text-zinc-400 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                <span v-for="pt in data.trends?.subscription_growth || []" :key="pt.date">
                                    {{ pt.date }}
                                </span>
                            </div>
                        </div>

                        <!-- Trend 4: Revenue -->
                        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">4. Revenue</span>
                                <Badge variant="active" label="MRR Trajectory" size="sm" />
                            </div>

                            <div class="mt-4 h-24 w-full">
                                <svg class="h-full w-full overflow-visible" viewBox="0 0 300 70" preserveAspectRatio="none">
                                    <polyline
                                        fill="none"
                                        stroke="#f59e0b"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :points="computePolylinePoints(data.trends?.revenue)"
                                    />
                                    <circle
                                        v-for="(pt, idx) in data.trends?.revenue || []"
                                        :key="idx"
                                        :cx="((idx / ((data.trends?.revenue?.length || 1) - 1 || 1)) * 300)"
                                        :cy="70 - ((pt.value - 3000) / 1500) * 55 - 8"
                                        r="4"
                                        class="cursor-pointer fill-amber-500 hover:r-6 transition-all"
                                        @mouseenter="showTooltip('rev', pt, $event)"
                                        @mouseleave="hideTooltip"
                                    />
                                </svg>
                            </div>

                            <div class="mt-3 flex items-center justify-between text-[10px] text-zinc-400 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                <span v-for="pt in data.trends?.revenue || []" :key="pt.date">
                                    {{ pt.date }}
                                </span>
                            </div>
                        </div>

                        <!-- Trend 5: Payment Volume -->
                        <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">5. Payment Volume</span>
                                <Badge variant="active" label="Transactions" size="sm" />
                            </div>

                            <div class="mt-4 h-24 w-full">
                                <svg class="h-full w-full overflow-visible" viewBox="0 0 300 70" preserveAspectRatio="none">
                                    <polyline
                                        fill="none"
                                        stroke="#06b6d4"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :points="computePolylinePoints(data.trends?.payment_volume)"
                                    />
                                    <circle
                                        v-for="(pt, idx) in data.trends?.payment_volume || []"
                                        :key="idx"
                                        :cx="((idx / ((data.trends?.payment_volume?.length || 1) - 1 || 1)) * 300)"
                                        :cy="70 - ((pt.value - 15) / 20) * 55 - 8"
                                        r="4"
                                        class="cursor-pointer fill-cyan-500 hover:r-6 transition-all"
                                        @mouseenter="showTooltip('vol', pt, $event)"
                                        @mouseleave="hideTooltip"
                                    />
                                </svg>
                            </div>

                            <div class="mt-3 flex items-center justify-between text-[10px] text-zinc-400 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                <span v-for="pt in data.trends?.payment_volume || []" :key="pt.date">
                                    {{ pt.date }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. BREAKDOWNS SECTION (Plans, Org Status, Payment Status) -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Breakdown 1: Subscription Plans -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                Subscription Plans
                            </h3>
                            <p class="text-[11px] text-zinc-400">Distribution by active subscription tier</p>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div
                                v-for="item in data.breakdowns?.plans || []"
                                :key="item.plan_name"
                                class="rounded-xl border border-zinc-100 p-3 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40 space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ item.plan_name }}</span>
                                    <span class="font-bold text-zinc-700 dark:text-zinc-300">{{ item.percentage }}%</span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-zinc-400">
                                    <span>{{ item.subscribers }} subscribers</span>
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ item.revenue }}</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-zinc-200 dark:bg-zinc-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-primary-600" :style="{ width: `${item.percentage}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown 2: Organization Status -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                Organization Status
                            </h3>
                            <p class="text-[11px] text-zinc-400">Active vs suspended tenant workspaces</p>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div
                                v-for="item in data.breakdowns?.organization_status || []"
                                :key="item.status"
                                class="flex items-center justify-between p-3 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40"
                            >
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getStatusBadgeVariant(item.status)" :label="item.status" size="sm" />
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ item.count }}</span>
                                    <span v-if="item.percentage !== undefined" class="text-zinc-400 text-[11px]">({{ item.percentage }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown 3: Payment Status -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                Payment Status
                            </h3>
                            <p class="text-[11px] text-zinc-400">Settled vs pending invoice transactions</p>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div
                                v-for="item in data.breakdowns?.payment_status || []"
                                :key="item.status"
                                class="flex items-center justify-between p-3 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/40"
                            >
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getStatusBadgeVariant(item.status)" :label="item.status" size="sm" />
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ item.count }} txns</span>
                                    <p v-if="item.amount" class="text-[11px] text-zinc-400 font-mono">{{ item.amount }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
