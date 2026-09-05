<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import StatsCard from '@/components/StatsCard.vue';
import Card from '@/components/Card.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import EmptyState from '@/components/EmptyState.vue';
import DatePicker from '@/components/DatePicker.vue';

// ─── TypeScript Interfaces ──────────────────────────────────────────────────

export interface AnalyticsSummary {
    organizations: number;
    users: number;
    subscriptions: number;
    revenue: number;
    currency: string;
}

export interface TrendDataPoint {
    month: string; // "YYYY-MM"
    value: number;
}

export interface SubscriptionDistributionItem {
    id: number;
    name: string;
    value: number;
}

export interface RecentGrowth {
    organizations: number;
    users: number;
    subscriptions: number;
    payments: number;
}

export interface PlatformAnalyticsData {
    summary: AnalyticsSummary;
    revenue: TrendDataPoint[];
    organizations: TrendDataPoint[];
    subscriptions: TrendDataPoint[];
    subscription_distribution: SubscriptionDistributionItem[];
    recent_growth: RecentGrowth;
}

export interface AnalyticsFilters {
    from?: string | null;
    to?: string | null;
}

export interface Props {
    analytics?: PlatformAnalyticsData;
    filters?: AnalyticsFilters;
}

const props = withDefaults(defineProps<Props>(), {
    analytics: () => ({
        summary: {
            organizations: 0,
            users: 0,
            subscriptions: 0,
            revenue: 0,
            currency: 'USD',
        },
        revenue: [],
        organizations: [],
        subscriptions: [],
        subscription_distribution: [],
        recent_growth: {
            organizations: 0,
            users: 0,
            subscriptions: 0,
            payments: 0,
        },
    }),
    filters: () => ({
        from: null,
        to: null,
    }),
});

// ─── Safe Fallbacks & Computed Analytics ────────────────────────────────────

const summary = computed(() => props.analytics?.summary ?? {
    organizations: 0,
    users: 0,
    subscriptions: 0,
    revenue: 0,
    currency: 'USD',
});

const revenueTrend = computed(() => props.analytics?.revenue ?? []);
const organizationTrend = computed(() => props.analytics?.organizations ?? []);
const subscriptionTrend = computed(() => props.analytics?.subscriptions ?? []);
const subscriptionDistribution = computed(() => props.analytics?.subscription_distribution ?? []);
const recentGrowth = computed(() => props.analytics?.recent_growth ?? {
    organizations: 0,
    users: 0,
    subscriptions: 0,
    payments: 0,
});

// ─── Formatting Helpers ─────────────────────────────────────────────────────

const formatCurrency = (amount?: number | null, currency = 'USD'): string => {
    const num = Number(amount || 0);
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency || 'USD',
            maximumFractionDigits: 2,
        }).format(num);
    } catch {
        return `$${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
};

const formatNumber = (num?: number | null): string => {
    return Number(num || 0).toLocaleString('en-US');
};

const formatCompact = (val: number, isCurrency = false, currency = 'USD'): string => {
    if (val === 0) return isCurrency ? '$0' : '0';
    if (Math.abs(val) >= 1_000_000) {
        const str = (val / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
        return isCurrency ? `$${str}` : str;
    }
    if (Math.abs(val) >= 1_000) {
        const str = (val / 1_000).toFixed(1).replace(/\.0$/, '') + 'k';
        return isCurrency ? `$${str}` : str;
    }
    return isCurrency ? formatCurrency(val, currency) : String(Math.round(val));
};

const formatMonth = (monthStr: string, mode: 'short' | 'full' = 'short'): string => {
    if (!monthStr) return '';
    const parts = monthStr.split('-');
    if (parts.length !== 2) return monthStr;
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const date = new Date(year, month, 1);
    if (isNaN(date.getTime())) return monthStr;

    if (mode === 'full') {
        return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
};

// ─── Date Range Filtering ───────────────────────────────────────────────────

const isFiltering = ref(false);
const showCustomRange = ref(Boolean(props.filters?.from && props.filters?.to));

const customFrom = ref<string | null>(props.filters?.from ?? null);
const customTo = ref<string | null>(props.filters?.to ?? null);

const activeRangeKey = computed(() => {
    if (showCustomRange.value) return 'custom';
    if (!props.filters?.from && !props.filters?.to) return 'all';
    return 'custom';
});

const applyDateFilter = (from?: string | null, to?: string | null) => {
    isFiltering.value = true;
    const targetUrl = typeof window !== 'undefined' ? window.location.pathname : '/superadmin/analytics';

    router.get(
        targetUrl,
        {
            from: from || undefined,
            to: to || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        },
    );
};

const setPreset = (preset: '30d' | '90d' | '12m' | 'ytd' | 'all') => {
    showCustomRange.value = false;
    const today = new Date();
    const formatDate = (d: Date) => d.toISOString().split('T')[0];

    if (preset === 'all') {
        applyDateFilter(null, null);
        return;
    }

    if (preset === '30d') {
        const fromDate = new Date();
        fromDate.setDate(today.getDate() - 30);
        applyDateFilter(formatDate(fromDate), formatDate(today));
        return;
    }

    if (preset === '90d') {
        const fromDate = new Date();
        fromDate.setDate(today.getDate() - 90);
        applyDateFilter(formatDate(fromDate), formatDate(today));
        return;
    }

    if (preset === '12m') {
        const fromDate = new Date(today.getFullYear(), today.getMonth() - 11, 1);
        const toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        applyDateFilter(formatDate(fromDate), formatDate(toDate));
        return;
    }

    if (preset === 'ytd') {
        const fromDate = new Date(today.getFullYear(), 0, 1);
        applyDateFilter(formatDate(fromDate), formatDate(today));
    }
};

const submitCustomRange = () => {
    if (!customFrom.value || !customTo.value) return;
    applyDateFilter(customFrom.value, customTo.value);
};

const refreshData = () => {
    isFiltering.value = true;
    router.reload({
        onFinish: () => {
            isFiltering.value = false;
        },
    });
};

// ─── SVG Chart Visualizations & Calculations ────────────────────────────────

// 1. Revenue Area Chart
const revenueSvg = computed(() => {
    const data = revenueTrend.value;
    if (data.length === 0) return null;

    const width = 800;
    const height = 240;
    const pad = { top: 25, right: 30, bottom: 40, left: 65 };
    const chartW = width - pad.left - pad.right;
    const chartH = height - pad.top - pad.bottom;

    const rawMax = Math.max(...data.map((d) => d.value), 0);
    // Determine pleasant round max value for scale
    const order = rawMax > 0 ? Math.pow(10, Math.floor(Math.log10(rawMax))) : 100;
    const niceMax = rawMax > 0 ? Math.ceil(rawMax / order) * order : 100;

    const points = data.map((d, i) => {
        const x = pad.left + (i / Math.max(data.length - 1, 1)) * chartW;
        const y = pad.top + (1 - (d.value / (niceMax || 1))) * chartH;
        return {
            x,
            y,
            month: d.month,
            value: d.value,
            formatted: formatCurrency(d.value, summary.value.currency),
        };
    });

    // Build smooth SVG path
    let linePath = '';
    if (points.length === 1) {
        linePath = `M ${pad.left} ${points[0].y} L ${width - pad.right} ${points[0].y}`;
    } else {
        linePath = points.reduce((acc, curr, i, arr) => {
            if (i === 0) return `M ${curr.x.toFixed(1)} ${curr.y.toFixed(1)}`;
            const prev = arr[i - 1];
            const cpx1 = prev.x + (curr.x - prev.x) / 2;
            const cpy1 = prev.y;
            const cpx2 = prev.x + (curr.x - prev.x) / 2;
            const cpy2 = curr.y;
            return `${acc} C ${cpx1.toFixed(1)} ${cpy1.toFixed(1)}, ${cpx2.toFixed(1)} ${cpy2.toFixed(1)}, ${curr.x.toFixed(1)} ${curr.y.toFixed(1)}`;
        }, '');
    }

    const baselineY = height - pad.bottom;
    const areaPath = `${linePath} L ${points[points.length - 1].x.toFixed(1)} ${baselineY} L ${points[0].x.toFixed(1)} ${baselineY} Z`;

    // 4 Y-axis gridline increments
    const gridLines = [0, 0.25, 0.5, 0.75, 1].map((ratio) => {
        const val = niceMax * ratio;
        const y = pad.top + (1 - ratio) * chartH;
        return {
            y,
            label: formatCompact(val, true, summary.value.currency),
        };
    });

    return {
        width,
        height,
        points,
        linePath,
        areaPath,
        gridLines,
        pad,
    };
});

// Hover state for revenue chart
const activeRevenuePoint = ref<{
    month: string;
    value: number;
    formatted: string;
    x: number;
    y: number;
} | null>(null);

// 2. Dual Growth Charts (Organizations & Subscriptions)
const createGrowthSvg = (data: TrendDataPoint[], color: string) => {
    if (data.length === 0) return null;

    const width = 450;
    const height = 180;
    const pad = { top: 20, right: 20, bottom: 35, left: 45 };
    const chartW = width - pad.left - pad.right;
    const chartH = height - pad.top - pad.bottom;

    const rawMax = Math.max(...data.map((d) => d.value), 0);
    const niceMax = rawMax > 0 ? (rawMax <= 5 ? 5 : Math.ceil(rawMax / 5) * 5) : 5;

    const points = data.map((d, i) => {
        const x = pad.left + (i / Math.max(data.length - 1, 1)) * chartW;
        const y = pad.top + (1 - (d.value / (niceMax || 1))) * chartH;
        return {
            x,
            y,
            month: d.month,
            value: d.value,
        };
    });

    let linePath = '';
    if (points.length === 1) {
        linePath = `M ${pad.left} ${points[0].y} L ${width - pad.right} ${points[0].y}`;
    } else {
        linePath = points.reduce((acc, curr, i, arr) => {
            if (i === 0) return `M ${curr.x.toFixed(1)} ${curr.y.toFixed(1)}`;
            const prev = arr[i - 1];
            const cpx1 = prev.x + (curr.x - prev.x) / 2;
            const cpy1 = prev.y;
            const cpx2 = prev.x + (curr.x - prev.x) / 2;
            const cpy2 = curr.y;
            return `${acc} C ${cpx1.toFixed(1)} ${cpy1.toFixed(1)}, ${cpx2.toFixed(1)} ${cpy2.toFixed(1)}, ${curr.x.toFixed(1)} ${curr.y.toFixed(1)}`;
        }, '');
    }

    const baselineY = height - pad.bottom;
    const areaPath = `${linePath} L ${points[points.length - 1].x.toFixed(1)} ${baselineY} L ${points[0].x.toFixed(1)} ${baselineY} Z`;

    const gridLines = [0, 0.5, 1].map((ratio) => {
        const val = Math.round(niceMax * ratio);
        const y = pad.top + (1 - ratio) * chartH;
        return {
            y,
            label: String(val),
        };
    });

    return {
        width,
        height,
        points,
        linePath,
        areaPath,
        gridLines,
        color,
        pad,
    };
};

const orgSvg = computed(() => createGrowthSvg(organizationTrend.value, '#3b82f6'));
const subSvg = computed(() => createGrowthSvg(subscriptionTrend.value, '#8b5cf6'));

const activeOrgPoint = ref<{ month: string; value: number; x: number; y: number } | null>(null);
const activeSubPoint = ref<{ month: string; value: number; x: number; y: number } | null>(null);

// 3. Subscription Distribution Donut Chart
const PLAN_COLORS = [
    '#4f46e5', // Indigo
    '#10b981', // Emerald
    '#f59e0b', // Amber
    '#0284c7', // Sky Blue
    '#ec4899', // Pink
    '#8b5cf6', // Purple
    '#06b6d4', // Cyan
    '#64748b', // Slate
];

const distributionData = computed(() => {
    const items = subscriptionDistribution.value;
    const total = items.reduce((sum, item) => sum + item.value, 0);

    let cumulativeAngle = 0;
    const radius = 80;
    const strokeWidth = 26;
    const circumference = 2 * Math.PI * radius; // ~502.65

    const slices = items.map((item, index) => {
        const color = PLAN_COLORS[index % PLAN_COLORS.length];
        const percentage = total > 0 ? (item.value / total) * 100 : 0;
        const dashLength = (percentage / 100) * circumference;
        const dashOffset = -cumulativeAngle * circumference;

        cumulativeAngle += percentage / 100;

        return {
            ...item,
            color,
            percentage: Number(percentage.toFixed(1)),
            dashArray: `${dashLength} ${circumference}`,
            dashOffset,
        };
    });

    return {
        total,
        slices,
        radius,
        strokeWidth,
        circumference,
    };
});

const activeDonutSlice = ref<number | null>(null);
</script>

<template>
    <SuperAdminLayout
        title="Platform Analytics"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Analytics' },
        ]"
    >
        <Head title="Platform Analytics - SathiSaaS SuperAdmin" />

        <div class="space-y-8 pb-16">
            <!-- ─── 1. Page Header ───────────────────────────────────────────── -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-zinc-200 pb-6 dark:border-zinc-800">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            Platform Analytics
                        </h1>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse" />
                            Live Telemetry
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Monitor platform growth, revenue, subscriptions, and organization activity.
                    </p>
                </div>

                <!-- Right Controls: Date Presets & Refresh -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Preset Selector Tabs -->
                    <div class="flex items-center rounded-xl border border-zinc-200 bg-zinc-50/80 p-1 dark:border-zinc-800 dark:bg-zinc-900/80 shadow-2xs">
                        <button
                            type="button"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all cursor-pointer',
                                activeRangeKey === 'all'
                                    ? 'bg-white text-zinc-900 font-semibold shadow-xs dark:bg-zinc-800 dark:text-white'
                                    : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100',
                            ]"
                            @click="setPreset('all')"
                        >
                            All Time
                        </button>
                        <button
                            type="button"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all cursor-pointer',
                                !showCustomRange && filters?.from && filters?.to
                                    ? 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100'
                                    : '',
                            ]"
                            @click="setPreset('30d')"
                        >
                            30 Days
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-all cursor-pointer"
                            @click="setPreset('90d')"
                        >
                            90 Days
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-all cursor-pointer"
                            @click="setPreset('12m')"
                        >
                            12 Months
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 transition-all cursor-pointer"
                            @click="setPreset('ytd')"
                        >
                            YTD
                        </button>
                        <button
                            type="button"
                            :class="[
                                'rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all cursor-pointer',
                                showCustomRange
                                    ? 'bg-white text-zinc-900 font-semibold shadow-xs dark:bg-zinc-800 dark:text-white'
                                    : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100',
                            ]"
                            @click="showCustomRange = !showCustomRange"
                        >
                            Custom
                        </button>
                    </div>

                    <!-- Refresh Button -->
                    <Button
                        variant="secondary"
                        size="sm"
                        :loading="isFiltering"
                        title="Reload Analytics"
                        @click="refreshData"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="hidden sm:inline">Refresh</span>
                    </Button>
                </div>
            </div>

            <!-- Custom Date Range Sub-Bar -->
            <div
                v-if="showCustomRange"
                class="flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/75 p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
            >
                <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                    Custom Date Range:
                </span>
                <div class="w-40 sm:w-48">
                    <DatePicker
                        v-model="customFrom"
                        placeholder="From date"
                    />
                </div>
                <span class="text-zinc-400 text-xs">to</span>
                <div class="w-40 sm:w-48">
                    <DatePicker
                        v-model="customTo"
                        placeholder="To date"
                    />
                </div>
                <Button
                    variant="primary"
                    size="sm"
                    :disabled="!customFrom || !customTo || isFiltering"
                    @click="submitCustomRange"
                >
                    Apply Filter
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    @click="setPreset('all')"
                >
                    Reset
                </Button>
            </div>

            <!-- ─── 2. Overview Cards (4 Primary Cards) ───────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- A. Organizations -->
                <StatsCard
                    title="Organizations"
                    :value="formatNumber(summary.organizations)"
                    subtitle="Total organizations created in period"
                    badge-color="blue"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </template>
                </StatsCard>

                <!-- B. Users -->
                <StatsCard
                    title="Platform Users"
                    :value="formatNumber(summary.users)"
                    subtitle="Total users created in period"
                    badge-color="indigo"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <!-- C. Subscriptions -->
                <StatsCard
                    title="Subscriptions"
                    :value="formatNumber(summary.subscriptions)"
                    subtitle="Total subscriptions created in period"
                    badge-color="amber"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </template>
                </StatsCard>

                <!-- D. Revenue -->
                <StatsCard
                    title="Paid Revenue"
                    :value="formatCurrency(summary.revenue, summary.currency)"
                    subtitle="Total paid revenue in period"
                    badge-color="emerald"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>
            </div>

            <!-- ─── 3. Revenue Trend Chart (Large Section) ────────────────────── -->
            <Card :no-padding="true">
                <template #header>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 w-full">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Revenue Trend
                                </h2>
                                <Badge variant="active" label="Paid Transactions" size="sm" />
                            </div>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Paid revenue over time.
                            </p>
                        </div>

                        <!-- Highlight Total -->
                        <div class="text-left sm:text-right">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Period Total:</span>
                            <span class="ml-1.5 font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                                {{ formatCurrency(summary.revenue, summary.currency) }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Chart Container -->
                <div class="p-6">
                    <!-- Empty State -->
                    <div v-if="!revenueSvg || revenueTrend.length === 0" class="py-12">
                        <EmptyState
                            title="No revenue data available"
                            description="No paid transactions recorded for this period."
                        >
                            <template #icon>
                                <svg class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                        </EmptyState>
                    </div>

                    <!-- Interactive SVG Chart -->
                    <div v-else class="relative w-full overflow-hidden">
                        <!-- Floating Tooltip -->
                        <div
                            v-if="activeRevenuePoint"
                            class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full transform rounded-xl border border-zinc-200 bg-white/95 px-3 py-2 text-xs shadow-lg backdrop-blur-xs dark:border-zinc-700 dark:bg-zinc-800/95 transition-all"
                            :style="{
                                left: `${(activeRevenuePoint.x / revenueSvg.width) * 100}%`,
                                top: `${(activeRevenuePoint.y / revenueSvg.height) * 100 - 6}%`,
                            }"
                        >
                            <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                                {{ formatMonth(activeRevenuePoint.month, 'full') }}
                            </p>
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                {{ activeRevenuePoint.formatted }}
                            </p>
                        </div>

                        <!-- SVG Area & Curve -->
                        <svg
                            class="w-full h-64 overflow-visible"
                            :viewBox="`0 0 ${revenueSvg.width} ${revenueSvg.height}`"
                            preserveAspectRatio="none"
                        >
                            <defs>
                                <linearGradient id="revenue-gradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.28" />
                                    <stop offset="100%" stop-color="#10b981" stop-opacity="0.01" />
                                </linearGradient>
                            </defs>

                            <!-- Horizontal Gridlines -->
                            <g class="stroke-zinc-200/80 dark:stroke-zinc-800/80 stroke-dasharray-[2,4]">
                                <line
                                    v-for="(grid, i) in revenueSvg.gridLines"
                                    :key="i"
                                    :x1="revenueSvg.pad.left"
                                    :y1="grid.y"
                                    :x2="revenueSvg.width - revenueSvg.pad.right"
                                    :y2="grid.y"
                                    stroke-width="1"
                                />
                            </g>

                            <!-- Y-Axis Labels -->
                            <g class="fill-zinc-400 dark:fill-zinc-500 text-[11px] font-medium text-right select-none">
                                <text
                                    v-for="(grid, i) in revenueSvg.gridLines"
                                    :key="i"
                                    :x="revenueSvg.pad.left - 12"
                                    :y="grid.y + 4"
                                    text-anchor="end"
                                >
                                    {{ grid.label }}
                                </text>
                            </g>

                            <!-- Area Gradient Fill -->
                            <path
                                :d="revenueSvg.areaPath"
                                fill="url(#revenue-gradient)"
                            />

                            <!-- Line Path -->
                            <path
                                :d="revenueSvg.linePath"
                                fill="none"
                                stroke="#10b981"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <!-- Vertical Hover Guideline -->
                            <line
                                v-if="activeRevenuePoint"
                                :x1="activeRevenuePoint.x"
                                :y1="revenueSvg.pad.top"
                                :x2="activeRevenuePoint.x"
                                :y2="revenueSvg.height - revenueSvg.pad.bottom"
                                stroke="#10b981"
                                stroke-width="1.5"
                                stroke-dasharray="3,3"
                                class="opacity-70"
                            />

                            <!-- Interactive Points -->
                            <g>
                                <circle
                                    v-for="(pt, idx) in revenueSvg.points"
                                    :key="idx"
                                    :cx="pt.x"
                                    :cy="pt.y"
                                    :r="activeRevenuePoint?.month === pt.month ? 6.5 : 4"
                                    class="cursor-pointer transition-all duration-150"
                                    :class="activeRevenuePoint?.month === pt.month
                                        ? 'fill-emerald-500 stroke-4 stroke-white dark:stroke-zinc-900'
                                        : 'fill-white stroke-2.5 stroke-emerald-500 dark:fill-zinc-900'"
                                    @mouseenter="activeRevenuePoint = pt"
                                    @mouseleave="activeRevenuePoint = null"
                                />
                            </g>

                            <!-- X-Axis Labels -->
                            <g class="fill-zinc-400 dark:fill-zinc-500 text-[11px] select-none">
                                <text
                                    v-for="(pt, idx) in revenueSvg.points"
                                    :key="idx"
                                    :x="pt.x"
                                    :y="revenueSvg.height - 12"
                                    text-anchor="middle"
                                >
                                    {{ formatMonth(pt.month) }}
                                </text>
                            </g>
                        </svg>
                    </div>
                </div>
            </Card>

            <!-- ─── 4. Organization & Subscription Growth ─────────────────────── -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Organization Growth -->
                <Card :no-padding="true">
                    <template #header>
                        <div class="flex items-center justify-between w-full">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Organization Growth
                                </h3>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    New tenants onboarded per month.
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                {{ summary.organizations }} Total
                            </span>
                        </div>
                    </template>

                    <div class="p-6">
                        <div v-if="!orgSvg || organizationTrend.length === 0" class="py-10">
                            <EmptyState
                                title="No organization data"
                                description="No organizations were created during this timeframe."
                            />
                        </div>

                        <div v-else class="relative w-full overflow-hidden">
                            <!-- Tooltip -->
                            <div
                                v-if="activeOrgPoint"
                                class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full transform rounded-lg border border-zinc-200 bg-white/95 px-2.5 py-1.5 text-xs shadow-md backdrop-blur-xs dark:border-zinc-700 dark:bg-zinc-800/95"
                                :style="{
                                    left: `${(activeOrgPoint.x / orgSvg.width) * 100}%`,
                                    top: `${(activeOrgPoint.y / orgSvg.height) * 100 - 8}%`,
                                }"
                            >
                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400">
                                    {{ formatMonth(activeOrgPoint.month, 'full') }}
                                </p>
                                <p class="font-bold text-blue-600 dark:text-blue-400">
                                    {{ activeOrgPoint.value }} {{ activeOrgPoint.value === 1 ? 'organization' : 'organizations' }}
                                </p>
                            </div>

                            <svg
                                class="w-full h-48 overflow-visible"
                                :viewBox="`0 0 ${orgSvg.width} ${orgSvg.height}`"
                                preserveAspectRatio="none"
                            >
                                <defs>
                                    <linearGradient id="org-gradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.25" />
                                        <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.01" />
                                    </linearGradient>
                                </defs>

                                <g class="stroke-zinc-200/80 dark:stroke-zinc-800/80 stroke-dasharray-[2,4]">
                                    <line
                                        v-for="(grid, i) in orgSvg.gridLines"
                                        :key="i"
                                        :x1="orgSvg.pad.left"
                                        :y1="grid.y"
                                        :x2="orgSvg.width - orgSvg.pad.right"
                                        :y2="grid.y"
                                        stroke-width="1"
                                    />
                                </g>

                                <g class="fill-zinc-400 dark:fill-zinc-500 text-[10px] select-none">
                                    <text
                                        v-for="(grid, i) in orgSvg.gridLines"
                                        :key="i"
                                        :x="orgSvg.pad.left - 8"
                                        :y="grid.y + 3"
                                        text-anchor="end"
                                    >
                                        {{ grid.label }}
                                    </text>
                                </g>

                                <path :d="orgSvg.areaPath" fill="url(#org-gradient)" />

                                <path
                                    :d="orgSvg.linePath"
                                    fill="none"
                                    stroke="#3b82f6"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <circle
                                    v-for="(pt, idx) in orgSvg.points"
                                    :key="idx"
                                    :cx="pt.x"
                                    :cy="pt.y"
                                    :r="activeOrgPoint?.month === pt.month ? 5.5 : 3.5"
                                    class="cursor-pointer transition-all duration-150"
                                    :class="activeOrgPoint?.month === pt.month
                                        ? 'fill-blue-500 stroke-3 stroke-white dark:stroke-zinc-900'
                                        : 'fill-white stroke-2 stroke-blue-500 dark:fill-zinc-900'"
                                    @mouseenter="activeOrgPoint = pt"
                                    @mouseleave="activeOrgPoint = null"
                                />

                                <g class="fill-zinc-400 dark:fill-zinc-500 text-[10px] select-none">
                                    <text
                                        v-for="(pt, idx) in orgSvg.points"
                                        :key="idx"
                                        :x="pt.x"
                                        :y="orgSvg.height - 10"
                                        text-anchor="middle"
                                    >
                                        {{ formatMonth(pt.month) }}
                                    </text>
                                </g>
                            </svg>
                        </div>
                    </div>
                </Card>

                <!-- Subscription Growth -->
                <Card :no-padding="true">
                    <template #header>
                        <div class="flex items-center justify-between w-full">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Subscription Growth
                                </h3>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    New subscription tiers activated.
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                {{ summary.subscriptions }} Total
                            </span>
                        </div>
                    </template>

                    <div class="p-6">
                        <div v-if="!subSvg || subscriptionTrend.length === 0" class="py-10">
                            <EmptyState
                                title="No subscription data"
                                description="No subscriptions were created during this timeframe."
                            />
                        </div>

                        <div v-else class="relative w-full overflow-hidden">
                            <!-- Tooltip -->
                            <div
                                v-if="activeSubPoint"
                                class="pointer-events-none absolute z-20 -translate-x-1/2 -translate-y-full transform rounded-lg border border-zinc-200 bg-white/95 px-2.5 py-1.5 text-xs shadow-md backdrop-blur-xs dark:border-zinc-700 dark:bg-zinc-800/95"
                                :style="{
                                    left: `${(activeSubPoint.x / subSvg.width) * 100}%`,
                                    top: `${(activeSubPoint.y / subSvg.height) * 100 - 8}%`,
                                }"
                            >
                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400">
                                    {{ formatMonth(activeSubPoint.month, 'full') }}
                                </p>
                                <p class="font-bold text-purple-600 dark:text-purple-400">
                                    {{ activeSubPoint.value }} {{ activeSubPoint.value === 1 ? 'subscription' : 'subscriptions' }}
                                </p>
                            </div>

                            <svg
                                class="w-full h-48 overflow-visible"
                                :viewBox="`0 0 ${subSvg.width} ${subSvg.height}`"
                                preserveAspectRatio="none"
                            >
                                <defs>
                                    <linearGradient id="sub-gradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.25" />
                                        <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0.01" />
                                    </linearGradient>
                                </defs>

                                <g class="stroke-zinc-200/80 dark:stroke-zinc-800/80 stroke-dasharray-[2,4]">
                                    <line
                                        v-for="(grid, i) in subSvg.gridLines"
                                        :key="i"
                                        :x1="subSvg.pad.left"
                                        :y1="grid.y"
                                        :x2="subSvg.width - subSvg.pad.right"
                                        :y2="grid.y"
                                        stroke-width="1"
                                    />
                                </g>

                                <g class="fill-zinc-400 dark:fill-zinc-500 text-[10px] select-none">
                                    <text
                                        v-for="(grid, i) in subSvg.gridLines"
                                        :key="i"
                                        :x="subSvg.pad.left - 8"
                                        :y="grid.y + 3"
                                        text-anchor="end"
                                    >
                                        {{ grid.label }}
                                    </text>
                                </g>

                                <path :d="subSvg.areaPath" fill="url(#sub-gradient)" />

                                <path
                                    :d="subSvg.linePath"
                                    fill="none"
                                    stroke="#8b5cf6"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <circle
                                    v-for="(pt, idx) in subSvg.points"
                                    :key="idx"
                                    :cx="pt.x"
                                    :cy="pt.y"
                                    :r="activeSubPoint?.month === pt.month ? 5.5 : 3.5"
                                    class="cursor-pointer transition-all duration-150"
                                    :class="activeSubPoint?.month === pt.month
                                        ? 'fill-purple-500 stroke-3 stroke-white dark:stroke-zinc-900'
                                        : 'fill-white stroke-2 stroke-purple-500 dark:fill-zinc-900'"
                                    @mouseenter="activeSubPoint = pt"
                                    @mouseleave="activeSubPoint = null"
                                />

                                <g class="fill-zinc-400 dark:fill-zinc-500 text-[10px] select-none">
                                    <text
                                        v-for="(pt, idx) in subSvg.points"
                                        :key="idx"
                                        :x="pt.x"
                                        :y="subSvg.height - 10"
                                        text-anchor="middle"
                                    >
                                        {{ formatMonth(pt.month) }}
                                    </text>
                                </g>
                            </svg>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- ─── 5. Subscription Distribution (Donut & Plan Legend) ────────── -->
            <Card :no-padding="true">
                <template #header>
                    <div class="flex items-center justify-between w-full">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Subscription Distribution
                            </h2>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Active and trialing subscriptions by plan.
                            </p>
                        </div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ distributionData.total }} Total Active Tiers
                        </span>
                    </div>
                </template>

                <div class="p-6">
                    <div v-if="distributionData.slices.length === 0" class="py-12">
                        <EmptyState
                            title="No active subscription plans"
                            description="No tenants are currently subscribed to active or trialing plans."
                        />
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <!-- Left: SVG Donut Visualization -->
                        <div class="flex flex-col items-center justify-center relative py-4">
                            <svg class="h-60 w-60 -rotate-90 transform overflow-visible" viewBox="0 0 220 220">
                                <!-- Background Track -->
                                <circle
                                    cx="110"
                                    cy="110"
                                    :r="distributionData.radius"
                                    fill="transparent"
                                    class="stroke-zinc-100 dark:stroke-zinc-800/80"
                                    :stroke-width="distributionData.strokeWidth"
                                />

                                <!-- Slices -->
                                <circle
                                    v-for="slice in distributionData.slices"
                                    :key="slice.id"
                                    cx="110"
                                    cy="110"
                                    :r="distributionData.radius"
                                    fill="transparent"
                                    :stroke="slice.color"
                                    :stroke-width="activeDonutSlice === slice.id ? distributionData.strokeWidth + 4 : distributionData.strokeWidth"
                                    :stroke-dasharray="slice.dashArray"
                                    :stroke-dashoffset="slice.dashOffset"
                                    stroke-linecap="round"
                                    class="transition-all duration-300 cursor-pointer"
                                    @mouseenter="activeDonutSlice = slice.id"
                                    @mouseleave="activeDonutSlice = null"
                                />
                            </svg>

                            <!-- Center Total & Label -->
                            <div class="absolute flex flex-col items-center justify-center text-center pointer-events-none">
                                <span class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                                    {{ formatNumber(distributionData.total) }}
                                </span>
                                <span class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                    Active Subs
                                </span>
                            </div>
                        </div>

                        <!-- Right: Legend / Breakdown List -->
                        <div class="space-y-3.5">
                            <div
                                v-for="slice in distributionData.slices"
                                :key="slice.id"
                                class="rounded-xl border border-zinc-100 p-3.5 transition-all dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/30"
                                :class="{
                                    'ring-2 ring-primary-500/50 bg-white dark:bg-zinc-800': activeDonutSlice === slice.id,
                                }"
                                @mouseenter="activeDonutSlice = slice.id"
                                @mouseleave="activeDonutSlice = null"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="h-3 w-3 rounded-full shrink-0"
                                            :style="{ backgroundColor: slice.color }"
                                        />
                                        <span class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ slice.name }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                            {{ slice.value }} {{ slice.value === 1 ? 'sub' : 'subs' }}
                                        </span>
                                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                            {{ slice.percentage }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-2.5 h-1.5 w-full rounded-full bg-zinc-200/80 dark:bg-zinc-700/60 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{
                                            width: `${slice.percentage}%`,
                                            backgroundColor: slice.color,
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- ─── 6. Recent 30-Day Growth ───────────────────────────────────── -->
            <div>
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                        Last 30 Days
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Platform velocity and conversion activity across key indicators.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <!-- 1. Recent Organizations -->
                    <div class="rounded-xl border border-zinc-200/80 bg-white p-4 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                New Organizations
                            </span>
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                                +{{ recentGrowth.organizations }}
                            </span>
                            <span class="text-[11px] text-zinc-400">past 30d</span>
                        </div>
                    </div>

                    <!-- 2. Recent Users -->
                    <div class="rounded-xl border border-zinc-200/80 bg-white p-4 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                New Users
                            </span>
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                                +{{ recentGrowth.users }}
                            </span>
                            <span class="text-[11px] text-zinc-400">past 30d</span>
                        </div>
                    </div>

                    <!-- 3. Recent Subscriptions -->
                    <div class="rounded-xl border border-zinc-200/80 bg-white p-4 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                New Subscriptions
                            </span>
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                                +{{ recentGrowth.subscriptions }}
                            </span>
                            <span class="text-[11px] text-zinc-400">past 30d</span>
                        </div>
                    </div>

                    <!-- 4. Recent Payments -->
                    <div class="rounded-xl border border-zinc-200/80 bg-white p-4 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                Processed Payments
                            </span>
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                                +{{ recentGrowth.payments }}
                            </span>
                            <span class="text-[11px] text-zinc-400">transactions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
