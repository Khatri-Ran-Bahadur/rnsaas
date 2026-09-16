<script setup lang="ts">
import { computed } from 'vue';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useTaxTerminology } from '@/utils/taxTerminology';
import { Card, Button } from '@/components';
import { Head, Link } from '@inertiajs/vue3';

export interface SummaryStats {
    total_gross_ytd?: number;
    total_net_paid_ytd?: number;
    total_epf_ytd?: number;
    total_socso_ytd?: number;
    total_eis_ytd?: number;
    total_tax_pcb_ytd?: number;
    currency?: string;
    currency_symbol?: string;
}

export interface StatutoryReportItem {
    id: number;
    name: string;
    authority: string;
    period: string;
    headcount: number;
    total_employee: number;
    total_employer: number;
    total_payable: number;
    format: string;
}

export interface BankBatchItem {
    id: number;
    batch_ref: string;
    bank: string;
    total_records: number;
    total_amount: number;
    value_date: string;
    status: string;
}

const props = defineProps<{
    summaryStats?: SummaryStats;
    statutoryReports?: StatutoryReportItem[];
    bankBatches?: BankBatchItem[];
}>();

const { currency: tenantCurrency, currencySymbol: tenantCurrencySymbol, formatMoney } = useCurrency();
const { terms } = useTaxTerminology();

const safeStats = computed(() => ({
    total_gross_ytd: props.summaryStats?.total_gross_ytd ?? 0,
    total_net_paid_ytd: props.summaryStats?.total_net_paid_ytd ?? 0,
    total_epf_ytd: props.summaryStats?.total_epf_ytd ?? 0,
    total_socso_ytd: props.summaryStats?.total_socso_ytd ?? 0,
    total_eis_ytd: props.summaryStats?.total_eis_ytd ?? 0,
    total_tax_pcb_ytd: props.summaryStats?.total_tax_pcb_ytd ?? 0,
    currency: props.summaryStats?.currency ?? tenantCurrency.value,
    currency_symbol: props.summaryStats?.currency_symbol ?? tenantCurrencySymbol.value,
}));

const handleExport = (repName: string) => {
    alert(`Generating official electronic submission file for: ${repName}`);
};

const handleDownloadBatch = (batchRef: string) => {
    alert(`Downloading bank autopay batch file: ${batchRef}`);
};
</script>

<template>
    <Head title="Payroll Reports & Compliance Exports - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Reports & Compliance</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Payroll Reports & Statutory Remittance Exports</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Export official statutory remittance submission files (Social Security, Pension, Statutory Payroll Tax Deductions) and Bank Autopay batches.
                    </p>
                </div>
            </div>

            <!-- YTD Financial KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Gross Paid YTD</div>
                    <div class="text-xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ formatMoney(safeStats.total_gross_ytd) }}
                    </div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Net Disbursed YTD</div>
                    <div class="text-xl font-black text-emerald-600 font-mono">
                        {{ formatMoney(safeStats.total_net_paid_ytd) }}
                    </div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Statutory Pension / Provident YTD</div>
                    <div class="text-xl font-black text-purple-600 font-mono">
                        {{ formatMoney(safeStats.total_epf_ytd) }}
                    </div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Payroll Withholding Tax YTD</div>
                    <div class="text-xl font-black text-blue-600 font-mono">
                        {{ formatMoney(safeStats.total_tax_pcb_ytd) }}
                    </div>
                </Card>
            </div>

            <!-- Official Statutory Compliance Filing Exports -->
            <Card class="p-6 space-y-4">
                <div class="border-b border-zinc-200 dark:border-zinc-800 pb-3">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Official Statutory Remittance Files</h3>
                    <p class="text-xs text-zinc-500">Validated electronic files formatted specifically for statutory authority portal uploads</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="rep in reports"
                        :key="rep.id"
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex flex-col justify-between space-y-4"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">{{ rep.period }}</span>
                                <span class="text-[10px] text-zinc-400 font-mono">{{ rep.headcount }} Employees</span>
                            </div>
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white">{{ rep.name }}</h4>
                            <div class="text-xs text-zinc-500">Authority: {{ rep.authority }}</div>

                            <div class="p-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-xs space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-zinc-500">Employee Portion:</span>
                                    <span class="font-mono font-semibold">{{ formatMoney(rep.total_employee) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-500">Employer Portion:</span>
                                    <span class="font-mono font-semibold">{{ formatMoney(rep.total_employer) }}</span>
                                </div>
                                <div class="flex justify-between font-bold pt-1 border-t border-zinc-100 dark:border-zinc-800">
                                    <span>Total Remittance:</span>
                                    <span class="font-mono text-emerald-600">{{ formatMoney(rep.total_payable) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-[10px] text-zinc-400 italic">{{ rep.format }}</span>
                            <Button variant="primary" size="sm" @click="handleExport(rep.name)">
                                ⬇️ Export File
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Bank Autopay Batches -->
            <Card class="p-6 space-y-4">
                <div class="border-b border-zinc-200 dark:border-zinc-800 pb-3">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Bank Autopay & Direct Giro Export Batches</h3>
                    <p class="text-xs text-zinc-500">Corporate banking direct credit batch files</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="b in batches"
                        :key="b.id"
                        class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-emerald-600">{{ b.batch_ref }}</span>
                            <span class="text-xs text-zinc-500">Value Date: {{ b.value_date }}</span>
                        </div>
                        <div class="font-bold text-sm text-zinc-900 dark:text-white">{{ b.bank }}</div>
                        <div class="flex items-center justify-between text-xs pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <div><strong>{{ b.total_records }}</strong> transactions ({{ formatMoney(b.total_amount) }})</div>
                            <Button variant="outline" size="sm" @click="handleDownloadBatch(b.batch_ref)">Download Batch</Button>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </OrganizationLayout>
</template>
