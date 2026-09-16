<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { useCurrency } from '@/composables/useCurrency';
import { Card, Badge, Button } from '@/components';

export interface LoanItem {
    id: number;
    loan_number: string;
    type: 'company_loan' | 'salary_advance';
    employee_id: number;
    employee_code: string;
    employee_name: string;
    department: string;
    principal_amount: number;
    interest_rate: number;
    total_payable: number;
    monthly_installment: number;
    total_installments: number;
    paid_installments: number;
    total_repaid: number;
    remaining_balance: number;
    start_period: string;
    end_period: string;
    status: 'active' | 'completed' | 'paused';
    reason: string;
    approved_by: string;
}

export interface LoanStats {
    total_disbursed: number;
    total_repaid: number;
    outstanding_balance: number;
    active_loans_count: number;
    currency: string;
    currency_symbol: string;
}

const props = defineProps<{
    loans: {
        data: LoanItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    stats: LoanStats;
}>();

const isModalOpen = ref(false);
const newEmployeeName = ref('');
const newAmount = ref<number>(2000.00);
const newInstallmentCount = ref<number>(10);
const newType = ref<'company_loan' | 'salary_advance'>('company_loan');
const newReason = ref('');

const { currency, currencySymbol } = useCurrency();

const formatMoney = (val: number) => {
    return (props.stats?.currency_symbol || currencySymbol.value) + ' ' + (Number(val) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleCreateLoan = () => {
    router.post('/admin/payroll/loans', {
        type: newType.value,
        employee_name: newEmployeeName.value,
        principal_amount: newAmount.value,
        total_installments: newInstallmentCount.value,
        monthly_installment: newAmount.value / newInstallmentCount.value,
        reason: newReason.value,
    }, {
        onSuccess: () => {
            isModalOpen.value = false;
        }
    });
};
</script>

<template>
    <Head title="Employee Loans & Advances - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Loans & Advances</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Staff Loans & Salary Advances Ledger</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Disburse company loans, track automated monthly installment deductions on payslips, and monitor outstanding principal.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="primary" size="sm" @click="isModalOpen = true">
                        + Issue Loan / Advance
                    </Button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Total Disbursed</div>
                    <div class="text-xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ formatMoney(stats.total_disbursed) }}
                    </div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Total Repaid via Payroll</div>
                    <div class="text-xl font-black text-emerald-600 font-mono">
                        {{ formatMoney(stats.total_repaid) }}
                    </div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Outstanding Balance</div>
                    <div class="text-xl font-black text-rose-600 font-mono">
                        {{ formatMoney(stats.outstanding_balance) }}
                    </div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-[11px] font-semibold text-zinc-500 uppercase">Active Running Loans</div>
                    <div class="text-xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ stats.active_loans_count }} Active
                    </div>
                </Card>
            </div>

            <!-- Loans Table -->
            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5">Loan Ref & Type</th>
                                <th class="px-4 py-3.5">Employee</th>
                                <th class="px-4 py-3.5 text-right">Principal</th>
                                <th class="px-4 py-3.5 text-right">Monthly Installment</th>
                                <th class="px-4 py-3.5 text-center">Progress</th>
                                <th class="px-4 py-3.5 text-right font-bold">Remaining</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="l in loans.data"
                                :key="l.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="font-mono font-bold text-zinc-900 dark:text-white">{{ l.loan_number }}</div>
                                    <div class="capitalize text-[10px] font-semibold text-emerald-600">{{ l.type.replace('_', ' ') }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ l.employee_name }}</div>
                                    <div class="text-[10px] text-zinc-500 font-mono">{{ l.employee_code }} • {{ l.department }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ formatMoney(l.principal_amount) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-900 dark:text-white font-bold">
                                    {{ formatMoney(l.monthly_installment) }} <span class="text-[10px] font-normal text-zinc-400">/mo</span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <div class="font-semibold">{{ l.paid_installments }} / {{ l.total_installments }} Paid</div>
                                    <!-- Progress Bar -->
                                    <div class="w-24 mx-auto bg-zinc-200 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden mt-1">
                                        <div class="bg-emerald-500 h-full rounded-full" :style="{ width: `${(l.paid_installments / l.total_installments) * 100}%` }"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                    {{ formatMoney(l.remaining_balance) }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <Badge :variant="l.status === 'completed' ? 'secondary' : 'success'" class="uppercase text-[9px]">
                                        {{ l.status }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Issue Loan Modal -->
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-lg w-full space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">Issue Staff Loan / Salary Advance</h3>
                        <button type="button" @click="isModalOpen = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Disbursement Type</label>
                            <select
                                v-model="newType"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-medium"
                            >
                                <option value="company_loan">Company Staff Loan (Long-term, Multi-installment)</option>
                                <option value="salary_advance">Emergency Salary Advance (1-3 installments)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Employee Name / Search</label>
                            <input
                                v-model="newEmployeeName"
                                type="text"
                                placeholder="e.g., Ahmad Daniel Bin Roslan"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Principal Amount ({{ stats?.currency || currency }})</label>
                                <input
                                    v-model.number="newAmount"
                                    type="number"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Total Installment Months</label>
                                <input
                                    v-model.number="newInstallmentCount"
                                    type="number"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Reason / Purpose</label>
                            <textarea
                                v-model="newReason"
                                rows="2"
                                placeholder="e.g. Workstation Hardware Purchase Scheme"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                        <Button variant="outline" size="sm" @click="isModalOpen = false">Cancel</Button>
                        <Button variant="primary" size="sm" @click="handleCreateLoan">Disburse & Establish Schedule</Button>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
