<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, DatePicker } from '@/components';

interface AgingSummary {
    current: string;
    days_1_30: string;
    days_31_60: string;
    days_61_90: string;
    days_90_plus: string;
    total: string;
}

interface CustomerAgingRow {
    customer_id: string;
    customer_code: string;
    customer_name: string;
    current: string;
    days_1_30: string;
    days_31_60: string;
    days_61_90: string;
    days_90_plus: string;
    total: string;
}

interface InvoiceAgingItem {
    customer_id: string;
    customer_code: string;
    customer_name: string;
    invoice_id: string;
    invoice_number: string;
    due_date: string;
    original_amount: string;
    paid_amount: string;
    outstanding_amount: string;
    bucket: string;
    days_overdue: number;
}

interface Props {
    report: {
        as_of_date: string;
        summary: AgingSummary;
        customers: CustomerAgingRow[];
        items: InvoiceAgingItem[];
    };
    asOfDate: string;
}

const props = defineProps<Props>();

const dateInput = ref(props.asOfDate);
const expandedCustomer = ref<string | null>(null);

const applyDate = () => {
    router.get(
        '/admin/accounting/reports/receivable-aging',
        { as_of_date: dateInput.value },
        { preserveState: true }
    );
};

const toggleCustomer = (code: string) => {
    expandedCustomer.value = expandedCustomer.value === code ? null : code;
};

const getCustomerInvoices = (code: string) => {
    return props.report.items.filter(item => item.customer_code === code);
};

const formatMoney = (val: string | number | undefined) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Accounts Receivable Aging Report - Accounting" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/accounting/reports"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; Reports Center
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Accounts Receivable Aging
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-zinc-400">
                        Unpaid client invoices classified by aging brackets as of selected date.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        onclick="window.print()"
                    >
                        Print Report
                    </button>
                    <div class="flex items-center gap-2">
                        <div class="w-40">
                            <DatePicker v-model="dateInput" placeholder="As of date" />
                        </div>
                        <Button size="sm" @click="applyDate">
                            Update
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Summary Buckets -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Current</span>
                    <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white">${{ formatMoney(report.summary.current) }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">1 - 30 Days</span>
                    <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white">${{ formatMoney(report.summary.days_1_30) }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-orange-600 dark:text-orange-400">31 - 60 Days</span>
                    <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white">${{ formatMoney(report.summary.days_31_60) }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-500 dark:text-rose-400">61 - 90 Days</span>
                    <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white">${{ formatMoney(report.summary.days_61_90) }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-300">90+ Days</span>
                    <div class="mt-1 text-lg font-bold text-slate-900 dark:text-white">${{ formatMoney(report.summary.days_90_plus) }}</div>
                </div>

                <div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 shadow-xs dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Total Due</span>
                    <div class="mt-1 text-lg font-extrabold text-indigo-700 dark:text-indigo-300">${{ formatMoney(report.summary.total) }}</div>
                </div>
            </div>

            <!-- Customer Breakdown Table -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-zinc-400">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-5 py-3">Customer</th>
                                <th scope="col" class="px-5 py-3 text-right">Current</th>
                                <th scope="col" class="px-5 py-3 text-right">1 - 30 Days</th>
                                <th scope="col" class="px-5 py-3 text-right">31 - 60 Days</th>
                                <th scope="col" class="px-5 py-3 text-right">61 - 90 Days</th>
                                <th scope="col" class="px-5 py-3 text-right">90+ Days</th>
                                <th scope="col" class="px-5 py-3 text-right font-bold">Total</th>
                                <th scope="col" class="px-5 py-3 text-center w-16">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                            <template v-for="cust in report.customers" :key="cust.customer_code">
                                <tr
                                    class="cursor-pointer transition-colors hover:bg-slate-50/80 dark:hover:bg-zinc-800/50"
                                    @click="toggleCustomer(cust.customer_code)"
                                >
                                    <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-white">
                                        {{ cust.customer_name }}
                                        <div class="text-xs font-mono font-normal text-slate-400 dark:text-zinc-500">{{ cust.customer_code }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono">${{ formatMoney(cust.current) }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono">${{ formatMoney(cust.days_1_30) }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono">${{ formatMoney(cust.days_31_60) }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono">${{ formatMoney(cust.days_61_90) }}</td>
                                    <td class="px-5 py-3.5 text-right font-mono text-rose-600 dark:text-rose-400">${{ formatMoney(cust.days_90_plus) }}</td>
                                    <td class="px-5 py-3.5 text-right font-bold font-mono text-slate-900 dark:text-white">
                                        ${{ formatMoney(cust.total) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200">
                                            <svg
                                                class="h-4 w-4 transition-transform duration-200"
                                                :class="{ 'rotate-180': expandedCustomer === cust.customer_code }"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Expanded Invoices Sub-table -->
                                <tr v-if="expandedCustomer === cust.customer_code" class="bg-slate-50/50 dark:bg-zinc-800/30">
                                    <td colspan="8" class="p-4">
                                        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-zinc-700/60 dark:bg-zinc-900">
                                            <div class="mb-2 flex items-center justify-between">
                                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                                    Invoices for {{ cust.customer_name }}
                                                </span>
                                                <Link
                                                    :href="`/admin/accounting/reports/customers/${cust.customer_id}/statement`"
                                                    class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                                >
                                                    Full Statement &rarr;
                                                </Link>
                                            </div>
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="border-b border-slate-100 text-slate-400 dark:border-zinc-800">
                                                        <th class="py-1.5 text-left">Invoice #</th>
                                                        <th class="py-1.5 text-left">Due Date</th>
                                                        <th class="py-1.5 text-center">Days Overdue</th>
                                                        <th class="py-1.5 text-right">Original</th>
                                                        <th class="py-1.5 text-right">Paid</th>
                                                        <th class="py-1.5 text-right font-bold">Outstanding</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                                    <tr v-for="item in getCustomerInvoices(cust.customer_code)" :key="item.invoice_id">
                                                        <td class="py-2 font-medium text-slate-900 dark:text-white">
                                                            <Link :href="`/admin/accounting/invoices/${item.invoice_id}`" class="hover:underline">
                                                                {{ item.invoice_number }}
                                                            </Link>
                                                        </td>
                                                        <td class="py-2 text-slate-500 dark:text-zinc-400">{{ item.due_date }}</td>
                                                        <td class="py-2 text-center">
                                                            <span
                                                                class="rounded px-1.5 py-0.5 font-semibold text-[10px]"
                                                                :class="item.days_overdue > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400'"
                                                            >
                                                                {{ item.days_overdue > 0 ? `${item.days_overdue} days` : 'Current' }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2 text-right">${{ formatMoney(item.original_amount) }}</td>
                                                        <td class="py-2 text-right text-emerald-600 dark:text-emerald-400">${{ formatMoney(item.paid_amount) }}</td>
                                                        <td class="py-2 text-right font-bold text-slate-900 dark:text-white">${{ formatMoney(item.outstanding_amount) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="report.customers.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 dark:text-zinc-500">
                                    No outstanding accounts receivable found as of this date.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
