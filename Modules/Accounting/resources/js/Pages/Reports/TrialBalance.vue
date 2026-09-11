<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, DatePicker } from '@/components';

interface TrialBalanceAccount {
    account_id: number;
    code: string;
    name: string;
    debit: string;
    credit: string;
}

interface Props {
    trialBalance: {
        accounts: TrialBalanceAccount[];
        totalDebit: string;
        totalCredit: string;
    };
    fromDate: string;
    toDate: string;
}

const props = defineProps<Props>();
const page = usePage();

const fromInput = ref(props.fromDate);
const toInput = ref(props.toDate);
const isRebalancing = ref(false);

const currency = computed(() => {
    const curr = (page.props as any).current_tenant?.currency || (page.props as any).currentTenant?.currency || 'MYR';
    return curr === 'MYR' ? 'RM' : (curr === 'USD' ? '$' : curr + ' ');
});

const companyName = computed(() => {
    return (page.props as any).current_tenant?.name || (page.props as any).currentTenant?.name || (page.props as any).tenant?.name || 'My Company';
});

const handleGenerate = () => {
    router.get(
        '/admin/accounting/reports/trial-balance',
        {
            from_date: fromInput.value,
            to_date: toInput.value,
        },
        { preserveState: true }
    );
};

const formatCurrency = (val: string | number | undefined) => {
    const num = Number(val || 0);
    if (!num || Math.abs(num) < 0.001) return '-';
    return `${currency.value}${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatCurrencyTotal = (val: string | number | undefined) => {
    const num = Number(val || 0);
    return `${currency.value}${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatDate = (dateStr?: string) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
};

const downloadPDF = () => {
    const url = `/admin/accounting/reports/trial-balance/print?from_date=${fromInput.value}&to_date=${toInput.value}&download=pdf`;
    window.open(url, '_blank');
};

const exportExcel = () => {
    let csv = 'Account Code,Account Name,Debit,Credit\n';
    props.trialBalance.accounts.forEach(acc => {
        csv += `"${acc.code}","${acc.name}","${Number(acc.debit) || 0}","${Number(acc.credit) || 0}"\n`;
    });
    csv += `"TOTAL","Total",${props.trialBalance.totalDebit},${props.trialBalance.totalCredit}\n`;
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', `trial-balance-${props.fromDate}-to-${props.toDate}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const isBalanced = computed(() => {
    return Math.abs(Number(props.trialBalance.totalDebit || 0) - Number(props.trialBalance.totalCredit || 0)) < 0.01;
});
</script>

<template>
    <Head title="Trial Balance" />

    <OrganizationLayout>
        <div class="space-y-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Trial Balance
            </h1>

            <!-- Top Filter & Action Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 shadow-xs">
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
                    <!-- Title & Date Subtitle on Left -->
                    <div class="flex items-center gap-4 min-w-[240px]">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white leading-snug">
                                Trial Balance
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 font-medium">
                                {{ fromDate }} - {{ toDate }}
                            </p>
                        </div>
                    </div>

                    <!-- Date Filters & Actions on Right -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div>
                                <span class="block text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mb-1">From Date</span>
                                <div class="w-40">
                                    <DatePicker v-model="fromInput" placeholder="From Date" />
                                </div>
                            </div>
                            <div>
                                <span class="block text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mb-1">To Date</span>
                                <div class="w-40">
                                    <DatePicker v-model="toInput" placeholder="To Date" />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-5">
                            <!-- Generate Button -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-[#0f172a] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 transition-colors cursor-pointer"
                                @click="handleGenerate"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Generate
                            </button>

                            <!-- Download PDF Button -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-colors cursor-pointer"
                                @click="downloadPDF"
                            >
                                <svg class="h-4 w-4 text-slate-600 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Download PDF
                            </button>

                            <!-- Export Excel Button -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-colors cursor-pointer"
                                @click="exportExcel"
                            >
                                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export Excel
                            </button>

                            <!-- Audit & Rebalance Button -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-amber-600 transition-colors cursor-pointer"
                                @click="handleGenerate"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Audit & Rebalance
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2 Large Summary KPI Cards -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Total Debit Card -->
                    <div class="text-center p-8 bg-[#eefbf4] dark:bg-emerald-950/20 rounded-2xl border border-[#c4f0d6] dark:border-emerald-800/50">
                        <h4 class="font-bold text-sm text-[#27945d] dark:text-emerald-400 mb-2">Total Debit</h4>
                        <p class="text-3xl sm:text-4xl font-extrabold text-[#196b42] dark:text-emerald-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrencyTotal(trialBalance.totalDebit) }}
                        </p>
                    </div>

                    <!-- Total Credit Card -->
                    <div class="text-center p-8 bg-[#eef5fe] dark:bg-blue-950/20 rounded-2xl border border-[#c9dffc] dark:border-blue-800/50">
                        <h4 class="font-bold text-sm text-[#2b6bc9] dark:text-blue-400 mb-2">Total Credit</h4>
                        <p class="text-3xl sm:text-4xl font-extrabold text-[#1d4f9b] dark:text-blue-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrencyTotal(trialBalance.totalCredit) }}
                        </p>
                    </div>
                </div>

                <!-- Balanced / Discrepancy Banner -->
                <div v-if="!isBalanced" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-red-800 font-semibold text-sm">
                        ⚠️ Warning: Trial balance is not balanced! Difference: {{ currency }}{{ Math.abs(Number(trialBalance.totalDebit) - Number(trialBalance.totalCredit)).toFixed(2) }}
                    </p>
                </div>
            </div>

            <!-- Trial Balance Table Card (Also captured by PDF generator) -->
            <div class="trial-balance-paper bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 sm:p-8 shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-200 dark:border-zinc-700 text-slate-900 dark:text-white font-bold text-sm">
                                <th class="py-3 px-4 w-[20%]">Account Code</th>
                                <th class="py-3 px-4 w-[50%]">Account Name</th>
                                <th class="py-3 px-4 w-[15%] text-right">Debit</th>
                                <th class="py-3 px-4 w-[15%] text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                            <tr
                                v-for="acc in trialBalance.accounts"
                                :key="acc.account_id"
                                class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 transition-colors"
                            >
                                <td class="py-3 px-4 font-mono font-semibold text-[#16a34a] dark:text-emerald-400">
                                    {{ acc.code }}
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800 dark:text-zinc-200">
                                    {{ acc.name }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-medium text-slate-900 dark:text-white tabular-nums">
                                    {{ Number(acc.debit) > 0 ? formatCurrency(acc.debit) : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-medium text-slate-900 dark:text-white tabular-nums">
                                    {{ Number(acc.credit) > 0 ? formatCurrency(acc.credit) : '-' }}
                                </td>
                            </tr>
                            <tr v-if="trialBalance.accounts.length === 0">
                                <td colspan="4" class="py-12 text-center text-slate-400 italic">
                                    No account transactions found for the selected date range.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-extrabold text-slate-900 dark:text-white text-sm border-t-2 border-slate-300 dark:border-zinc-700">
                                <td colspan="2" class="py-4 px-4 uppercase tracking-wider">
                                    TOTAL
                                </td>
                                <td class="py-4 px-4 text-right font-mono tabular-nums font-bold">
                                    {{ formatCurrencyTotal(trialBalance.totalDebit) }}
                                </td>
                                <td class="py-4 px-4 text-right font-mono tabular-nums font-bold">
                                    {{ formatCurrencyTotal(trialBalance.totalCredit) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
