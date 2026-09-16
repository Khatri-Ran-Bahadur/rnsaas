<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Button } from '@/components';

export interface PayslipData {
    id: number;
    slip_number: string;
    run_id: number;
    run_number: string;
    period_start: string;
    period_end: string;
    pay_date: string;
    company: {
        name: string;
        reg_no: string;
        employer_epf_no: string;
        employer_socso_no: string;
        employer_tax_no: string;
        address: string;
        currency: string;
        currency_symbol: string;
    };
    employee: {
        id: number;
        name: string;
        employee_code: string;
        nric_passport: string;
        department: string;
        designation: string;
        joining_date: string;
        employment_type: string;
        epf_number: string;
        socso_number: string;
        tax_number: string;
        bank_name: string;
        bank_account: string;
        payment_method: string;
    };
    attendance_summary: {
        working_days: number;
        days_worked: number;
        paid_leave_days: number;
        unpaid_leave_days: number;
        normal_ot_hours: number;
        rest_day_ot_hours: number;
        holiday_ot_hours: number;
    };
    earnings: Array<{ name: string; type: string; amount: number }>;
    deductions: Array<{ name: string; type: string; amount: number }>;
    employer_contributions: Array<{ name: string; amount: number }>;
    totals: {
        gross_earnings: number;
        total_deductions: number;
        net_pay: number;
        employer_total_statutory: number;
        total_cost_to_company: number;
    };
    ytd_accumulators: {
        ytd_gross_pay: number;
        ytd_epf_employee: number;
        ytd_epf_employer: number;
        ytd_socso_employee: number;
        ytd_eis_employee: number;
        ytd_tax_pcb: number;
        ytd_net_pay: number;
    };
}

const props = defineProps<{
    payslip: PayslipData;
}>();

const formatMoney = (val: number) => {
    return (props.payslip?.company?.currency_symbol || 'RM') + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const triggerPrint = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Payslip ${payslip.slip_number} - ${payslip.employee.name}`" />

    <OrganizationLayout>
        <div class="space-y-6 max-w-4xl mx-auto">
            <!-- Header with Quick Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <Link href="/admin/payroll/payslips" class="hover:text-emerald-600 transition">Payslips</Link>
                        <span>/</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-300 font-medium">{{ payslip.slip_number }}</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Official Salary Voucher</span>
                    </h1>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="outline" size="sm" @click="triggerPrint">
                        🖨️ Print Payslip
                    </Button>
                    <Link
                        href="/admin/payroll/payslips"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 transition"
                    >
                        Back to List
                    </Link>
                </div>
            </div>

            <!-- Printable Payslip Document Container -->
            <div id="printable-payslip" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-8 shadow-sm space-y-6 text-zinc-900 dark:text-white print:border-none print:shadow-none print:p-0">
                <!-- Company Header -->
                <div class="flex justify-between items-start border-b border-zinc-200 dark:border-zinc-800 pb-6">
                    <div class="space-y-1">
                        <h2 class="text-xl font-black tracking-tight text-zinc-900 dark:text-white uppercase">
                            {{ payslip.company.name }}
                        </h2>
                        <div class="text-xs text-zinc-500 space-y-0.5">
                            <div>Co. Reg No: {{ payslip.company.reg_no }}</div>
                            <div>{{ payslip.company.address }}</div>
                            <div class="text-[11px] pt-1">
                                EPF: <strong>{{ payslip.company.employer_epf_no }}</strong> • SOCSO: <strong>{{ payslip.company.employer_socso_no }}</strong> • Tax: <strong>{{ payslip.company.employer_tax_no }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="text-right space-y-1">
                        <div class="inline-block px-3 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-bold text-xs uppercase rounded-lg border border-emerald-200 dark:border-emerald-800">
                            CONFIDENTIAL PAYSLIP
                        </div>
                        <div class="font-mono text-xs font-bold">{{ payslip.slip_number }}</div>
                        <div class="text-xs text-zinc-500">Pay Date: <strong>{{ payslip.pay_date }}</strong></div>
                    </div>
                </div>

                <!-- Employee Information Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-xs">
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Employee Name</span>
                        <strong class="text-zinc-900 dark:text-white">{{ payslip.employee.name }}</strong>
                    </div>
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Employee Code & NRIC</span>
                        <strong class="font-mono text-zinc-900 dark:text-white">{{ payslip.employee.employee_code }} • {{ payslip.employee.nric_passport }}</strong>
                    </div>
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Department / Role</span>
                        <span class="text-zinc-800 dark:text-zinc-200">{{ payslip.employee.department }} ({{ payslip.employee.designation }})</span>
                    </div>
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Bank & Account</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ payslip.employee.bank_name }} ({{ payslip.employee.bank_account }})</span>
                    </div>
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">EPF Member No</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ payslip.employee.epf_number }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">SOCSO / Tax No</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ payslip.employee.socso_number }} / {{ payslip.employee.tax_number }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Payroll Period</span>
                        <span class="text-zinc-800 dark:text-zinc-200">{{ payslip.period_start }} to {{ payslip.period_end }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-500 block text-[10px] uppercase font-semibold">Days Worked / OT</span>
                        <span class="text-zinc-800 dark:text-zinc-200">{{ payslip.attendance_summary.days_worked }} days • {{ payslip.attendance_summary.normal_ot_hours + payslip.attendance_summary.rest_day_ot_hours }}h OT</span>
                    </div>
                </div>

                <!-- Earnings & Deductions Tables (Side by Side) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                    <!-- Left: Earnings -->
                    <div class="space-y-2 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 bg-white dark:bg-zinc-900">
                        <h3 class="font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider text-[11px] pb-2 border-b border-zinc-200 dark:border-zinc-800">
                            1. Gross Earnings & Allowances
                        </h3>
                        <div class="space-y-1.5 divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            <div
                                v-for="(earn, idx) in payslip.earnings"
                                :key="idx"
                                class="flex justify-between pt-1.5"
                            >
                                <span class="text-zinc-700 dark:text-zinc-300">{{ earn.name }}</span>
                                <span class="font-mono font-semibold">{{ formatMoney(earn.amount) }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between pt-3 border-t-2 border-zinc-300 dark:border-zinc-700 font-bold text-sm text-zinc-900 dark:text-white">
                            <span>Total Gross Earnings:</span>
                            <span class="font-mono">{{ formatMoney(payslip.totals.gross_earnings) }}</span>
                        </div>
                    </div>

                    <!-- Right: Deductions -->
                    <div class="space-y-2 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 bg-white dark:bg-zinc-900">
                        <h3 class="font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider text-[11px] pb-2 border-b border-zinc-200 dark:border-zinc-800">
                            2. Employee Deductions & Tax
                        </h3>
                        <div class="space-y-1.5 divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            <div
                                v-for="(ded, idx) in payslip.deductions"
                                :key="idx"
                                class="flex justify-between pt-1.5"
                            >
                                <span class="text-zinc-700 dark:text-zinc-300">{{ ded.name }}</span>
                                <span class="font-mono font-semibold text-rose-600">-{{ formatMoney(ded.amount) }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between pt-3 border-t-2 border-zinc-300 dark:border-zinc-700 font-bold text-sm text-rose-600">
                            <span>Total Deductions:</span>
                            <span class="font-mono">-{{ formatMoney(payslip.totals.total_deductions) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Net Take-Home Salary Banner -->
                <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between shadow-md">
                    <div>
                        <div class="text-xs uppercase font-bold tracking-wider opacity-90">Net Pay Credited to Bank</div>
                        <div class="text-[11px] opacity-80">Payment Method: {{ payslip.employee.payment_method }} ({{ payslip.employee.bank_name }})</div>
                    </div>
                    <div class="text-3xl font-black font-mono mt-2 sm:mt-0">
                        {{ formatMoney(payslip.totals.net_pay) }}
                    </div>
                </div>

                <!-- Employer Statutory Contributions & YTD Accumulators -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs pt-2">
                    <!-- Employer Contributions -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-2">
                        <h4 class="font-bold text-purple-700 dark:text-purple-400 text-[11px] uppercase tracking-wider">
                            Employer Statutory Contributions
                        </h4>
                        <div class="space-y-1">
                            <div
                                v-for="(contrib, idx) in payslip.employer_contributions"
                                :key="idx"
                                class="flex justify-between text-zinc-600 dark:text-zinc-400"
                            >
                                <span>{{ contrib.name }}</span>
                                <span class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ formatMoney(contrib.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Year-To-Date (YTD) Summary -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-2">
                        <h4 class="font-bold text-zinc-700 dark:text-zinc-300 text-[11px] uppercase tracking-wider">
                            Year-To-Date (YTD) Summary
                        </h4>
                        <div class="space-y-1 text-zinc-600 dark:text-zinc-400">
                            <div class="flex justify-between">
                                <span>YTD Gross Earnings:</span>
                                <span class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ formatMoney(payslip.ytd_accumulators.ytd_gross_pay) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>YTD EPF (Employee):</span>
                                <span class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ formatMoney(payslip.ytd_accumulators.ytd_epf_employee) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>YTD PCB Tax Deducted:</span>
                                <span class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ formatMoney(payslip.ytd_accumulators.ytd_tax_pcb) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-zinc-900 dark:text-white pt-1 border-t border-zinc-200 dark:border-zinc-800">
                                <span>YTD Net Pay:</span>
                                <span class="font-mono text-emerald-600">{{ formatMoney(payslip.ytd_accumulators.ytd_net_pay) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="text-center text-[10px] text-zinc-400 border-t border-zinc-200 dark:border-zinc-800 pt-4">
                    This is a computer-generated salary voucher. No physical signature is required. Generated via SathiSaaS Universal Payroll Engine.
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-payslip, #printable-payslip * {
        visibility: visible;
    }
    #printable-payslip {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
}
</style>
