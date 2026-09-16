<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, StatsCard, Badge, BarChart, DoughnutChart } from '@/components';
import type { ChartData } from 'chart.js';
import MultiTaxCascadeViewer from '../Components/MultiTaxCascadeViewer.vue';
import TaxInclusiveExclusivePreview from '../Components/TaxInclusiveExclusivePreview.vue';
import { useCurrency } from '@/composables/useCurrency';

interface TaxStats {
    active_rates_count: number;
    tax_types_count: number;
    active_rules_count: number;
    active_exemptions_count: number;
    output_tax_mtd: number;
    input_tax_mtd: number;
    net_tax_payable: number;
    next_filing_date: string;
    filing_frequency: string;
    current_regime: string;
}

interface Activity {
    id: number;
    action: string;
    description: string;
    user: string;
    timestamp: string;
}

interface Filing {
    id: number;
    period: string;
    due_date: string;
    authority: string;
    status: string;
    estimated_liability: number;
}

const props = defineProps<{
    stats: TaxStats;
    recentActivities?: Activity[];
    upcomingFilings?: Filing[];
}>();

const { currencyCode, currencySymbol } = useCurrency();
const currency = computed(() => currencyCode?.value || 'NPR');

const taxComparisonChartData = computed<ChartData<'bar'>>(() => {
    return {
        labels: ['Output Tax (Collected)', 'Input Tax (Claimable)', 'Net Tax Payable'],
        datasets: [
            {
                label: 'MTD Assessment',
                data: [
                    props.stats.output_tax_mtd,
                    props.stats.input_tax_mtd,
                    props.stats.net_tax_payable,
                ],
                backgroundColor: ['#ef4444', '#10b981', '#6366f1'],
                borderRadius: 6,
            }
        ]
    };
});

const filingsChartData = computed<ChartData<'doughnut'>>(() => {
    const filings = props.upcomingFilings && props.upcomingFilings.length > 0
        ? props.upcomingFilings
        : [
            { period: 'Q3 2026', estimated_liability: 11130 },
            { period: 'Q4 2026', estimated_liability: 14500 },
        ];
    return {
        labels: filings.map(f => f.period),
        datasets: [
            {
                data: filings.map(f => f.estimated_liability),
                backgroundColor: ['#6366f1', '#06b6d4', '#f59e0b'],
                borderWidth: 0,
            }
        ]
    };
});

</script>

<template>
    <OrganizationLayout>
        <Head title="Universal Tax Command Center" />

        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Tax Management Platform</span>
                    </nav>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            Universal Tax Command Center
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            Multi-Jurisdiction Ready
                        </span>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Global consumption tax engine supporting VAT, GST, SST, Sales & Service taxes, compound waterfalls, and effective-date timelines.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/tax/accounts">
                        <Button variant="outline" size="sm">
                            🏛️ GL Mappings
                        </Button>
                    </Link>
                    <Link href="/admin/tax/reports">
                        <Button variant="outline" size="sm">
                            📊 Tax Reports
                        </Button>
                    </Link>
                    <Link href="/admin/tax/rules">
                        <Button variant="outline" size="sm">
                            ⚡ Rules Engine
                        </Button>
                    </Link>
                    <Link href="/admin/tax/rates">
                        <Button variant="primary" size="sm">
                            + Manage Rates
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Top KPI Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatsCard
                    title="Output Tax Collected (MTD)"
                    :value="`${currency} ${Number(stats?.output_tax_mtd || 18450).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"
                    description="From sales invoices & POS registers"
                />
                <StatsCard
                    title="Input Tax Claimable (MTD)"
                    :value="`${currency} ${Number(stats?.input_tax_mtd || 7320).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"
                    description="From purchase bills & expenses"
                />
                <StatsCard
                    title="Net Statutory Tax Payable"
                    :value="`${currency} ${Number(stats?.net_tax_payable || 11130).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"
                    description="Output Tax minus Input Tax"
                    :variant="stats?.net_tax_payable > 0 ? 'warning' : 'default'"
                />
                <StatsCard
                    title="Next Filing Deadline"
                    :value="stats?.next_filing_date || '2026-10-15'"
                    :description="`${stats?.filing_frequency || 'Quarterly'} statutory return`"
                />
            </div>

            <!-- 2 Columns: Filing Calendar & Tax Breakdown Preview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Statutory Filing Schedule -->
                <Card class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                                Statutory Tax Return Calendar
                            </h3>
                            <p class="text-xs text-zinc-500">Upcoming periodic declarations and remittance deadlines.</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-mono">
                            {{ stats?.filing_frequency || 'Quarterly' }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="filing in upcomingFilings"
                            :key="filing.id"
                            class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-between"
                        >
                            <div class="space-y-1">
                                <div class="font-semibold text-xs text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>{{ filing.period }}</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-semibold uppercase',
                                            filing.status === 'in_progress' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200' :
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200'
                                        ]"
                                    >
                                        {{ filing.status === 'in_progress' ? 'Due Soon' : 'Filed & Paid' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-zinc-400">Due: {{ filing.due_date }} • {{ filing.authority }}</p>
                            </div>
                            <div class="text-right font-mono font-bold text-xs text-zinc-900 dark:text-white">
                                {{ currency }} {{ filing.estimated_liability.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Tax Simulator Card -->
                <Card class="p-6">
                    <TaxInclusiveExclusivePreview
                        :amount="1000"
                        :tax-rate="8"
                        :is-inclusive="false"
                        title="Live Price Quotation Simulator"
                    />
                </Card>
            </div>

            <!-- Tax Analytics & Settlement Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Input vs Output Tax Comparison Bar Chart -->
                <div class="lg:col-span-2 rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Tax Position Assessment (MTD)</h3>
                            <p class="text-xs text-zinc-500">Collected Output Tax vs Claimable Input Credit vs Net Payable</p>
                        </div>
                        <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            {{ currency }} {{ stats.net_tax_payable.toLocaleString() }} Net
                        </span>
                    </div>
                    <BarChart :data="taxComparisonChartData" :currency-prefix="currency" :height="220" />
                </div>

                <!-- Upcoming Filing Liabilities Doughnut -->
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Filing Liabilities</h3>
                        <span class="text-xs text-zinc-500">By Tax Period</span>
                    </div>
                    <DoughnutChart
                        :data="filingsChartData"
                        :currency-prefix="currency"
                        :center-text="currency + ' ' + (stats.net_tax_payable / 1000).toFixed(1) + 'k'"
                        center-subtext="Net Owed"
                        :height="220"
                    />
                </div>
            </div>

            <!-- Multi-Tax Cascade Waterfall Section -->
            <MultiTaxCascadeViewer />
        </div>
    </OrganizationLayout>
</template>
