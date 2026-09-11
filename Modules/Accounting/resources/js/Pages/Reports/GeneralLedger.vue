<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Select, DatePicker, type SelectOption } from '@/components';

interface AccountOption {
    id: number;
    code: string;
    name: string;
}

interface LedgerEntry {
    id: number;
    entryNumber: string;
    entryDate: string;
    description: string;
    debit: string;
    credit: string;
    balance: string;
    referenceType?: string | null;
    referenceId?: string | null;
}

interface GeneralLedgerReport {
    accountId: number;
    accountCode: string;
    accountName: string;
    openingBalance: string;
    entries: LedgerEntry[];
    totalDebit: string;
    totalCredit: string;
    closingBalance: string;
}

interface Props {
    accounts: AccountOption[];
    selectedAccountId: number;
    fromDate: string;
    toDate: string;
    ledger: GeneralLedgerReport | null;
}

const props = defineProps<Props>();
const page = usePage();

const accountInput = ref(props.selectedAccountId || (props.accounts[0]?.id ?? ''));
const fromInput = ref(props.fromDate);
const toInput = ref(props.toDate);

const accountOptions = computed<SelectOption[]>(() => {
    return props.accounts.map((acc) => ({
        label: `${acc.code} - ${acc.name}`,
        value: acc.id,
    }));
});

const applyFilter = () => {
    router.get(
        '/admin/accounting/reports/general-ledger',
        {
            account_id: accountInput.value,
            from_date: fromInput.value,
            to_date: toInput.value,
        },
        { preserveState: true }
    );
};

const formatMoney = (val: string | number | undefined) => {
    const num = Number(val || 0);
    if (!num || Math.abs(num) < 0.001) return '—';
    if (num < 0) return `(${Math.abs(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })})`;
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatBalance = (val: string | number | undefined) => {
    const num = Number(val || 0);
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
const formattedDateTime = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false })}`;

const companyName = computed(() => {
    return (page.props as any).current_tenant?.name || (page.props as any).currentTenant?.name || (page.props as any).tenant?.name || 'ORGANIZATION LEDGER';
});

const userName = computed(() => {
    return (page.props as any).auth?.user?.name || 'ADMINISTRATOR';
});

const downloadPDF = () => {
    const url = `/admin/accounting/reports/general-ledger/print?account_id=${accountInput.value}&from_date=${fromInput.value}&to_date=${toInput.value}&download=pdf`;
    window.open(url, '_blank');
};

const handlePrint = () => {
    const url = `/admin/accounting/reports/general-ledger/print?account_id=${accountInput.value}&from_date=${fromInput.value}&to_date=${toInput.value}`;
    window.open(url, '_blank');
};
</script>

<template>
    <Head title="General Ledger - Accounting Reports" />

    <OrganizationLayout>
        <div class="space-y-6">

            <!-- Screen Header & Actions (Hidden on Print) -->
            <div class="print:hidden flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/accounting/reports"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
                        >
                            &larr; Reports Center
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        General Ledger Register
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-zinc-400">
                        Detailed chronological register of posted journal lines for any specific chart of account.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- PDF Download Button -->
                    <Button
                        variant="outline"
                        size="sm"
                        class="gap-2 cursor-pointer shadow-xs"
                        :disabled="isDownloading"
                        @click="downloadPDF"
                    >
                        <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download PDF
                    </Button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 transition-all cursor-pointer"
                        @click="handlePrint"
                    >
                        <svg class="h-4 w-4 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </button>
                </div>
            </div>

            <!-- Filter Controls (Screen Only) -->
            <div class="print:hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="flex-1">
                        <Select
                            v-model="accountInput"
                            :options="accountOptions"
                            placeholder="Select chart of account..."
                            :searchable="true"
                            @change="applyFilter"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="w-36">
                            <DatePicker v-model="fromInput" placeholder="From date" />
                        </div>
                        <span class="text-xs text-slate-400 font-medium">to</span>
                        <div class="w-36">
                            <DatePicker v-model="toInput" placeholder="To date" />
                        </div>
                        <Button size="sm" @click="applyFilter">
                            Filter
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Modern KPI Stats Cards (Screen Only) -->
            <div v-if="ledger" class="print:hidden grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-5 bg-gradient-to-br from-slate-50 to-slate-100/80 dark:from-zinc-900 dark:to-zinc-800/60 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Selected Account</span>
                    <div class="mt-2 text-base font-bold text-slate-900 dark:text-white truncate">{{ ledger.accountName }}</div>
                    <div class="mt-0.5 font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ ledger.accountCode }}</div>
                </div>

                <div class="p-5 bg-gradient-to-br from-slate-50 to-slate-100/80 dark:from-zinc-900 dark:to-zinc-800/60 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Opening Balance</span>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono tabular-nums">
                        ${{ formatBalance(ledger.openingBalance) }}
                    </p>
                    <div class="mt-1 text-xs text-slate-500 font-medium">As of {{ formatDate(fromDate) }}</div>
                </div>

                <div class="p-5 bg-gradient-to-br from-blue-50 to-blue-100/60 dark:from-blue-950/40 dark:to-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800/60 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-800 dark:text-blue-300">Period Activity</span>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="flex justify-between font-mono">
                            <span class="text-slate-600 dark:text-zinc-400">Debit:</span>
                            <span class="font-bold text-slate-900 dark:text-white">${{ formatBalance(ledger.totalDebit) }}</span>
                        </div>
                        <div class="flex justify-between font-mono">
                            <span class="text-slate-600 dark:text-zinc-400">Credit:</span>
                            <span class="font-bold text-slate-900 dark:text-white">${{ formatBalance(ledger.totalCredit) }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-gradient-to-br from-indigo-50 to-indigo-100/60 dark:from-indigo-950/40 dark:to-indigo-900/20 rounded-2xl border border-indigo-200 dark:border-indigo-800/60 shadow-xs">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300">Closing Balance</span>
                    <p class="mt-2 text-2xl font-black text-indigo-950 dark:text-indigo-100 font-mono tabular-nums">
                        ${{ formatBalance(ledger.closingBalance) }}
                    </p>
                    <div class="mt-1 text-xs text-indigo-700 dark:text-indigo-400 font-medium">As of {{ formatDate(toDate) }}</div>
                </div>
            </div>

            <!-- Formal Financial Document Paper (Clean Presentation Matching Centro / PDF) -->
            <div v-if="ledger" class="general-ledger-container bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-zinc-800 p-8 shadow-sm print:p-0 print:border-none print:shadow-none print:rounded-none">
                
                <!-- Formal Document Header -->
                <div class="border-b border-slate-200 dark:border-zinc-800 pb-5 mb-6">
                    <div class="flex justify-between items-start">
                        <!-- Top Left -->
                        <div class="w-1/3">
                            <div class="text-[11px] font-mono text-slate-500 dark:text-zinc-400">
                                {{ formattedDateTime }}
                            </div>
                            <div class="mt-1 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                                General Ledger Statement
                            </div>
                        </div>

                        <!-- Center -->
                        <div class="text-center w-1/3">
                            <h2 class="text-lg font-extrabold uppercase tracking-tight text-slate-900 dark:text-white leading-tight">
                                {{ companyName }}
                            </h2>
                            <div class="mt-1 text-sm font-bold text-slate-800 dark:text-zinc-200 uppercase tracking-wide">
                                Account Ledger Report
                            </div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400 font-medium">
                                {{ ledger.accountCode }} &mdash; {{ ledger.accountName }}
                            </div>
                            <div class="mt-0.5 text-[11px] text-slate-400 dark:text-zinc-500 font-medium">
                                Period: {{ formatDate(fromDate) }} &mdash; {{ formatDate(toDate) }}
                            </div>
                        </div>

                        <!-- Top Right -->
                        <div class="text-right w-1/3 text-[11px] text-slate-500 dark:text-zinc-400">
                            <div>Prepared By: <span class="font-semibold text-slate-700 dark:text-zinc-300">{{ userName }}</span></div>
                            <div class="mt-0.5">Currency: <span class="font-mono font-bold text-slate-700 dark:text-zinc-300">USD ($)</span></div>
                        </div>
                    </div>
                </div>

                <!-- Ledger Entries Table -->
                <div class="w-full">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-900 dark:border-zinc-300 text-slate-900 dark:text-white font-bold uppercase text-[11px]">
                                <th class="py-2.5 px-3 w-[12%]">Date</th>
                                <th class="py-2.5 px-3 w-[12%]">Entry #</th>
                                <th class="py-2.5 px-3 w-[36%]">Description</th>
                                <th class="py-2.5 px-3 w-[12%]">Reference</th>
                                <th class="py-2.5 px-3 w-[14%] text-right">Debit ($)</th>
                                <th class="py-2.5 px-3 w-[14%] text-right">Credit ($)</th>
                                <th class="py-2.5 px-3 w-[14%] text-right font-black">Balance ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                            <!-- Opening Balance Row -->
                            <tr class="bg-slate-50/70 dark:bg-zinc-800/30 font-medium">
                                <td class="py-2.5 px-3 text-slate-500 dark:text-zinc-400">{{ formatDate(fromDate) }}</td>
                                <td class="py-2.5 px-3 text-slate-400">—</td>
                                <td class="py-2.5 px-3 font-semibold text-slate-900 dark:text-white">Opening Balance Brought Forward</td>
                                <td class="py-2.5 px-3 text-slate-400">—</td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-400">—</td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-400">—</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white tabular-nums">
                                    ${{ formatBalance(ledger.openingBalance) }}
                                </td>
                            </tr>

                            <!-- Transaction Lines -->
                            <tr
                                v-for="entry in ledger.entries"
                                :key="entry.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 transition-colors"
                            >
                                <td class="py-2 px-3 text-slate-700 dark:text-zinc-300">{{ formatDate(entry.entryDate) }}</td>
                                <td class="py-2 px-3 font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ entry.entryNumber }}</td>
                                <td class="py-2 px-3 text-slate-900 dark:text-white font-medium">{{ entry.description }}</td>
                                <td class="py-2 px-3 font-mono text-slate-500">{{ entry.referenceType ? `${entry.referenceType} #${entry.referenceId}` : '—' }}</td>
                                <td class="py-2 px-3 text-right font-mono tabular-nums text-slate-900 dark:text-white">
                                    {{ formatMoney(entry.debit) }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono tabular-nums text-slate-900 dark:text-white">
                                    {{ formatMoney(entry.credit) }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold tabular-nums text-slate-900 dark:text-white">
                                    ${{ formatBalance(entry.balance) }}
                                </td>
                            </tr>

                            <tr v-if="ledger.entries.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                    No ledger activity recorded for this account during the selected date range.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <!-- Period Activity Subtotal -->
                            <tr class="font-bold text-slate-900 dark:text-white text-xs border-t-2 border-slate-900 dark:border-zinc-300">
                                <td colspan="4" class="py-2.5 px-3 uppercase tracking-wider text-right pr-6">
                                    Period Activity:
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums">
                                    ${{ formatBalance(ledger.totalDebit) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums">
                                    ${{ formatBalance(ledger.totalCredit) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums">
                                    —
                                </td>
                            </tr>

                            <!-- Closing Balance Grand Total -->
                            <tr class="font-extrabold text-slate-900 dark:text-white text-xs border-t border-slate-300 dark:border-zinc-700">
                                <td colspan="4" class="py-3 px-3 uppercase tracking-wider text-right pr-6 text-indigo-700 dark:text-indigo-400">
                                    Ending Balance as of {{ formatDate(toDate) }}:
                                </td>
                                <td colspan="2"></td>
                                <td class="py-3 px-3 text-right font-mono text-sm tabular-nums font-black border-b-4 border-double border-slate-900 dark:border-zinc-300 text-indigo-700 dark:text-indigo-300">
                                    ${{ formatBalance(ledger.closingBalance) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Footer Sign-off (Printed View) -->
                <div class="hidden print:block mt-12 pt-8 border-t border-slate-200">
                    <div class="grid grid-cols-3 gap-8 text-center text-xs">
                        <div>
                            <div class="border-b border-black pb-1 mb-2"></div>
                            <div class="font-bold">Prepared By</div>
                        </div>
                        <div>
                            <div class="border-b border-black pb-1 mb-2"></div>
                            <div class="font-bold">Reviewed By</div>
                        </div>
                        <div>
                            <div class="border-b border-black pb-1 mb-2"></div>
                            <div class="font-bold">Authorized Signatory</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>

<style>
@media print {
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        font-size: 9pt !important;
    }
    aside, nav, header, .print\\:hidden {
        display: none !important;
    }
    .general-ledger-container {
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
    @page {
        size: A4 portrait;
        margin: 15mm 12mm 15mm 12mm;
    }
    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
