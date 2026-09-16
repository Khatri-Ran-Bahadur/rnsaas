<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge } from '@/components';

interface AnalyticsData {
    oee: {
        overall: number;
        availability: number;
        performance: number;
        quality: number;
    };
    work_center_utilization: Array<{
        code: string;
        name: string;
        utilization_rate: number;
        active_jobs: number;
        downtime_hours: number;
    }>;
    cost_variance: {
        standard_cost_total: number;
        actual_cost_total: number;
        variance_percentage: number;
    };
    scrap_rate_percentage: number;
    on_time_in_full_rate: number;
}

const props = defineProps<{
    analytics: AnalyticsData;
    filters: {
        date_range: string;
        facility_id?: number;
    };
}>();

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
};
</script>

<template>
    <Head title="Manufacturing Intelligence & OEE Analytics - MRP" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Manufacturing Intelligence & OEE Analytics
                    </h1>
                    <p class="text-xs text-slate-500">
                        Real-time plant productivity, Overall Equipment Effectiveness (OEE), scrap loss variance, and work center bottlenecks.
                    </p>
                </div>

                <div class="flex items-center space-x-2">
                    <select class="px-3 py-2 text-xs font-bold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl">
                        <option>This Month (Sep 2026)</option>
                        <option>Last 30 Days</option>
                        <option>Current Quarter (Q3)</option>
                        <option>Year to Date (YTD)</option>
                    </select>
                </div>
            </div>

            <!-- OEE Gauges -->
            <div class="bg-gradient-to-br from-indigo-900 via-slate-900 to-slate-950 rounded-2xl p-6 text-white border border-indigo-500/20 shadow-xl">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-indigo-500/20">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-widest text-indigo-400">Plant Overall Equipment Effectiveness</div>
                        <div class="text-4xl font-black tracking-tight mt-1 flex items-baseline gap-2">
                            <span>{{ analytics.oee.overall }}%</span>
                            <span class="text-xs font-bold text-emerald-400 bg-emerald-500/20 px-2 py-0.5 rounded-full">+3.2% vs last month</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">World-class benchmark target: 85.0%</p>
                    </div>

                    <div class="grid grid-cols-3 gap-4 md:gap-8">
                        <div class="text-center p-3 rounded-xl bg-white/5 border border-white/10">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Availability</div>
                            <div class="text-xl font-black text-indigo-300 mt-0.5">{{ analytics.oee.availability }}%</div>
                            <div class="text-[10px] text-slate-500">Uptime vs Planned</div>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-white/5 border border-white/10">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Performance</div>
                            <div class="text-xl font-black text-sky-300 mt-0.5">{{ analytics.oee.performance }}%</div>
                            <div class="text-[10px] text-slate-500">Speed vs Nameplate</div>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-white/5 border border-white/10">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Quality</div>
                            <div class="text-xl font-black text-emerald-300 mt-0.5">{{ analytics.oee.quality }}%</div>
                            <div class="text-[10px] text-slate-500">First Pass Yield</div>
                        </div>
                    </div>
                </div>

                <!-- Secondary KPI strip -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6">
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium">Standard Manufacturing Cost</div>
                        <div class="text-lg font-bold mt-0.5">{{ formatCurrency(analytics.cost_variance.standard_cost_total) }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium">Actual Cost Incurred</div>
                        <div class="text-lg font-bold mt-0.5 text-amber-400">{{ formatCurrency(analytics.cost_variance.actual_cost_total) }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium">Scrap & Waste Rate</div>
                        <div class="text-lg font-bold mt-0.5 text-rose-400">{{ analytics.scrap_rate_percentage }}%</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400 font-medium">On-Time In-Full (OTIF)</div>
                        <div class="text-lg font-bold mt-0.5 text-emerald-400">{{ analytics.on_time_in_full_rate }}%</div>
                    </div>
                </div>
            </div>

            <!-- Work Center Utilization Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Work Center Loading & Bottleneck Analysis</h3>
                        <p class="text-xs text-slate-500">Machine utilization rates and recorded unplanned downtime hours.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="wc in analytics.work_center_utilization"
                        :key="wc.code"
                        class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4"
                    >
                        <div class="w-64">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded">
                                    {{ wc.code }}
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ wc.name }}</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-1">
                                {{ wc.active_jobs }} active production jobs running
                            </div>
                        </div>

                        <!-- Progress Bar for utilization -->
                        <div class="flex-1 max-w-md">
                            <div class="flex justify-between text-xs font-bold mb-1">
                                <span class="text-slate-500">Capacity Utilization</span>
                                <span :class="wc.utilization_rate > 90 ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300'">
                                    {{ wc.utilization_rate }}%
                                </span>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="{
                                        'bg-rose-500': wc.utilization_rate >= 90,
                                        'bg-emerald-500': wc.utilization_rate >= 65 && wc.utilization_rate < 90,
                                        'bg-amber-500': wc.utilization_rate < 65
                                    }"
                                    :style="{ width: `${wc.utilization_rate}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ wc.downtime_hours }} hrs downtime
                            </div>
                            <div class="text-[11px]" :class="wc.downtime_hours > 5 ? 'text-rose-500 font-semibold' : 'text-slate-400'">
                                {{ wc.downtime_hours > 5 ? 'Maintenance alert' : 'Normal parameters' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
