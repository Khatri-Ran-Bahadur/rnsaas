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

interface StatementProps {
    statement: {
        statement: string;
        sections: {
            revenue?: StatementAccountRow[];
            cost_of_sales?: StatementAccountRow[];
            operating_expenses?: StatementAccountRow[];
            other_income?: StatementAccountRow[];
            other_expenses?: StatementAccountRow[];
            gross_profit?: { amount: string };
            operating_profit?: { amount: string };
            net_profit?: { amount: string };
        };
        total: string;
    };
    fromDate: string;
    toDate: string;
}

const props = defineProps<StatementProps>();
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

const totalRevenue = computed(() => {
    return calculateSectionTotal(props.statement.sections.revenue);
});

const totalCostOfSales = computed(() => {
    return calculateSectionTotal(props.statement.sections.cost_of_sales);
});

const grossProfit = computed(() => {
    return Number(props.statement.sections.gross_profit?.amount ?? (totalRevenue.value - totalCostOfSales.value));
});

const totalOperatingExpenses = computed(() => {
    return calculateSectionTotal(props.statement.sections.operating_expenses);
});

const netProfit = computed(() => {
    return Number(props.statement.total || props.statement.sections.net_profit?.amount || 0);
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
        '/admin/accounting/statements/profit-and-loss',
        {
            from_date: fromInput.value,
            to_date: toInput.value,
        },
        { preserveState: true }
    );
};

const downloadPDF = () => {
    const url = `/admin/accounting/statements/profit-and-loss/print?from_date=${fromInput.value}&to_date=${toInput.value}&download=pdf`;
    window.open(url, '_blank');
};

const exportExcel = () => {
    let csv = 'Section,Account Code,Account Name,Amount\n';
    props.statement.sections.revenue?.forEach(acc => {
        csv += `"Revenue","${acc.code}","${acc.name}",${acc.amount}\n`;
    });
    props.statement.sections.cost_of_sales?.forEach(acc => {
        csv += `"Cost of Goods Sold","${acc.code}","${acc.name}",${acc.amount}\n`;
    });
    props.statement.sections.operating_expenses?.forEach(acc => {
        csv += `"Operating Expenses","${acc.code}","${acc.name}",${acc.amount}\n`;
    });
    csv += `"Summary","","Gross Profit",${grossProfit.value}\n`;
    csv += `"Summary","","Net Profit",${netProfit.value}\n`;

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', `profit-and-loss-${props.fromDate}-to-${props.toDate}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head title="Profit & Loss Statement" />

    <OrganizationLayout>
        <div class="space-y-6">

            <!-- Page Title -->
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Profit & Loss Statement
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
                                Profit & Loss Statement
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

                            <!-- Download Monthly PDF Button -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-amber-600 transition-colors cursor-pointer"
                                @click="downloadPDF"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Monthly PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4 KPI Summary Cards in a row -->
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- TOTAL REVENUE -->
                    <div class="text-center p-6 bg-[#eefbf4] dark:bg-emerald-950/20 rounded-2xl border border-[#c4f0d6] dark:border-emerald-800/50">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-[#27945d] dark:text-emerald-400 block mb-2">Total Revenue</span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#196b42] dark:text-emerald-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrency(totalRevenue) }}
                        </p>
                    </div>

                    <!-- COST OF GOODS SOLD -->
                    <div class="text-center p-6 bg-[#fef8ee] dark:bg-amber-950/20 rounded-2xl border border-[#fae2be] dark:border-amber-800/50">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-[#b45309] dark:text-amber-400 block mb-2">Cost of Goods Sold</span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#92400e] dark:text-amber-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrency(totalCostOfSales) }}
                        </p>
                    </div>

                    <!-- OPERATING EXPENSES -->
                    <div class="text-center p-6 bg-[#fdf1f1] dark:bg-rose-950/20 rounded-2xl border border-[#fad4d4] dark:border-rose-800/50">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-[#e11d48] dark:text-rose-400 block mb-2">Operating Expenses</span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#be123c] dark:text-rose-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrency(totalOperatingExpenses) }}
                        </p>
                    </div>

                    <!-- NET PROFIT -->
                    <div class="text-center p-6 bg-[#eef5fe] dark:bg-blue-950/20 rounded-2xl border border-[#c9dffc] dark:border-blue-800/50">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-[#2563eb] dark:text-blue-400 block mb-2">Net Profit</span>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#1d4ed8] dark:text-blue-300 font-mono tracking-tight tabular-nums">
                            {{ formatCurrency(netProfit) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Two-Column Statement Paper Card (Matching Screenshot 3) -->
            <div class="profit-loss-paper bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 sm:p-10 shadow-xs">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    
                    <!-- Left Column: Revenue & Cost of Goods Sold -->
                    <div class="space-y-8">
                        <!-- Revenue Section -->
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                                Revenue
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="acc in statement.sections.revenue"
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
                                <div v-if="!statement.sections.revenue?.length" class="text-xs text-slate-400 italic">
                                    No revenue recorded.
                                </div>
                            </div>
                        </div>

                        <!-- Cost of Goods Sold Section -->
                        <div>
                            <h3 class="text-base font-bold text-[#b45309] dark:text-amber-400 mb-4">
                                Cost of Goods Sold
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="acc in statement.sections.cost_of_sales"
                                    :key="acc.account_id"
                                    class="flex justify-between items-center text-sm"
                                >
                                    <span class="font-medium text-slate-800 dark:text-zinc-200">
                                        <span class="text-[#b45309] font-mono font-semibold mr-2">{{ acc.code }}</span> &ndash; {{ acc.name }}
                                    </span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white tabular-nums">
                                        {{ formatCurrency(acc.amount) }}
                                    </span>
                                </div>
                                <div v-if="!statement.sections.cost_of_sales?.length" class="text-xs text-slate-400 italic">
                                    No cost of goods sold recorded.
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue Line -->
                        <div class="pt-4 border-t border-slate-300 dark:border-zinc-700 flex justify-between items-center">
                            <span class="font-bold text-slate-900 dark:text-white text-sm">Total Revenue</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white text-sm tabular-nums">
                                {{ formatCurrency(totalRevenue) }}
                            </span>
                        </div>

                        <!-- Gross Profit Footer on Left Column -->
                        <div class="pt-6 border-t-2 border-slate-200 dark:border-zinc-700 flex items-center">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mr-2">Gross Profit:</span>
                            <span class="font-mono text-base font-bold text-slate-900 dark:text-white tabular-nums">
                                {{ formatCurrency(grossProfit) }}
                            </span>
                        </div>
                    </div>

                    <!-- Right Column: Operating Expenses & Net Profit -->
                    <div class="space-y-8 flex flex-col justify-between">
                        <!-- Operating Expenses Section -->
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                                Operating Expenses
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="acc in statement.sections.operating_expenses"
                                    :key="acc.account_id"
                                    class="flex justify-between items-center text-sm"
                                >
                                    <span class="font-medium text-slate-800 dark:text-zinc-200">
                                        <span class="text-[#dc2626] font-mono font-semibold mr-2">{{ acc.code }}</span> &ndash; {{ acc.name }}
                                    </span>
                                    <span class="font-mono font-semibold text-slate-900 dark:text-white tabular-nums">
                                        {{ formatCurrency(acc.amount) }}
                                    </span>
                                </div>
                                <div v-if="!statement.sections.operating_expenses?.length" class="text-xs text-slate-400 italic">
                                    No operating expenses recorded.
                                </div>
                            </div>
                        </div>

                        <div>
                            <!-- Total Operating Expenses Line -->
                            <div class="pt-4 border-t border-slate-300 dark:border-zinc-700 flex justify-between items-center">
                                <span class="font-bold text-slate-900 dark:text-white text-sm">Total Operating Expenses</span>
                                <span class="font-mono font-bold text-slate-900 dark:text-white text-sm tabular-nums">
                                    {{ formatCurrency(totalOperatingExpenses) }}
                                </span>
                            </div>

                            <!-- Net Profit Footer on Right Column -->
                            <div class="pt-6 border-t-2 border-slate-200 dark:border-zinc-700 flex justify-end items-center">
                                <span class="text-sm font-bold text-slate-900 dark:text-white mr-3">Net Profit:</span>
                                <span class="font-mono text-xl font-extrabold text-[#16a34a] dark:text-emerald-400 tabular-nums">
                                    {{ formatCurrency(netProfit) }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
