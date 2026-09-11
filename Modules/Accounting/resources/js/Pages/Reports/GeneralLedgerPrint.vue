<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import html2pdf from 'html2pdf.js';

interface LedgerEntry {
    id: number;
    entryNumber: string;
    entryDate: string;
    description: string;
    debit: string | number;
    credit: string | number;
    balance: string | number;
    referenceType?: string | null;
    referenceId?: string | null;
}

interface GeneralLedgerReport {
    accountId: number;
    accountCode: string;
    accountName: string;
    openingBalance: string | number;
    entries: LedgerEntry[];
    totalDebit: string | number;
    totalCredit: string | number;
    closingBalance: string | number;
}

interface Props {
    selectedAccountId: number;
    fromDate: string;
    toDate: string;
    ledger: GeneralLedgerReport | null;
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

const formatNum = (val: string | number | undefined) => {
    const num = Number(val || 0);
    if (!num || Math.abs(num) < 0.001) return '';
    if (num < 0) return `(${Math.abs(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
    const printContent = document.querySelector('.general-ledger-container');
    if (printContent) {
        const opt = {
            margin: [0.4, 0.4, 0.4, 0.4],
            filename: `general-ledger-${props.ledger?.accountCode || 'report'}-${formatDate(props.fromDate)}-to-${formatDate(props.toDate)}.pdf`,
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
    <Head title="General Ledger Report" />

    <div class="min-h-screen bg-white p-8 text-black font-sans box-border">
        <!-- Downloading spinner overlay -->
        <div v-if="isDownloading" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-slate-900"></div>
                <p class="text-base font-semibold text-slate-800">Generating PDF...</p>
            </div>
        </div>

        <div class="general-ledger-container bg-white w-full max-w-full mx-auto p-0 text-black font-sans box-border" style="font-size: 8.5pt; font-family: Arial, sans-serif;">
            
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

                    <!-- Center Title & Account Info -->
                    <div class="text-center flex-1 pt-0">
                        <h1 class="text-[13pt] font-extrabold text-black uppercase tracking-tight m-0 leading-tight">
                            {{ companyName }}
                        </h1>
                        <div class="text-[10.5pt] text-black font-bold mt-1">
                            General Ledger Statement
                        </div>
                        <div v-if="ledger" class="text-[9pt] text-black font-semibold mt-0.5">
                            {{ ledger.accountCode }} &mdash; {{ ledger.accountName }}
                        </div>
                        <div class="text-[8pt] text-gray-700 font-medium mt-0.5">
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

            <!-- Ledger Table -->
            <div v-if="ledger" class="mb-4 w-full">
                <table class="w-full border-collapse text-[8.5pt]">
                    <thead>
                        <tr class="border-t border-b border-black text-black font-bold">
                            <th class="text-left py-1 w-[12%]">Date</th>
                            <th class="text-left py-1 w-[14%]">Entry #</th>
                            <th class="text-left py-1 w-[38%]">Description</th>
                            <th class="text-right py-1 w-[12%]">Debit ({{ currency.trim() }})</th>
                            <th class="text-right py-1 w-[12%]">Credit ({{ currency.trim() }})</th>
                            <th class="text-right py-1 w-[12%]">Balance ({{ currency.trim() }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Opening Balance Row -->
                        <tr class="border-b border-gray-100 font-semibold">
                            <td class="py-1">{{ formatDate(fromDate) }}</td>
                            <td class="py-1">—</td>
                            <td class="py-1">Opening Balance Brought Forward</td>
                            <td class="py-1 text-right tabular-nums"></td>
                            <td class="py-1 text-right tabular-nums"></td>
                            <td class="py-1 text-right tabular-nums font-bold">{{ formatNum(ledger.openingBalance) || '0.00' }}</td>
                        </tr>

                        <!-- Transaction Rows -->
                        <tr v-for="entry in ledger.entries" :key="entry.id" class="border-b border-gray-100">
                            <td class="py-0.5 font-normal">{{ formatDate(entry.entryDate) }}</td>
                            <td class="py-0.5 font-normal">{{ entry.entryNumber }}</td>
                            <td class="py-0.5 text-black font-normal">{{ entry.description }}</td>
                            <td class="py-0.5 text-right tabular-nums font-normal">{{ formatNum(entry.debit) }}</td>
                            <td class="py-0.5 text-right tabular-nums font-normal">{{ formatNum(entry.credit) }}</td>
                            <td class="py-0.5 text-right tabular-nums font-bold">{{ formatNum(entry.balance) }}</td>
                        </tr>

                        <tr v-if="ledger.entries.length === 0">
                            <td colspan="6" class="py-6 text-center text-gray-400 italic">
                                No ledger transactions found for this account in the selected period.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <!-- Period Activity Row -->
                        <tr class="font-bold border-t border-black">
                            <td colspan="3" class="py-1 text-right pr-4 uppercase text-[8pt]">Period Activity:</td>
                            <td class="py-1 text-right tabular-nums border-b border-black">{{ formatNum(ledger.totalDebit) || '0.00' }}</td>
                            <td class="py-1 text-right tabular-nums border-b border-black">{{ formatNum(ledger.totalCredit) || '0.00' }}</td>
                            <td class="py-1 text-right tabular-nums"></td>
                        </tr>
                        <!-- Closing Balance Row -->
                        <tr class="font-extrabold">
                            <td colspan="3" class="py-1 text-right pr-4 uppercase text-[8pt]">Ending Balance as of {{ formatDate(toDate) }}:</td>
                            <td colspan="2"></td>
                            <td class="py-1 text-right tabular-nums border-t border-black border-b-4 border-double border-black">{{ formatNum(ledger.closingBalance) || '0.00' }}</td>
                        </tr>
                    </tfoot>
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
.general-ledger-container {
    box-shadow: none;
}
tr {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
}
</style>
