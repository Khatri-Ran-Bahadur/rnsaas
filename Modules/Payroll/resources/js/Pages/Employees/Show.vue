<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface EmployeeProfile {
    id: number;
    name: string;
    employee_code: string;
    nric_passport: string;
    email: string;
    phone: string;
    department: string;
    designation: string;
    employment_type: string;
    joining_date: string;
    payroll_group: string;
    currency: string;
    currency_symbol: string;
    status: string;
    current_compensation: {
        base_salary: number;
        pay_rate_type: string;
        effective_since: string;
        allowances: Array<{ id: number; name: string; amount: number; taxable: boolean; epf_applicable: boolean }>;
        fixed_deductions: Array<{ id: number; name: string; amount: number; remaining_balance?: number }>;
        total_gross_package: number;
    };
    statutory_profile: {
        epf_member_no: string;
        epf_employee_rate: string;
        epf_employer_rate: string;
        socso_member_no: string;
        tax_number: string;
        tax_residency: string;
        marital_status: string;
        working_spouse: boolean;
        children_count: number;
    };
    bank_details: {
        bank_name: string;
        account_number: string;
        account_holder: string;
        payment_method: string;
    };
    salary_history: Array<{
        id: number;
        effective_date: string;
        base_salary: number;
        total_gross: number;
        increment_amount: number;
        increment_percentage: string;
        reason: string;
        approved_by: string;
        created_at: string;
    }>;
}

const props = defineProps<{
    employee: EmployeeProfile;
}>();

const isRevisionModalOpen = ref(false);
const newEffectiveDate = ref('2026-10-01');
const newBaseSalary = ref<number>(9500.00);
const revisionReason = ref('Annual Merit Increment & Performance Promotion');
const approvedBy = ref('Chief Executive Officer');

const formatMoney = (val: number) => {
    return (props.employee?.currency_symbol || 'RM') + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleSaveRevision = () => {
    router.post(`/admin/payroll/employees/${props.employee.id}/revision`, {
        effective_date: newEffectiveDate.value,
        base_salary: newBaseSalary.value,
        reason: revisionReason.value,
        approved_by: approvedBy.value,
    }, {
        onSuccess: () => {
            isRevisionModalOpen.value = false;
        }
    });
};
</script>

<template>
    <Head :title="`${employee.name} - Compensation & Salary History`" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <Link href="/admin/payroll/employees" class="hover:text-emerald-600 transition">Employees</Link>
                        <span>/</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-300 font-medium">{{ employee.employee_code }}</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ employee.name }}
                        </h1>
                        <Badge variant="success" class="uppercase text-[10px]">{{ employee.status }}</Badge>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        {{ employee.designation }} • {{ employee.department }} • Group: <strong>{{ employee.payroll_group }}</strong>
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="primary" size="sm" @click="isRevisionModalOpen = true">
                        + Record Salary Revision
                    </Button>
                    <Link
                        href="/admin/payroll/employees"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 transition"
                    >
                        Back
                    </Link>
                </div>
            </div>

            <!-- Current Active Compensation Package -->
            <Card class="p-6 border-l-4 border-emerald-500 bg-gradient-to-r from-emerald-50/40 via-white to-slate-50 dark:from-emerald-950/20 dark:via-zinc-900 dark:to-zinc-900">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="space-y-2">
                        <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                            Active Compensation Package (Effective Since {{ employee.current_compensation.effective_since }})
                        </div>
                        <div class="flex items-baseline space-x-3">
                            <span class="text-3xl font-black font-mono text-zinc-900 dark:text-white">
                                {{ formatMoney(employee.current_compensation.base_salary) }}
                            </span>
                            <span class="text-xs text-zinc-500 font-semibold">/ month Basic Salary</span>
                        </div>
                        <div class="text-xs text-zinc-600 dark:text-zinc-400">
                            Total Gross Package: <strong class="font-mono text-emerald-600 font-bold text-sm">{{ formatMoney(employee.current_compensation.total_gross_package) }}</strong> (Includes {{ employee.current_compensation.allowances.length }} fixed allowances)
                        </div>
                    </div>

                    <!-- Allowances Badges -->
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="alw in employee.current_compensation.allowances"
                            :key="alw.id"
                            class="px-3 py-2 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-xs shadow-xs"
                        >
                            <span class="text-zinc-500 block text-[10px]">{{ alw.name }}</span>
                            <strong class="font-mono text-zinc-900 dark:text-white">{{ formatMoney(alw.amount) }}</strong>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Middle Split: Left (Statutory & Banking Details), Right (Salary Revision History Timeline) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Profile & Banking & Statutory (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <!-- Statutory Profile -->
                    <Card class="p-5 space-y-3">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white pb-2 border-b border-zinc-200 dark:border-zinc-800">
                            🏛️ Statutory & Tax Withholding Profile
                        </h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-zinc-500">EPF Member Number:</span>
                                <strong class="font-mono">{{ employee.statutory_profile.epf_member_no }} ({{ employee.statutory_profile.epf_employee_rate }} EE / {{ employee.statutory_profile.epf_employer_rate }} ER)</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">SOCSO Number:</span>
                                <strong class="font-mono">{{ employee.statutory_profile.socso_member_no }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">LHDN Tax Number:</span>
                                <strong class="font-mono">{{ employee.statutory_profile.tax_number }} ({{ employee.statutory_profile.tax_residency }})</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Marital & Dependents:</span>
                                <strong>{{ employee.statutory_profile.marital_status }} ({{ employee.statutory_profile.children_count }} children)</strong>
                            </div>
                        </div>
                    </Card>

                    <!-- Banking Details -->
                    <Card class="p-5 space-y-3">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white pb-2 border-b border-zinc-200 dark:border-zinc-800">
                            💳 Bank Direct Disbursement Details
                        </h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Bank Name:</span>
                                <strong>{{ employee.bank_details.bank_name }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Account Number:</span>
                                <strong class="font-mono text-emerald-600">{{ employee.bank_details.account_number }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Beneficiary Name:</span>
                                <strong>{{ employee.bank_details.account_holder }}</strong>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Right: Chronological Salary Revision History Timeline (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <Card class="p-5 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">📜 Salary Revision History Timeline</h3>
                                <p class="text-[11px] text-zinc-500">Historical records are immutable. Past payroll runs preserve their historical compensation.</p>
                            </div>
                        </div>

                        <!-- Timeline List -->
                        <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-zinc-200 dark:before:bg-zinc-800">
                            <div
                                v-for="rev in employee.salary_history"
                                :key="rev.id"
                                class="relative space-y-1.5"
                            >
                                <!-- Timeline Bullet -->
                                <div class="absolute -left-6 top-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white dark:border-zinc-900"></div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-mono font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md">
                                        Effective: {{ rev.effective_date }}
                                    </span>
                                    <span v-if="rev.increment_amount > 0" class="text-xs font-bold text-emerald-600">
                                        +{{ formatMoney(rev.increment_amount) }} ({{ rev.increment_percentage }})
                                    </span>
                                    <span v-else class="text-xs text-zinc-400 font-medium">Initial Offer</span>
                                </div>

                                <div class="text-base font-black font-mono text-zinc-900 dark:text-white">
                                    {{ formatMoney(rev.base_salary) }} <span class="text-xs font-normal text-zinc-500">Basic</span>
                                </div>

                                <div class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">
                                    "{{ rev.reason }}"
                                </div>

                                <div class="text-[11px] text-zinc-400">
                                    Approved by: <strong>{{ rev.approved_by }}</strong> • Recorded: {{ rev.created_at }}
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Modal: Record New Salary Revision -->
            <div
                v-if="isRevisionModalOpen"
                class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 max-w-lg w-full space-y-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">Record New Salary Revision</h3>
                        <button type="button" @click="isRevisionModalOpen = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Effective Date</label>
                            <input
                                v-model="newEffectiveDate"
                                type="date"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">New Basic Salary ({{ employee.currency || 'Currency' }})</label>
                            <input
                                v-model.number="newBaseSalary"
                                type="number"
                                step="100"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-sm font-bold"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Increment Reason / Appraisal Notes</label>
                            <textarea
                                v-model="revisionReason"
                                rows="3"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Approving Officer / Authority</label>
                            <input
                                v-model="approvedBy"
                                type="text"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                        <Button variant="outline" size="sm" @click="isRevisionModalOpen = false">Cancel</Button>
                        <Button variant="primary" size="sm" @click="handleSaveRevision">Save Revision</Button>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
