<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import html2pdf from 'html2pdf.js';

interface StatementAccountRow {
    account_id?: number;
    id?: number;
    code?: string;
    account_code?: string;
    name?: string;
    account_name?: string;
    amount?: string | number;
    balance?: string | number;
}

interface Props {
    statement: {
        statement: string;
        sections: {
            revenue?: StatementAccountRow[];
            cost_of_sales?: StatementAccountRow[];
            operating_expenses?: StatementAccountRow[];
            other_income?: StatementAccountRow[];
            other_expenses?: StatementAccountRow[];
            gross_profit?: { amount: string | number };
            operating_profit?: { amount: string | number };
            net_profit?: { amount: string | number };
        };
        total: string | number;
    };
    fromDate: string;
    toDate: string;
}

const props = defineProps<Props>();
const page = usePage();
const isDownloading = ref(false);

const currency = computed(() => {
    const curr = (page.props as any).current_tenant?.currency || (page.props as any).currentTenant?.currency || 'MYR';
    return curr === 'MYR' ? 'RM' : (curr === 'USD' ? '$' : curr + ' ');
});

const companyName = computed(() => {
    return (page.props as any).current_tenant?.name || (page.props as any).currentTenant?.name || (page.props as any).tenant?.name || 'MY COMPANY';
});

const calculateSectionTotal = (rows?: StatementAccountRow[]) => {
    if (!rows) return 0;
    return rows.reduce((sum, r) => sum + Number(r.amount || r.balance || 0), 0);
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

const formatNum = (val: string | number | undefined) => {
    const num = Number(val || 0);
    if (!num || Math.abs(num) < 0.001) return '';
    if (num < 0) return `(${Math.abs(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatPercent = (val: string | number | undefined) => {
    const num = Number(val || 0);
    if (!totalRevenue.value || Math.abs(totalRevenue.value) < 0.001) return '0.0';
    const pct = (num / totalRevenue.value) * 100;
    return pct.toFixed(1);
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

const now = new Date();
const formattedDateTime = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })}`;

const downloadPDF = async () => {
    isDownloading.value = true;
    const printContent = document.querySelector('.profit-loss-container');
    if (printContent) {
        const opt = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: `profit-loss-${formatDate(props.fromDate)}-to-${formatDate(props.toDate)}.pdf`,
            image: { type: 'jpeg' as const, quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, letterRendering: true, scrollX: 0, scrollY: 0 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' as const },
            pagebreak: { mode: ['css', 'legacy'], avoid: ['tr'] }
        };

        try {
            const worker = (html2pdf as any)().set(opt).from(printContent as HTMLElement).toPdf();
            const pdf = await worker.get('pdf');
            const totalPages = pdf.internal.getNumberOfPages();

            for (let i = 1; i <= totalPages; i++) {
                pdf.setPage(i);
                pdf.setFontSize(7.5);
                pdf.setFont('helvetica', 'bold');
                pdf.setTextColor(0, 0, 0);
                pdf.text(`Page ${i} of ${totalPages}`, 7.9, 0.92, { align: 'right' });
            }

            await worker.save();
            setTimeout(() => window.close(), 1000);
        } catch (error) {
            console.error('PDF generation failed:', error);
        }
    }
    isDownloading.value = false;
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('download') === 'pdf') {
        setTimeout(() => {
            downloadPDF();
        }, 500);
    }
});
</script>

<template>
    <Head title="Profit & Loss Statement" />

    <div class="min-h-screen bg-white p-8 text-black font-sans box-border">
        <!-- Downloading spinner overlay -->
        <div v-if="isDownloading" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-slate-900"></div>
                <p class="text-base font-semibold text-slate-800">Generating PDF...</p>
            </div>
        </div>

        <div class="profit-loss-container bg-white w-full max-w-full mx-auto p-0 text-black font-sans box-border" style="font-size: 8.5pt; font-family: Arial, sans-serif;">
            
            <!-- Header Layout (Identical to reference accounting system) -->
            <div class="mb-4">
                <div class="flex justify-between items-start">
                    <!-- Top Left: Software Title -->
                    <div class="w-[230px]">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-[11pt] text-black tracking-tight">
                                Accounting System
                            </span>
                        </div>
                    </div>

                    <!-- Center Title & Date Range -->
                    <div class="text-center flex-1 pt-0">
                        <h1 class="text-[13pt] font-extrabold text-black uppercase tracking-tight m-0 leading-tight">
                            {{ companyName }}
                        </h1>
                        <div class="text-[10.5pt] text-black font-bold mt-1">
                            Profit & Loss Statement
                        </div>
                        <div class="text-[8.5pt] text-black font-medium mt-0.5">
                            Period: {{ formatDate(fromDate) }} &mdash; {{ formatDate(toDate) }}
                        </div>
                    </div>

                    <!-- Top Right: Date Timestamp -->
                    <div class="w-[230px] text-right text-[8.5pt] text-black font-medium leading-tight">
                        <div>{{ formattedDateTime }}</div>
                        <div class="h-[14px]">
                            <!-- Page count rendered by jsPDF -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Header Line & Column Headers -->
            <div class="w-full text-right font-bold text-[8.5pt] border-b border-black pb-1 mb-1">
                <span class="inline-block w-[130px] text-right pr-4">Year To Date</span>
                <span class="inline-block w-[45px] text-right">%</span>
            </div>
            <div class="flex justify-between items-center text-[7.5pt] font-bold mb-1 px-1">
                <span></span>
                <span class="w-[175px] text-left pl-2">{{ currency.trim() }}</span>
            </div>

            <!-- Profit and Loss Table -->
            <div class="mb-4 w-full">
                <table class="w-full border-collapse text-[8.5pt]">
                    <tbody>
                        <!-- SALES / REVENUE -->
                        <tr class="font-bold uppercase text-[8.5pt]">
                            <td colspan="5" class="pt-2 pb-1 text-black">
                                <span class="underline">SALES / REVENUE</span>
                            </td>
                        </tr>
                        <tr v-for="acc in statement.sections.revenue" :key="acc.account_id || acc.id" class="border-b border-gray-50">
                            <td class="py-0.5 w-[12%] font-medium text-black pl-3">{{ acc.code || acc.account_code }}</td>
                            <td class="py-0.5 w-[48%] text-black">{{ acc.name || acc.account_name }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums pr-2">{{ formatNum(acc.amount || acc.balance) }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums"></td>
                            <td class="py-0.5 w-[10%] text-right tabular-nums text-gray-700">{{ formatPercent(acc.amount || acc.balance) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="2"></td>
                            <td class="border-t border-black"></td>
                            <td class="py-1 text-right tabular-nums border-t border-black">{{ formatNum(totalRevenue) }}</td>
                            <td class="py-1 text-right tabular-nums text-black">{{ formatPercent(totalRevenue) }}</td>
                        </tr>

                        <!-- COST OF GOODS SOLD -->
                        <tr v-if="statement.sections.cost_of_sales && statement.sections.cost_of_sales.length > 0" class="font-bold uppercase text-[8.5pt]">
                            <td colspan="5" class="pt-3 pb-1 text-black">
                                <span class="underline">COST OF GOODS SOLD</span>
                            </td>
                        </tr>
                        <tr v-for="acc in statement.sections.cost_of_sales" :key="acc.account_id || acc.id" class="border-b border-gray-50">
                            <td class="py-0.5 w-[12%] font-medium text-black pl-3">{{ acc.code || acc.account_code }}</td>
                            <td class="py-0.5 w-[48%] text-black">{{ acc.name || acc.account_name }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums pr-2">{{ formatNum(acc.amount || acc.balance) }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums"></td>
                            <td class="py-0.5 w-[10%] text-right tabular-nums text-gray-700">{{ formatPercent(acc.amount || acc.balance) }}</td>
                        </tr>
                        <tr v-if="statement.sections.cost_of_sales && statement.sections.cost_of_sales.length > 0" class="font-bold">
                            <td colspan="2"></td>
                            <td class="border-t border-black"></td>
                            <td class="py-1 text-right tabular-nums border-t border-black">{{ formatNum(totalCostOfSales) }}</td>
                            <td class="py-1 text-right tabular-nums text-black">{{ formatPercent(totalCostOfSales) }}</td>
                        </tr>

                        <!-- GROSS PROFIT/(LOSS) -->
                        <tr class="font-extrabold uppercase text-[9pt]">
                            <td colspan="3" class="py-1.5 text-black">
                                <span class="underline">GROSS PROFIT/(LOSS)</span>
                            </td>
                            <td class="py-1.5 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatNum(grossProfit) }}</td>
                            <td class="py-1.5 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatPercent(grossProfit) }}</td>
                        </tr>

                        <!-- EXPENSES -->
                        <tr class="font-bold uppercase text-[8.5pt]">
                            <td colspan="5" class="pt-3 pb-1 text-black">
                                <span class="underline">EXPENSES</span>
                            </td>
                        </tr>
                        <tr v-for="acc in statement.sections.operating_expenses" :key="acc.account_id || acc.id" class="border-b border-gray-50">
                            <td class="py-0.5 w-[12%] font-medium text-black pl-3">{{ acc.code || acc.account_code }}</td>
                            <td class="py-0.5 w-[48%] text-black">{{ acc.name || acc.account_name }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums pr-2">{{ formatNum(acc.amount || acc.balance) }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums"></td>
                            <td class="py-0.5 w-[10%] text-right tabular-nums text-gray-700">{{ formatPercent(acc.amount || acc.balance) }}</td>
                        </tr>
                        <tr class="font-bold border-t border-black">
                            <td colspan="2"></td>
                            <td class="border-t border-black"></td>
                            <td class="py-1 text-right tabular-nums border-t border-black">{{ formatNum(totalOperatingExpenses) }}</td>
                            <td class="py-1 text-right tabular-nums text-black">{{ formatPercent(totalOperatingExpenses) }}</td>
                        </tr>

                        <!-- NET PROFIT/(LOSS) -->
                        <tr class="font-extrabold uppercase text-[9pt]">
                            <td colspan="3" class="py-2 text-black">
                                <span class="underline">{{ netProfit >= 0 ? 'NET PROFIT' : 'NET PROFIT/(LOSS)' }}</span>
                            </td>
                            <td class="py-2 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatNum(netProfit) }}</td>
                            <td class="py-2 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatPercent(netProfit) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style>
body {
    -webkit-print-color-adjust: exact;
    color-adjust: exact;
    font-family: Arial, sans-serif;
}
@page {
    margin: 10mm 8mm 10mm 8mm;
    size: A4 portrait;
}
.profit-loss-container {
    box-shadow: none;
}
tr {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}
</style>
