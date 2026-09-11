<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, DatePicker } from '@/components';

interface StatementAccountRow {
    account_id: number;
    code: string;
    name: string;
    amount: string;
}

interface BalanceSheetProps {
    statement: {
        statement: string;
        sections: {
            assets?: StatementAccountRow[];
            liabilities?: StatementAccountRow[];
            equity?: StatementAccountRow[];
            net_profit?: { amount: string };
        };
        total: string;
    };
    fromDate: string;
    toDate: string;
}

const props = defineProps<BalanceSheetProps>();
const page = usePage();

const fromInput = ref(props.fromDate);
const toInput = ref(props.toDate);

const currency = computed(() => {
    const curr = (page.props as any).current_tenant?.currency || (page.props as any).currentTenant?.currency || 'MYR';
    return curr === 'MYR' ? 'RM' : (curr === 'USD' ? '$' : curr + ' ');
});

const calculateSectionTotal = (rows?: StatementAccountRow[]) => {
    if (!rows) return 0;
    return rows.reduce((sum, r) => sum + Number(r.amount || 0), 0);
};

const totalAssets = computed(() => {
    return calculateSectionTotal(props.statement.sections.assets);
});

const totalLiabilities = computed(() => {
    return calculateSectionTotal(props.statement.sections.liabilities);
});

const totalEquity = computed(() => {
    const accountsTotal = calculateSectionTotal(props.statement.sections.equity);
    const netProfit = Number(props.statement.sections.net_profit?.amount || 0);
    return accountsTotal + netProfit;
});

const totalLiabilitiesAndEquity = computed(() => {
    return totalLiabilities.value + totalEquity.value;
});

const isBalanced = computed(() => {
    return Math.abs(totalAssets.value - totalLiabilitiesAndEquity.value) < 0.01;
});

const formatCurrency = (val: string | number | undefined) => {
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

const handleGenerate = () => {
    router.get(
        '/admin/accounting/statements/balance-sheet',
        {
            from_date: fromInput.value,
            to_date: toInput.value,
        },
        { preserveState: true }
    );
};

const downloadPDF = () => {
    const url = `/admin/accounting/statements/balance-sheet/print?from_date=${fromInput.value}&to_date=${toInput.value}&download=pdf`;
    window.open(url, '_blank');
};

const exportExcel = () => {
    let csv = 'Category,Account Code,Account Name,Amount\n';
    props.statement.sections.assets?.forEach(acc => {
        csv += `"Assets","${acc.code}","${acc.name}",${acc.amount}\n`;
    });
    props.statement.sections.liabilities?.forEach(acc => {
        csv += `"Liabilities","${acc.code}","${acc.name}",${acc.amount}\n`;
    });
    props.statement.sections.equity?.forEach(acc => {
        csv += `"Equity","${acc.code}","${acc.name}",${acc.amount}\n`;
    });
    csv += `"Summary","","Total Assets",${totalAssets.value}\n`;
    csv += `"Summary","","Total Liabilities",${totalLiabilities.value}\n`;
    csv += `"Summary","","Total Equity",${totalEquity.value}\n`;

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', `balance-sheet-${props.toDate}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Balance Sheet" />

    <OrganizationLayout>
        <div class="space-y-6">

            <!-- Page Title -->
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Balance Sheets
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
                                Balance Sheet
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 font-medium">
                                As of {{ toDate }}
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
                        </div>
                    </div>
                </div>

                <!-- 4 Summary KPI Cards in a row -->
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- TOTAL ASSETS -->
                    <div class="text-center p-6 bg-[#eefbf4] dark:bg-emerald-950/20 rounded-2xl border border-[#c4f0d6] dark:border-emerald-800/50">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-[#27945d] dark:text-emerald-400 block mb-2">Total Assets</span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#196b42] dark:text-emerald-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrency(totalAssets) }}
                        </p>
                    </div>

                    <!-- TOTAL LIABILITIES -->
                    <div class="text-center p-6 bg-[#fef8ee] dark:bg-amber-950/20 rounded-2xl border border-[#fae2be] dark:border-amber-800/50">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-[#b45309] dark:text-amber-400 block mb-2">Total Liabilities</span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#92400e] dark:text-amber-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrency(totalLiabilities) }}
                        </p>
                    </div>

                    <!-- TOTAL EQUITY -->
                    <div class="text-center p-6 bg-[#eef5fe] dark:bg-blue-950/20 rounded-2xl border border-[#c9dffc] dark:border-blue-800/50">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-[#2563eb] dark:text-blue-400 block mb-2">Total Equity</span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#1d4ed8] dark:text-blue-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrency(totalEquity) }}
                        </p>
                    </div>

                    <!-- EQUATION BALANCE -->
                    <div
                        class="text-center p-6 rounded-2xl border"
                        :class="isBalanced ? 'bg-[#eefbf4] border-[#c4f0d6]' : 'bg-[#fdf1f1] border-[#fad4d4]'"
                    >
                        <span class="font-bold text-[11px] uppercase tracking-wider block mb-2" :class="isBalanced ? 'text-[#27945d]' : 'text-[#e11d48]'">
                            {{ isBalanced ? 'Equation Status' : 'Imbalance' }}
                        </span>
                        <p class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight tabular-nums" :class="isBalanced ? 'text-[#196b42]' : 'text-[#be123c]'">
                            {{ isBalanced ? 'A = L + E' : `Δ ${formatCurrency(Math.abs(totalAssets - totalLiabilitiesAndEquity))}` }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Two-Column Statement Paper Card (Matching Screenshot / View.tsx) -->
            <div class="balance-sheet-paper bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 sm:p-10 shadow-xs">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    
                    <!-- Left Column: Liabilities & Equity -->
                    <div class="space-y-8">
                        <!-- Liabilities Section -->
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                                Liabilities
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="acc in statement.sections.liabilities"
                                    :key="acc.account_id"
                                    class="flex justify-between items-center text-sm"
                                >
                                    <span class="font-medium text-slate-800 dark:text-zinc-200">
                                        <span class="text-[#16a34a] font-mono font-semibold mr-2">{{ acc.code }}</span> &ndash; {{ acc.name }}
                                    </span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white tabular-nums">
                                        {{ formatCurrency(acc.amount) }}
                                    </span>
                                </div>
                                <div v-if="!statement.sections.liabilities?.length" class="text-xs text-slate-400 italic">
                                    No liabilities recorded.
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200 dark:border-zinc-800 flex justify-between items-center text-sm font-bold">
                                <span>Total Liabilities</span>
                                <span class="font-mono tabular-nums">{{ formatCurrency(totalLiabilities) }}</span>
                            </div>
                        </div>

                        <!-- Equity Section -->
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                                Equity
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="acc in statement.sections.equity"
                                    :key="acc.account_id"
                                    class="flex justify-between items-center text-sm"
                                >
                                    <span class="font-medium text-slate-800 dark:text-zinc-200">
                                        <span class="text-[#16a34a] font-mono font-semibold mr-2">{{ acc.code }}</span> &ndash; {{ acc.name }}
                                    </span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white tabular-nums">
                                        {{ formatCurrency(acc.amount) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="font-medium text-slate-800 dark:text-zinc-200">
                                        <span class="text-indigo-600 font-mono font-semibold mr-2">RE</span> &ndash; Current Period Net Profit / (Loss)
                                    </span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white tabular-nums">
                                        {{ formatCurrency(statement.sections.net_profit?.amount) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200 dark:border-zinc-800 flex justify-between items-center text-sm font-bold">
                                <span>Total Equity</span>
                                <span class="font-mono tabular-nums">{{ formatCurrency(totalEquity) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Assets -->
                    <div class="space-y-8">
                        <!-- Assets Section -->
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                                Assets
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="acc in statement.sections.assets"
                                    :key="acc.account_id"
                                    class="flex justify-between items-center text-sm"
                                >
                                    <span class="font-medium text-slate-800 dark:text-zinc-200">
                                        <span class="text-[#16a34a] font-mono font-semibold mr-2">{{ acc.code }}</span> &ndash; {{ acc.name }}
                                    </span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white tabular-nums">
                                        {{ formatCurrency(acc.amount) }}
                                    </span>
                                </div>
                                <div v-if="!statement.sections.assets?.length" class="text-xs text-slate-400 italic">
                                    No assets recorded.
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200 dark:border-zinc-800 flex justify-between items-center text-sm font-bold">
                                <span>Total Assets</span>
                                <span class="font-mono tabular-nums">{{ formatCurrency(totalAssets) }}</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bottom Grand Totals (Always Show) -->
                <div class="mt-10 pt-6 border-t-2 border-slate-300 dark:border-zinc-700">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        <div class="flex justify-between items-center py-2 font-bold text-lg text-slate-900 dark:text-white">
                            <span>Total for Liabilities & Equity</span>
                            <span class="font-mono text-xl tabular-nums font-black text-indigo-600 dark:text-indigo-400">
                                {{ formatCurrency(totalLiabilitiesAndEquity) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 font-bold text-lg text-slate-900 dark:text-white">
                            <span>Total for Assets</span>
                            <span class="font-mono text-xl tabular-nums font-black text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(totalAssets) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
