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
            assets?: StatementAccountRow[];
            liabilities?: StatementAccountRow[];
            equity?: StatementAccountRow[];
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

const formatNum = (val: string | number | undefined) => {
    const num = Number(val || 0);
    if (!num || Math.abs(num) < 0.001) return '';
    if (num < 0) return `(${Math.abs(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatPercent = (val: string | number | undefined) => {
    const num = Number(val || 0);
    if (!totalAssets.value || Math.abs(totalAssets.value) < 0.001) return '0.0';
    const pct = (num / totalAssets.value) * 100;
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
    const printContent = document.querySelector('.balance-sheet-container');
    if (printContent) {
        const opt = {
            margin: [0.4, 0.4, 0.4, 0.4],
            filename: `balance-sheet-as-at-${formatDate(props.toDate)}.pdf`,
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
                pdf.setFontSize(8);
                pdf.setFont('helvetica', 'bold');
                pdf.setTextColor(0, 0, 0);
                pdf.text(`Page ${i} of ${totalPages}`, 7.85, 0.42, { align: 'right' });
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
    <Head title="Balance Sheet" />

    <div class="min-h-screen bg-white p-8 text-black font-sans box-border">
        <!-- Downloading spinner overlay -->
        <div v-if="isDownloading" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-slate-900"></div>
                <p class="text-base font-semibold text-slate-800">Generating PDF...</p>
            </div>
        </div>

        <div class="balance-sheet-container bg-white w-full max-w-full mx-auto p-0 text-black font-sans box-border" style="font-size: 8.5pt; font-family: Arial, sans-serif;">
            
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

                    <!-- Center Title & Date -->
                    <div class="text-center flex-1 pt-0">
                        <h1 class="text-[13pt] font-extrabold text-black uppercase tracking-tight m-0 leading-tight">
                            {{ companyName }}
                        </h1>
                        <div class="text-[10.5pt] text-black font-bold mt-1">
                            Statement Of Financial Position As At {{ formatDate(toDate) }}
                        </div>
                    </div>

                    <!-- Top Right: Date Timestamp -->
                    <div class="w-[230px] text-right text-[8.5pt] text-black font-medium leading-tight">
                        <div>{{ formattedDateTime }}</div>
                        <div class="h-[14px]">
                            <!-- Page count rendered dynamically by jsPDF -->
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

            <!-- Financial Position Table -->
            <div class="mb-4 w-full">
                <table class="w-full border-collapse text-[8.5pt]">
                    <tbody>
                        <!-- ASSETS SECTION -->
                        <tr class="font-bold uppercase text-[8.5pt]">
                            <td colspan="5" class="pt-3 pb-1 text-black">
                                <span class="underline">ASSETS</span>
                            </td>
                        </tr>
                        <tr v-for="item in statement.sections.assets" :key="item.account_id || item.id" class="border-b border-gray-50">
                            <td class="py-0.5 w-[12%] font-medium text-black pl-3">{{ item.code || item.account_code }}</td>
                            <td class="py-0.5 w-[48%] text-black">{{ item.name || item.account_name }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums pr-2">{{ formatNum(item.amount || item.balance) }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums"></td>
                            <td class="py-0.5 w-[10%] text-right tabular-nums text-gray-700">{{ formatPercent(item.amount || item.balance) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="2"></td>
                            <td class="border-t border-black"></td>
                            <td class="py-1 text-right tabular-nums border-t border-black">{{ formatNum(totalAssets) }}</td>
                            <td class="py-1 text-right tabular-nums text-black">{{ formatPercent(totalAssets) }}</td>
                        </tr>

                        <!-- TOTAL ASSETS ROW -->
                        <tr class="font-extrabold uppercase text-[9pt]">
                            <td colspan="3" class="py-2 text-black">
                                <span class="underline">TOTAL ASSETS</span>
                            </td>
                            <td class="py-2 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatNum(totalAssets) }}</td>
                            <td class="py-2 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatPercent(totalAssets) }}</td>
                        </tr>

                        <!-- EQUITY SECTION -->
                        <tr class="font-bold uppercase text-[8.5pt]">
                            <td colspan="5" class="pt-4 pb-1 text-black">
                                <span class="underline">EQUITY</span>
                            </td>
                        </tr>
                        <tr v-for="item in statement.sections.equity" :key="item.account_id || item.id" class="border-b border-gray-50">
                            <td class="py-0.5 w-[12%] font-medium text-black pl-3">{{ item.code || item.account_code }}</td>
                            <td class="py-0.5 w-[48%] text-black">{{ item.name || item.account_name }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums pr-2">{{ formatNum(item.amount || item.balance) }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums"></td>
                            <td class="py-0.5 w-[10%] text-right tabular-nums text-gray-700">{{ formatPercent(item.amount || item.balance) }}</td>
                        </tr>
                        <!-- Current Period Net Profit Row -->
                        <tr class="border-b border-gray-50">
                            <td class="py-0.5 w-[12%] font-medium text-indigo-600 pl-3">RE</td>
                            <td class="py-0.5 w-[48%] text-black">Current Period Net Profit / (Loss)</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums pr-2">{{ formatNum(statement.sections.net_profit?.amount) }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums"></td>
                            <td class="py-0.5 w-[10%] text-right tabular-nums text-gray-700">{{ formatPercent(statement.sections.net_profit?.amount) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="2"></td>
                            <td class="border-t border-black"></td>
                            <td class="py-1 text-right tabular-nums border-t border-black">{{ formatNum(totalEquity) }}</td>
                            <td class="py-1 text-right tabular-nums text-black">{{ formatPercent(totalEquity) }}</td>
                        </tr>

                        <!-- TOTAL EQUITY ROW -->
                        <tr class="font-extrabold uppercase text-[8.5pt]">
                            <td colspan="3" class="py-1 text-black">
                                <span class="underline">TOTAL EQUITY</span>
                            </td>
                            <td class="py-1 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatNum(totalEquity) }}</td>
                            <td class="py-1 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatPercent(totalEquity) }}</td>
                        </tr>

                        <!-- LIABILITIES SECTION -->
                        <tr class="font-bold uppercase text-[8.5pt]">
                            <td colspan="5" class="pt-3 pb-1 text-black">
                                <span class="underline">LIABILITIES</span>
                            </td>
                        </tr>
                        <tr v-for="item in statement.sections.liabilities" :key="item.account_id || item.id" class="border-b border-gray-50">
                            <td class="py-0.5 w-[12%] font-medium text-black pl-3">{{ item.code || item.account_code }}</td>
                            <td class="py-0.5 w-[48%] text-black">{{ item.name || item.account_name }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums pr-2">{{ formatNum(item.amount || item.balance) }}</td>
                            <td class="py-0.5 w-[15%] text-right tabular-nums"></td>
                            <td class="py-0.5 w-[10%] text-right tabular-nums text-gray-700">{{ formatPercent(item.amount || item.balance) }}</td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="2"></td>
                            <td class="border-t border-black"></td>
                            <td class="py-1 text-right tabular-nums border-t border-black">{{ formatNum(totalLiabilities) }}</td>
                            <td class="py-1 text-right tabular-nums text-black">{{ formatPercent(totalLiabilities) }}</td>
                        </tr>

                        <!-- TOTAL LIABILITIES & EQUITY ROW -->
                        <tr class="font-extrabold uppercase text-[9pt]">
                            <td colspan="3" class="py-2 text-black">
                                <span class="underline">TOTAL LIABILITIES & EQUITY</span>
                            </td>
                            <td class="py-2 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatNum(totalLiabilitiesAndEquity) }}</td>
                            <td class="py-2 text-right border-t border-black border-b-4 border-double border-black tabular-nums">{{ formatPercent(totalLiabilitiesAndEquity) }}</td>
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
    margin: 12mm 10mm 12mm 10mm;
    size: A4 portrait;
}
.balance-sheet-container {
    box-shadow: none;
}
tr {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}
</style>
