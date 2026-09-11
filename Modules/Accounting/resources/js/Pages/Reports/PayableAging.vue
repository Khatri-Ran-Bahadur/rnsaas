<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, DatePicker } from '@/components';

interface AgingBuckets {
    current: string;
    days_1_30: string;
    days_31_60: string;
    days_61_90: string;
    days_90_plus: string;
    total: string;
}

interface VendorAgingItem extends AgingBuckets {
    vendor_id: number;
    vendor_code: string;
    vendor_name: string;
}

interface AgingReportData {
    as_of_date: string;
    summary: AgingBuckets;
    vendors: VendorAgingItem[];
}

interface Props {
    report: AgingReportData;
    asOfDate: string;
}

const props = defineProps<Props>();

const selectedDate = ref(props.asOfDate);

const changeDate = () => {
    router.get(
        '/admin/accounting/reports/payable-aging',
        { as_of_date: selectedDate.value },
        { preserveState: true, preserveScroll: true }
    );
};

const formatCurrency = (val: string | number) => {
    const num = parseFloat(String(val) || '0');
    return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <OrganizationLayout
        title="Accounts Payable Aging Report"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Reports' },
            { label: 'Payable Aging' },
        ]"
    >
        <Head title="Payable Aging Report - Accounting" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Accounts Payable Aging
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Outstanding supplier liabilities categorized into 30-day chronological aging periods.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-zinc-500">As Of Date:</label>
                        <div class="w-40">
                            <DatePicker
                                v-model="selectedDate"
                                placeholder="As of date"
                                @update:model-value="changeDate"
                            />
                        </div>
                    </div>

                    <Button variant="secondary" size="sm" @click="printReport">
                        <svg class="h-4 w-4 mr-1.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </Button>
                </div>
            </div>

            <!-- Aging Summary Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-zinc-500">Current (Not Due)</p>
                    <p class="mt-1 text-lg font-bold font-mono text-zinc-900 dark:text-zinc-100">{{ formatCurrency(report.summary.current) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-amber-600">1 - 30 Days</p>
                    <p class="mt-1 text-lg font-bold font-mono text-amber-600">{{ formatCurrency(report.summary.days_1_30) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-orange-600">31 - 60 Days</p>
                    <p class="mt-1 text-lg font-bold font-mono text-orange-600">{{ formatCurrency(report.summary.days_31_60) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-rose-500">61 - 90 Days</p>
                    <p class="mt-1 text-lg font-bold font-mono text-rose-500">{{ formatCurrency(report.summary.days_61_90) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-rose-700 dark:text-rose-400">90+ Days Overdue</p>
                    <p class="mt-1 text-lg font-bold font-mono text-rose-700 dark:text-rose-400">{{ formatCurrency(report.summary.days_90_plus) }}</p>
                </div>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 shadow-xs dark:border-indigo-900/60 dark:bg-indigo-950/40">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Total Payables</p>
                    <p class="mt-1 text-lg font-bold font-mono text-indigo-700 dark:text-indigo-300">{{ formatCurrency(report.summary.total) }}</p>
                </div>
            </div>

            <!-- Aging Breakdown Table -->
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                            <tr>
                                <th class="px-5 py-3.5">Vendor</th>
                                <th class="px-4 py-3.5 text-right">Current</th>
                                <th class="px-4 py-3.5 text-right">1-30 Days</th>
                                <th class="px-4 py-3.5 text-right">31-60 Days</th>
                                <th class="px-4 py-3.5 text-right">61-90 Days</th>
                                <th class="px-4 py-3.5 text-right">90+ Days</th>
                                <th class="px-5 py-3.5 text-right">Total Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="vendor in report.vendors"
                                :key="vendor.vendor_id"
                                class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/50"
                            >
                                <td class="px-5 py-3.5 font-medium text-zinc-900 dark:text-white">
                                    <Link
                                        :href="`/admin/accounting/reports/vendors/${vendor.vendor_id}/statement`"
                                        class="hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline"
                                    >
                                        {{ vendor.vendor_name }}
                                        <span class="font-mono text-[11px] text-zinc-400 font-normal ml-1">({{ vendor.vendor_code }})</span>
                                    </Link>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ formatCurrency(vendor.current) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ formatCurrency(vendor.days_1_30) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ formatCurrency(vendor.days_31_60) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ formatCurrency(vendor.days_61_90) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono font-medium text-rose-600 dark:text-rose-400">
                                    {{ formatCurrency(vendor.days_90_plus) }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(vendor.total) }}
                                </td>
                            </tr>
                        </tbody>
                        <!-- Total Row -->
                        <tfoot class="border-t-2 border-zinc-300 bg-zinc-50 font-bold dark:border-zinc-700 dark:bg-zinc-800/60">
                            <tr>
                                <td class="px-5 py-3.5 uppercase tracking-wider text-[11px] text-zinc-700 dark:text-zinc-300">
                                    Grand Total
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(report.summary.current) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(report.summary.days_1_30) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(report.summary.days_31_60) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(report.summary.days_61_90) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-rose-600 dark:text-rose-400">
                                    {{ formatCurrency(report.summary.days_90_plus) }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-indigo-600 dark:text-indigo-400">
                                    {{ formatCurrency(report.summary.total) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
