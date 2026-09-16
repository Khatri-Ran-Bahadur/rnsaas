<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface RunDetail {
    id: number;
    run_number: string;
    period_name: string;
    start_date: string;
    end_date: string;
    pay_date: string;
    status: 'draft' | 'processing' | 'calculated' | 'pending_approval' | 'approved' | 'finalized' | 'paid';
    payroll_group: string;
    total_employees: number;
    gross_amount: number;
    deductions_amount: number;
    net_amount: number;
    employer_statutory: number;
    total_payroll_cost: number;
    currency: string;
    currency_symbol: string;
    created_by: string;
    approved_by?: string | null;
    journal_entry_id?: number | null;
    bank_batch_exported: boolean;
}

export interface RunItemRow {
    id: number;
    employee_id: number;
    employee_code: string;
    employee_name: string;
    department: string;
    designation: string;
    employment_type: string;
    base_salary: number;
    allowances_total: number;
    overtime_pay: number;
    commission: number;
    bonus: number;
    gross_pay: number;
    epf_employee: number;
    socso_employee: number;
    eis_employee: number;
    tax_pcb: number;
    loan_deduction: number;
    advance_recovery: number;
    total_deductions: number;
    net_pay: number;
    epf_employer: number;
    socso_employer: number;
    eis_employer: number;
    total_employer_cost: number;
    bank_name: string;
    bank_account: string;
    status: string;
}

export interface StatutorySummary {
    epf_total_employee: number;
    epf_total_employer: number;
    epf_grand_total: number;
    socso_total_employee: number;
    socso_total_employer: number;
    socso_grand_total: number;
    eis_total_employee: number;
    eis_total_employer: number;
    eis_grand_total: number;
    pcb_tax_total: number;
    total_statutory_liability: number;
}

const props = defineProps<{
    run: RunDetail;
    items: RunItemRow[];
    statutorySummary: StatutorySummary;
}>();

const activeTab = ref<'roster' | 'statutory' | 'banking'>('roster');

const formatMoney = (val: number) => {
    return (props.run?.currency_symbol || 'RM') + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleApprove = () => {
    router.post(`/admin/payroll/runs/${props.run.id}/approve`);
};

const handleFinalize = () => {
    router.post(`/admin/payroll/runs/${props.run.id}/finalize`);
};

const handleMarkPaid = () => {
    router.post(`/admin/payroll/runs/${props.run.id}/mark-paid`);
};
</script>

<template>
    <Head :title="`Payroll Run: ${run.run_number} - SathiSaaS`" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header with Quick Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <Link href="/admin/payroll/runs" class="hover:text-emerald-600 transition">Runs</Link>
                        <span>/</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-300 font-medium">{{ run.run_number }}</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ run.period_name }}
                        </h1>
                        <Badge :variant="run.status === 'paid' ? 'success' : 'warning'" class="uppercase text-[10px]">
                            {{ run.status }}
                        </Badge>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Group: <strong>{{ run.payroll_group }}</strong> • Pay Date: <strong>{{ run.pay_date }}</strong> • Period: <strong>{{ run.start_date }} ~ {{ run.end_date }}</strong>
                    </p>
                </div>

                <!-- Workflow Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        v-if="run.status === 'calculated' || run.status === 'pending_approval'"
                        variant="primary"
                        size="sm"
                        @click="handleApprove"
                    >
                        ✓ Executive Approve Run
                    </Button>

                    <Button
                        v-if="run.status === 'approved'"
                        variant="primary"
                        size="sm"
                        @click="handleFinalize"
                    >
                        🔒 Finalize & Post Journal
                    </Button>

                    <Button
                        v-if="run.status === 'finalized'"
                        variant="primary"
                        size="sm"
                        @click="handleMarkPaid"
                    >
                        💰 Generate Bank File & Mark Paid
                    </Button>

                    <Link
                        href="/admin/payroll/reports"
                        class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 transition"
                    >
                        Statutory Filing Export
                    </Link>
                </div>
            </div>

            <!-- Financial Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Gross Earnings</div>
                    <div class="text-xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ formatMoney(run.gross_amount) }}
                    </div>
                    <div class="text-[10px] text-zinc-400">{{ run.total_employees }} Staff Members</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Employee Deductions</div>
                    <div class="text-xl font-black text-rose-600 font-mono">
                        -{{ formatMoney(run.deductions_amount) }}
                    </div>
                    <div class="text-[10px] text-zinc-400">EPF, SOCSO, EIS, PCB & Loans</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Net Salary Disbursement</div>
                    <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ formatMoney(run.net_amount) }}
                    </div>
                    <div class="text-[10px] text-zinc-400">To be credited via Bank Autopay</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Total Cost to Company</div>
                    <div class="text-xl font-black text-purple-600 dark:text-purple-400 font-mono">
                        {{ formatMoney(run.total_payroll_cost) }}
                    </div>
                    <div class="text-[10px] text-zinc-400">Includes Employer Statutory ({{ formatMoney(run.employer_statutory) }})</div>
                </Card>
            </div>

            <!-- Navigation Tabs -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-800 space-x-6 text-xs font-bold">
                <button
                    type="button"
                    @click="activeTab = 'roster'"
                    :class="['pb-2.5 transition cursor-pointer', activeTab === 'roster' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 hover:text-zinc-800']"
                >
                    Employee Salary Sheet ({{ items.length }})
                </button>
                <button
                    type="button"
                    @click="activeTab = 'statutory'"
                    :class="['pb-2.5 transition cursor-pointer', activeTab === 'statutory' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 hover:text-zinc-800']"
                >
                    Statutory Liabilities Summary (EPF / SOCSO / EIS / PCB)
                </button>
                <button
                    type="button"
                    @click="activeTab = 'banking'"
                    :class="['pb-2.5 transition cursor-pointer', activeTab === 'banking' ? 'border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 hover:text-zinc-800']"
                >
                    Bank Direct Credit Batch Export
                </button>
            </div>

            <!-- TAB 1: Employee Salary Sheet Table -->
            <div v-if="activeTab === 'roster'" class="space-y-4">
                <Card class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px]">
                                <tr>
                                    <th class="px-3.5 py-3">Employee</th>
                                    <th class="px-3.5 py-3 text-right">Base Pay</th>
                                    <th class="px-3.5 py-3 text-right">Allowances</th>
                                    <th class="px-3.5 py-3 text-right">OT & Variable</th>
                                    <th class="px-3.5 py-3 text-right font-bold">Gross Pay</th>
                                    <th class="px-3.5 py-3 text-right text-rose-600">EPF (EE)</th>
                                    <th class="px-3.5 py-3 text-right text-rose-600">PCB Tax</th>
                                    <th class="px-3.5 py-3 text-right text-rose-600">Other Ded.</th>
                                    <th class="px-3.5 py-3 text-right font-black text-emerald-600">Net Pay</th>
                                    <th class="px-3.5 py-3 text-right text-purple-600">EPF (ER)</th>
                                    <th class="px-3.5 py-3 text-center">Payslip</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr
                                    v-for="item in items"
                                    :key="item.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                                >
                                    <td class="px-3.5 py-3">
                                        <div class="font-bold text-zinc-900 dark:text-white">{{ item.employee_name }}</div>
                                        <div class="text-[10px] text-zinc-500 font-mono">{{ item.employee_code }} • {{ item.department }}</div>
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                        {{ formatMoney(item.base_salary) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                        {{ formatMoney(item.allowances_total) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                        {{ formatMoney(item.overtime_pay + item.commission + item.bonus) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono font-bold text-zinc-900 dark:text-white">
                                        {{ formatMoney(item.gross_pay) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono text-rose-600">
                                        -{{ formatMoney(item.epf_employee) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono text-rose-600">
                                        -{{ formatMoney(item.tax_pcb) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono text-zinc-600">
                                        {{ formatMoney(item.socso_employee + item.eis_employee + item.loan_deduction + item.advance_recovery) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                        {{ formatMoney(item.net_pay) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right font-mono font-medium text-purple-600">
                                        +{{ formatMoney(item.epf_employer) }}
                                    </td>
                                    <td class="px-3.5 py-3 text-center">
                                        <Link
                                            :href="`/admin/payroll/payslips/${item.employee_id}`"
                                            class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950 inline-block font-semibold"
                                            title="View Official Payslip"
                                        >
                                            📄 Slip
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- TAB 2: Statutory Summary -->
            <div v-if="activeTab === 'statutory'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Card class="p-5 space-y-3">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">EPF / KWSP Contributions</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-600">Employee Deduction (11%):</span>
                            <span class="font-mono font-bold text-rose-600">{{ formatMoney(statutorySummary.epf_total_employee) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600">Employer Contribution (12-13%):</span>
                            <span class="font-mono font-bold text-purple-600">{{ formatMoney(statutorySummary.epf_total_employer) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-800 font-bold">
                            <span>Total Payable to KWSP:</span>
                            <span class="font-mono text-zinc-900 dark:text-white">{{ formatMoney(statutorySummary.epf_grand_total) }}</span>
                        </div>
                    </div>
                </Card>

                <Card class="p-5 space-y-3">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">SOCSO & EIS (PERKESO)</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-600">SOCSO Total (EE + ER):</span>
                            <span class="font-mono font-bold">{{ formatMoney(statutorySummary.socso_grand_total) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-600">EIS Total (EE + ER):</span>
                            <span class="font-mono font-bold">{{ formatMoney(statutorySummary.eis_grand_total) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-800 font-bold">
                            <span>Total Payable to PERKESO:</span>
                            <span class="font-mono text-zinc-900 dark:text-white">{{ formatMoney(statutorySummary.socso_grand_total + statutorySummary.eis_grand_total) }}</span>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- TAB 3: Banking Batch Export -->
            <div v-if="activeTab === 'banking'" class="space-y-4">
                <Card class="p-6 space-y-4">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Bank Direct Credit File Generation (Autopay / Batch Salary Disbursal)</h3>
                    <p class="text-xs text-zinc-500">
                        Generate official batch payment text/CSV files compatible with corporate banking portals (Standard NACHA, ISO 20022 XML, CSV Direct Credit).
                    </p>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div class="space-y-1 text-xs">
                            <div class="font-bold text-zinc-900 dark:text-white">Primary Corporate Banking Batch (ACH / ISO 20022)</div>
                            <div class="text-zinc-500">Includes active employee bank accounts &bull; Total: <strong>{{ run.currency_symbol || '$' }} {{ Number(run.net_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</strong></div>
                        </div>
                        <Button variant="outline" size="sm">⬇️ Export Bank Batch</Button>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div class="space-y-1 text-xs">
                            <div class="font-bold text-zinc-900 dark:text-white">Universal CSV Salary Disbursal Format</div>
                            <div class="text-zinc-500">Compatible with multi-currency payroll accounts and wire transfers</div>
                        </div>
                        <Button variant="outline" size="sm">⬇️ Download CSV</Button>
                    </div>
                </Card>
            </div>
        </div>
    </OrganizationLayout>
</template>
