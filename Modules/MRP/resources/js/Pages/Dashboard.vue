<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BarChart from '@/components/charts/BarChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import type { ChartData } from 'chart.js';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';
import ProductionGanttTimeline from '../Components/ProductionGanttTimeline.vue';

interface Props {
    stats: {
        active_work_orders: number;
        wip_value: number;
        output_today_units: number;
        scrap_rate_percent: number;
        active_work_centers: number;
        planned_orders_count: number;
        critical_shortages_count: number;
        oee_percent: number;
    };
    activeOrders: Array<{
        id: number;
        wo_number: string;
        product_name: string;
        sku: string;
        batch_number: string;
        work_center: string;
        planned_qty: number;
        produced_qty: number;
        scrap_qty: number;
        progress_percent: number;
        status: string;
        priority: string;
        operator: string;
        due_date: string;
    }>;
    recentActivities: Array<{
        id: number;
        action: string;
        description: string;
        user: string;
        timestamp: string;
    }>;
    timelineEvents: Array<any>;
    productionTrends?: Array<{ date: string; planned: number; actual: number; wastage: number }>;
    workCenterLoads?: Array<{ name: string; load_percent: number; status: string }>;
}

const props = withDefaults(
    defineProps<Props>(),
    {
        stats: () => ({
            active_work_orders: 0,
            wip_value: 0,
            output_today_units: 0,
            scrap_rate_percent: 0,
            active_work_centers: 0,
            planned_orders_count: 0,
            critical_shortages_count: 0,
            oee_percent: 0,
        }),
        activeOrders: () => [],
        recentActivities: () => [],
        timelineEvents: () => [],
    }
);

const getPriorityBadgeVariant = (priority: string) => {
    switch (priority.toLowerCase()) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'normal': return 'info';
        default: return 'secondary';
    }
};

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'completed': return 'success';
        case 'in progress': return 'primary';
        case 'planned': return 'secondary';
        default: return 'secondary';
    }
};

const productionTrendChartData = computed<ChartData<'line'>>(() => {
    const trends = props.productionTrends && props.productionTrends.length > 0
        ? props.productionTrends
        : [
            { date: 'Sep 05', planned: 950, actual: 920 },
            { date: 'Sep 06', planned: 1100, actual: 1080 },
            { date: 'Sep 07', planned: 1250, actual: 1210 },
            { date: 'Sep 08', planned: 1400, actual: 1390 },
            { date: 'Sep 09', planned: 1300, actual: 1270 },
            { date: 'Sep 10', planned: 1500, actual: 1460 },
            { date: 'Sep 11', planned: 1600, actual: 1540 },
        ];

    return {
        labels: trends.map(t => t.date.length > 5 ? t.date.slice(5) : t.date),
        datasets: [
            {
                label: 'Actual Output (Units)',
                data: trends.map(t => t.actual),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
            },
            {
                label: 'Planned Target (Units)',
                data: trends.map(t => t.planned),
                borderColor: '#6366f1',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
            },
        ]
    };
});

const workCenterLoadChartData = computed<ChartData<'bar'>>(() => {
    const loads = props.workCenterLoads && props.workCenterLoads.length > 0
        ? props.workCenterLoads
        : [
            { name: 'Oven Deck Line A', load_percent: 88 },
            { name: 'Mixing & Proofing Station', load_percent: 74 },
            { name: 'Packaging Conveyor 1', load_percent: 92 },
            { name: 'Cold Proofing Cell', load_percent: 65 },
        ];

    return {
        labels: loads.map(l => l.name.split(' ')[0] + ' ' + (l.name.split(' ')[1] || '')),
        datasets: [
            {
                label: 'Capacity Load %',
                data: loads.map(l => l.load_percent),
                backgroundColor: loads.map(l => l.load_percent > 85 ? '#f59e0b' : '#3b82f6'),
                borderRadius: 6,
            }
        ]
    };
});

</script>

<template>
    <Head title="Manufacturing & MRP" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Hero Header with Quick Actions -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 rounded-3xl p-6 lg:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center space-x-2.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            MRP Engine v3.2
                        </span>
                        <span class="inline-flex items-center text-xs text-emerald-400 font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span> All Systems Operational
                        </span>
                    </div>
                    <div class="mt-3 flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black tracking-tight">MRP & Manufacturing Hub</h1>
                            <p class="text-xs text-slate-300 mt-0.5">Production planning, work orders, BOM recipes, and shop-floor execution</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Link
                        href="/admin/mrp/execution"
                        class="inline-flex items-center px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-lg shadow-emerald-900/40 transition-all active:scale-95"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Shop Floor Terminal
                    </Link>
                    <Link
                        href="/admin/mrp/planning"
                        class="inline-flex items-center px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-900/40 transition-all"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Run MRP Engine
                    </Link>
                    <Link
                        href="/admin/mrp/work-orders/create"
                        class="inline-flex items-center px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-sm border border-slate-700 transition-all"
                    >
                        + New Work Order
                    </Link>
                </div>
            </div>

            <!-- 8 Production Stat Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Active Work Orders</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">{{ stats.active_work_orders }} Orders</span>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-1 inline-block">● On Track</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Work In Progress (WIP)</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">${{ stats.wip_value.toLocaleString() }}</span>
                    <span class="text-[11px] text-slate-500 mt-1 inline-block">Real-time valuation</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Today's Output Units</span>
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">{{ stats.output_today_units.toLocaleString() }} Units</span>
                    <span class="text-[11px] text-slate-500 mt-1 inline-block">FG receipts posted</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Plant OEE Efficiency</span>
                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 block">{{ stats.oee_percent }}%</span>
                    <span class="text-[11px] text-slate-500 mt-1 inline-block">Benchmark: >85%</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Scrap / Defect Rate</span>
                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 block">{{ stats.scrap_rate_percent }}%</span>
                    <span class="text-[11px] text-slate-500 mt-1 inline-block">Tolerance: &lt;2.0%</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Active Work Centers</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">{{ stats.active_work_centers }} Centers</span>
                    <span class="text-[11px] text-emerald-600 font-medium mt-1 inline-block">100% Operational</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Planned Orders in Queue</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">{{ stats.planned_orders_count }} Orders</span>
                    <span class="text-[11px] text-slate-500 mt-1 inline-block">Next 14 days horizon</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm" :class="{'border-red-300 dark:border-red-900 bg-red-50/20': stats.critical_shortages_count > 0}">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block">Material Shortages</span>
                    <span class="text-2xl font-black text-red-600 dark:text-red-400 mt-1 block">{{ stats.critical_shortages_count }} Items</span>
                    <span class="text-[11px] text-red-500 font-medium mt-1 inline-block">Action Required</span>
                </div>
            </div>

            <!-- Production Analytics & Machine Load Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Output vs Target Line Trend -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Production Output vs Target Run</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">7-day manufacturing velocity & output yields</p>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            96.2% Yield
                        </span>
                    </div>
                    <LineChart :data="productionTrendChartData" :height="220" />
                </div>

                <!-- Work Center Load Utilization -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Work Center Load</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Current machine utilization</p>
                        </div>
                        <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            OEE {{ stats.oee_percent }}%
                        </span>
                    </div>
                    <BarChart :data="workCenterLoadChartData" :height="220" />
                </div>
            </div>

            <!-- Schedule Timeline Widget -->
            <ProductionGanttTimeline :events="timelineEvents" />

            <!-- Active Production Runs Grid -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Active Production Runs</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Live progress tracking on the manufacturing line</p>
                    </div>
                    <Link href="/admin/mrp/work-orders" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        View All Work Orders &rarr;
                    </Link>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="order in activeOrders"
                        :key="order.id"
                        class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 transition-all"
                    >
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center space-x-2.5">
                                    <span class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ order.wo_number }}</span>
                                    <Badge :variant="getPriorityBadgeVariant(order.priority)" size="sm">{{ order.priority }}</Badge>
                                    <Badge :variant="getStatusBadgeVariant(order.status)" size="sm">{{ order.status }}</Badge>
                                </div>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ order.product_name }}</div>
                                <div class="text-xs text-slate-500 flex flex-wrap gap-x-3">
                                    <span>SKU: {{ order.sku }}</span>
                                    <span>Batch: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ order.batch_number }}</strong></span>
                                    <span>Work Center: {{ order.work_center }}</span>
                                    <span>Operator: {{ order.operator }}</span>
                                </div>
                            </div>

                            <div class="w-full lg:w-72 space-y-1.5">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Output Progress:</span>
                                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ order.produced_qty }} / {{ order.planned_qty }} ({{ order.progress_percent }}%)</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: `${order.progress_percent}%` }"></div>
                                </div>
                                <div class="flex justify-between text-[11px] text-slate-400">
                                    <span>Scrap: {{ order.scrap_qty }} units</span>
                                    <span>Due: {{ order.due_date }}</span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Link
                                    :href="`/admin/mrp/work-orders/${order.id}`"
                                    class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 shadow-sm"
                                >
                                    Details
                                </Link>
                                <Link
                                    href="/admin/mrp/execution"
                                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-900/30"
                                >
                                    Terminal
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation Hub -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <Link
                    href="/admin/mrp/bom"
                    class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 shadow-sm transition-all text-center group"
                >
                    <div class="w-10 h-10 mx-auto rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <span class="mt-2.5 block text-xs font-bold text-slate-900 dark:text-slate-100">Bill of Materials</span>
                    <span class="text-[10px] text-slate-400">Recipes & BOMs</span>
                </Link>

                <Link
                    href="/admin/mrp/material-issues"
                    class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 shadow-sm transition-all text-center group"
                >
                    <div class="w-10 h-10 mx-auto rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                    </div>
                    <span class="mt-2.5 block text-xs font-bold text-slate-900 dark:text-slate-100">Material Issues</span>
                    <span class="text-[10px] text-slate-400">Dispatch & Picklists</span>
                </Link>

                <Link
                    href="/admin/mrp/finished-goods"
                    class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 shadow-sm transition-all text-center group"
                >
                    <div class="w-10 h-10 mx-auto rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="mt-2.5 block text-xs font-bold text-slate-900 dark:text-slate-100">Finished Goods</span>
                    <span class="text-[10px] text-slate-400">FG & By-products</span>
                </Link>

                <Link
                    href="/admin/mrp/quality"
                    class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 shadow-sm transition-all text-center group"
                >
                    <div class="w-10 h-10 mx-auto rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="mt-2.5 block text-xs font-bold text-slate-900 dark:text-slate-100">Quality Control</span>
                    <span class="text-[10px] text-slate-400">Inspections & QA</span>
                </Link>

                <Link
                    href="/admin/mrp/traceability"
                    class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 shadow-sm transition-all text-center group"
                >
                    <div class="w-10 h-10 mx-auto rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                        </svg>
                    </div>
                    <span class="mt-2.5 block text-xs font-bold text-slate-900 dark:text-slate-100">Traceability</span>
                    <span class="text-[10px] text-slate-400">Batch Genealogy</span>
                </Link>

                <Link
                    href="/admin/mrp/costs"
                    class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 shadow-sm transition-all text-center group"
                >
                    <div class="w-10 h-10 mx-auto rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="mt-2.5 block text-xs font-bold text-slate-900 dark:text-slate-100">Job Costing</span>
                    <span class="text-[10px] text-slate-400">Variance Analysis</span>
                </Link>
            </div>
        </div>
    </OrganizationLayout>
</template>
