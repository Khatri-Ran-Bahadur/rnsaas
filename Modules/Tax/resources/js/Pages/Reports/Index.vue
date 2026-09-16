<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn, StatsCard } from '@/components';

interface TaxSummary {
    gross_sales_taxable: number;
    output_tax_collected: number;
    gross_purchases_taxable: number;
    input_tax_claimable: number;
    net_tax_payable: number;
    zero_rated_sales: number;
    exempt_sales: number;
    total_gross_turnover: number;
}

interface RateBreakdown {
    rate_name: string;
    rate_percentage: number;
    sales_taxable_amount: number;
    output_tax: number;
    purchases_taxable_amount: number;
    input_tax: number;
    net_tax: number;
}

interface OutputTx {
    id: number;
    date: string;
    document_number: string;
    customer_name: string;
    customer_tax_id: string;
    channel: string;
    taxable_base: number;
    tax_rate_applied: string;
    tax_amount: number;
    total_amount: number;
    tax_category: string;
}

interface InputTx {
    id: number;
    date: string;
    document_number: string;
    vendor_name: string;
    vendor_tax_id: string;
    taxable_base: number;
    tax_rate_applied: string;
    tax_amount: number;
    is_claimable: boolean;
    total_amount: number;
}

const props = defineProps<{
    reportType: string;
    summaryData: TaxSummary;
    byRateBreakdown: RateBreakdown[];
    outputTransactions: OutputTx[];
    inputTransactions: InputTx[];
    filters: {
        from_date?: string;
        to_date?: string;
        branch_id?: string;
        tax_rate_id?: string;
        report_type?: string;
    };
}>();

import { useCurrency } from '@/composables/useCurrency';

const { currencyCode, currencySymbol } = useCurrency();
const currency = computed(() => currencyCode?.value || 'NPR');

const currentTab = ref<'summary' | 'by_rate' | 'output' | 'input'>(
    (props.reportType as any) || 'summary'
);

const fromDate = ref(props.filters.from_date || '2026-07-01');
const toDate = ref(props.filters.to_date || '2026-09-30');

const applyFilter = () => {
    router.get(
        '/admin/tax/reports',
        {
            report_type: currentTab.value,
            from_date: fromDate.value,
            to_date: toDate.value,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const switchTab = (tab: 'summary' | 'by_rate' | 'output' | 'input') => {
    currentTab.value = tab;
};

const outputColumns: TableColumn[] = [
    { key: 'date', label: 'Date', sortable: true },
    { key: 'doc', label: 'Document #' },
    { key: 'customer', label: 'Customer & Tax ID' },
    { key: 'channel', label: 'Sales Channel' },
    { key: 'base', label: 'Taxable Base', align: 'right' },
    { key: 'rate', label: 'Rate Applied', align: 'center' },
    { key: 'tax', label: 'Tax Amount', align: 'right' },
    { key: 'total', label: 'Gross Total', align: 'right' },
];

const inputColumns: TableColumn[] = [
    { key: 'date', label: 'Date', sortable: true },
    { key: 'doc', label: 'Document #' },
    { key: 'vendor', label: 'Vendor & Tax ID' },
    { key: 'base', label: 'Taxable Base', align: 'right' },
    { key: 'rate', label: 'Rate Applied', align: 'center' },
    { key: 'claimable', label: 'Recoverable Credit', align: 'center' },
    { key: 'tax', label: 'Tax Amount', align: 'right' },
    { key: 'total', label: 'Gross Total', align: 'right' },
];
</script>

<template>
    <OrganizationLayout>
        <Head title="Statutory Tax Reports & Returns" />

        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Tax Reports</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Statutory Tax Returns & Audit Reports
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Comprehensive periodic tax return calculation, input vs output reconciliation, and line-level transaction registers.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Button variant="outline" size="sm" onclick="window.print()">
                        🖨️ Print Statement
                    </Button>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <Card class="p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Period From</label>
                            <input
                                v-model="fromDate"
                                type="date"
                                class="px-2.5 py-1.5 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Period To</label>
                            <input
                                v-model="toDate"
                                type="date"
                                class="px-2.5 py-1.5 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            />
                        </div>

                        <div class="pt-4">
                            <Button variant="secondary" size="sm" @click="applyFilter">
                                Update Period
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Navigation Tabs -->
            <div class="border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-2 overflow-x-auto">
                <button
                    type="button"
                    @click="switchTab('summary')"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        currentTab === 'summary'
                            ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    📑 Statutory Tax Return Summary
                </button>

                <button
                    type="button"
                    @click="switchTab('by_rate')"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        currentTab === 'by_rate'
                            ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    📊 Tax by Rate & Category
                </button>

                <button
                    type="button"
                    @click="switchTab('output')"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        currentTab === 'output'
                            ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    📤 Output Tax Register (Sales)
                </button>

                <button
                    type="button"
                    @click="switchTab('input')"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        currentTab === 'input'
                            ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    📥 Input Tax Register (Purchases)
                </button>
            </div>

            <!-- Tab 1: Summary Return -->
            <div v-if="currentTab === 'summary'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card class="p-5 space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase">Gross Taxable Turnover</span>
                        <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white">
                            {{ currency }} {{ summaryData.gross_sales_taxable.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </div>
                        <p class="text-[11px] text-zinc-400">Total rateable supplies</p>
                    </Card>

                    <Card class="p-5 space-y-1">
                        <span class="text-xs font-semibold text-amber-600 uppercase">Total Output Tax (Sales)</span>
                        <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">
                            {{ currency }} {{ summaryData.output_tax_collected.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </div>
                        <p class="text-[11px] text-zinc-400">Tax collected from buyers</p>
                    </Card>

                    <Card class="p-5 space-y-1">
                        <span class="text-xs font-semibold text-blue-600 uppercase">Total Input Tax (Purchases)</span>
                        <div class="text-2xl font-bold font-mono text-blue-600 dark:text-blue-400">
                            {{ currency }} {{ summaryData.input_tax_claimable.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </div>
                        <p class="text-[11px] text-zinc-400">Claimable input tax credit</p>
                    </Card>
                </div>

                <!-- Statutory Return Calculation Box -->
                <Card class="p-6">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-4 mb-4">
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                                Periodic Statutory Tax Declaration
                            </h3>
                            <p class="text-xs text-zinc-500">Period: {{ fromDate }} through {{ toDate }}</p>
                        </div>
                        <span class="text-xs font-mono font-bold px-3 py-1 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                            Status: Reconciled
                        </span>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center text-zinc-700 dark:text-zinc-300">
                            <span>1. Total Output Tax Payable (Box 1):</span>
                            <span class="font-mono font-bold">{{ currency }} {{ summaryData.output_tax_collected.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-zinc-700 dark:text-zinc-300">
                            <span>2. Less: Total Input Tax Claimable (Box 2):</span>
                            <span class="font-mono font-bold text-blue-600">- {{ currency }} {{ summaryData.input_tax_claimable.toFixed(2) }}</span>
                        </div>
                        <div class="h-px bg-zinc-200 dark:bg-zinc-800 my-2"></div>
                        <div class="flex justify-between items-center text-base font-bold text-zinc-900 dark:text-white pt-1">
                            <span>3. Net Tax Amount Payable to Authority (Box 1 − Box 2):</span>
                            <span class="font-mono text-xl text-emerald-600 dark:text-emerald-400">
                                {{ currency }} {{ summaryData.net_tax_payable.toFixed(2) }}
                            </span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Tab 2: Tax by Rate Breakdown -->
            <div v-if="currentTab === 'by_rate'">
                <Card class="p-0 overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 font-semibold text-zinc-500 uppercase">
                                <th class="p-3">Tax Rate Code</th>
                                <th class="p-3 text-right">Sales Taxable Base</th>
                                <th class="p-3 text-right">Output Tax</th>
                                <th class="p-3 text-right">Purchases Taxable Base</th>
                                <th class="p-3 text-right">Input Tax</th>
                                <th class="p-3 text-right">Net Statutory Tax</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="r in byRateBreakdown" :key="r.rate_name">
                                <td class="p-3 font-semibold text-zinc-900 dark:text-white">{{ r.rate_name }}</td>
                                <td class="p-3 text-right font-mono">{{ currency }} {{ r.sales_taxable_amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                                <td class="p-3 text-right font-mono font-bold text-amber-600">{{ currency }} {{ r.output_tax.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                                <td class="p-3 text-right font-mono">{{ currency }} {{ r.purchases_taxable_amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                                <td class="p-3 text-right font-mono font-bold text-blue-600">{{ currency }} {{ r.input_tax.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                                <td class="p-3 text-right font-mono font-bold text-zinc-900 dark:text-white">{{ currency }} {{ r.net_tax.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </Card>
            </div>

            <!-- Tab 3: Output Tax Register -->
            <div v-if="currentTab === 'output'">
                <Card class="p-0 overflow-hidden">
                    <DataTable :columns="outputColumns" :data="outputTransactions">
                        <template #cell-date="{ row }"><span class="font-mono text-xs">{{ row.date }}</span></template>
                        <template #cell-doc="{ row }"><span class="font-mono font-bold text-xs">{{ row.document_number }}</span></template>
                        <template #cell-customer="{ row }">
                            <div>
                                <div class="font-semibold text-xs text-zinc-900 dark:text-white">{{ row.customer_name }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">TIN: {{ row.customer_tax_id }}</div>
                            </div>
                        </template>
                        <template #cell-channel="{ row }"><span class="text-xs">{{ row.channel }}</span></template>
                        <template #cell-base="{ row }"><span class="font-mono font-semibold">{{ currency }} {{ row.taxable_base.toFixed(2) }}</span></template>
                        <template #cell-rate="{ row }"><span class="px-2 py-0.5 rounded font-mono text-xs bg-zinc-100 dark:bg-zinc-800">{{ row.tax_rate_applied }}</span></template>
                        <template #cell-tax="{ row }"><span class="font-mono font-bold text-amber-600">{{ currency }} {{ row.tax_amount.toFixed(2) }}</span></template>
                        <template #cell-total="{ row }"><span class="font-mono font-bold text-zinc-900 dark:text-white">{{ currency }} {{ row.total_amount.toFixed(2) }}</span></template>
                    </DataTable>
                </Card>
            </div>

            <!-- Tab 4: Input Tax Register -->
            <div v-if="currentTab === 'input'">
                <Card class="p-0 overflow-hidden">
                    <DataTable :columns="inputColumns" :data="inputTransactions">
                        <template #cell-date="{ row }"><span class="font-mono text-xs">{{ row.date }}</span></template>
                        <template #cell-doc="{ row }"><span class="font-mono font-bold text-xs">{{ row.document_number }}</span></template>
                        <template #cell-vendor="{ row }">
                            <div>
                                <div class="font-semibold text-xs text-zinc-900 dark:text-white">{{ row.vendor_name }}</div>
                                <div class="text-[11px] font-mono text-zinc-400">TIN: {{ row.vendor_tax_id }}</div>
                            </div>
                        </template>
                        <template #cell-base="{ row }"><span class="font-mono font-semibold">{{ currency }} {{ row.taxable_base.toFixed(2) }}</span></template>
                        <template #cell-rate="{ row }"><span class="px-2 py-0.5 rounded font-mono text-xs bg-zinc-100 dark:bg-zinc-800">{{ row.tax_rate_applied }}</span></template>
                        <template #cell-claimable="{ row }">
                            <span v-if="row.is_claimable" class="text-xs font-bold text-emerald-600">✓ Claimable</span>
                            <span v-else class="text-xs text-zinc-400">✕ Blocked</span>
                        </template>
                        <template #cell-tax="{ row }"><span class="font-mono font-bold text-blue-600">{{ currency }} {{ row.tax_amount.toFixed(2) }}</span></template>
                        <template #cell-total="{ row }"><span class="font-mono font-bold text-zinc-900 dark:text-white">{{ currency }} {{ row.total_amount.toFixed(2) }}</span></template>
                    </DataTable>
                </Card>
            </div>
        </div>
    </OrganizationLayout>
</template>
