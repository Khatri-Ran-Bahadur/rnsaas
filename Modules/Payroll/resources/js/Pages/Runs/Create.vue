<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface GroupOption {
    id: number;
    name: string;
    frequency: string;
    working_days_per_month: number;
    standard_hours_per_day: number;
    active_headcount: number;
    default_payment_method: string;
}

export interface EmployeeRosterItem {
    id: number;
    name: string;
    employee_code: string;
    department: string;
    designation: string;
    employment_type: string;
    base_salary: number;
    currency: string;
    housing_allowance: number;
    transport_allowance: number;
    meal_allowance: number;
    attendance_days: number;
    absent_days: number;
    unpaid_leave_days: number;
    overtime_hours_15: number;
    overtime_hours_20: number;
    commission: number;
    bonus: number;
    loan_deduction: number;
    advance_recovery: number;
    epf_employee_pct: number;
    epf_employer_pct: number;
    socso_employee: number;
    socso_employer: number;
    eis_employee: number;
    eis_employer: number;
    tax_pcb: number;
    proration_factor: number;
}

const props = defineProps<{
    groups: GroupOption[];
    employees: EmployeeRosterItem[];
    statutorySchemes: Array<{ code: string; name: string; employee_rate?: string; employer_rate?: string; ceiling?: string }>;
}>();

const currentStep = ref<1 | 2 | 3 | 4 | 5>(1);

// Step 1 Form Data
const selectedGroupId = ref<number>(props.groups[0]?.id || 1);
const periodStart = ref('2026-09-01');
const periodEnd = ref('2026-09-30');
const payDate = ref('2026-10-05');
const attendanceCutoff = ref('2026-09-27');
const overtimeCutoff = ref('2026-09-27');
const runTitle = ref('September 2026 (Monthly Standard)');

// Step 3 Employee Inputs
const roster = ref<EmployeeRosterItem[]>(JSON.parse(JSON.stringify(props.employees)));

const selectedGroup = computed(() => {
    return props.groups?.find(g => g.id === selectedGroupId.value) || props.groups?.[0] || {
        id: 1,
        name: 'HQ & Corporate Monthly',
        frequency: 'monthly',
        working_days_per_month: 22,
        standard_hours_per_day: 8,
        active_headcount: 1,
        default_payment_method: 'bank_transfer',
    };
});

// Real-time calculation helper
const calculateEmployeeLine = (emp: EmployeeRosterItem) => {
    const hourlyBase = (emp.base_salary / (selectedGroup.value.working_days_per_month * 8));
    const ot15Pay = emp.overtime_hours_15 * hourlyBase * 1.5;
    const ot20Pay = emp.overtime_hours_20 * hourlyBase * 2.0;
    const totalOT = ot15Pay + ot20Pay;

    const baseEarned = emp.base_salary * (emp.proration_factor || 1.0);
    const allowances = emp.housing_allowance + emp.transport_allowance + emp.meal_allowance;
    const gross = baseEarned + allowances + totalOT + Number(emp.commission || 0) + Number(emp.bonus || 0);

    // Statutory Calculations
    const epfEmployee = gross * (emp.epf_employee_pct / 100);
    const epfEmployer = gross * (emp.epf_employer_pct / 100);
    const socsoEmployee = emp.socso_employee;
    const socsoEmployer = emp.socso_employer;
    const eisEmployee = emp.eis_employee;
    const eisEmployer = emp.eis_employer;
    const tax = Number(emp.tax_pcb || 0);

    const totalDeductions = epfEmployee + socsoEmployee + eisEmployee + tax + Number(emp.loan_deduction || 0) + Number(emp.advance_recovery || 0);
    const net = gross - totalDeductions;
    const totalEmployerCost = gross + epfEmployer + socsoEmployer + eisEmployer;

    return {
        gross,
        totalOT,
        epfEmployee,
        epfEmployer,
        socsoEmployee,
        socsoEmployer,
        eisEmployee,
        eisEmployer,
        totalDeductions,
        net,
        totalEmployerCost,
    };
};

const summaryTotals = computed(() => {
    let grossTotal = 0;
    let deductionsTotal = 0;
    let netTotal = 0;
    let employerCostTotal = 0;

    roster.value.forEach(emp => {
        const line = calculateEmployeeLine(emp);
        grossTotal += line.gross;
        deductionsTotal += line.totalDeductions;
        netTotal += line.net;
        employerCostTotal += line.totalEmployerCost;
    });

    return {
        grossTotal,
        deductionsTotal,
        netTotal,
        employerCostTotal,
        headcount: roster.value.length,
    };
});

import { useCurrency } from '@/composables/useCurrency';

const { currencySymbol } = useCurrency();

const formatMoney = (val: number) => {
    return currencySymbol.value + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const handleNextStep = () => {
    if (currentStep.value < 5) {
        currentStep.value++;
    }
};

const handlePrevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const handleSubmitRun = () => {
    router.post('/admin/payroll/runs', {
        group_id: selectedGroupId.value,
        payroll_group_id: selectedGroupId.value,
        title: runTitle.value,
        period_name: runTitle.value,
        start_date: periodStart.value,
        end_date: periodEnd.value,
        pay_date: payDate.value,
        attendance_cutoff: attendanceCutoff.value,
        overtime_cutoff: overtimeCutoff.value,
    });
};
</script>

<template>
    <Head title="Payroll Wizard - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <Link href="/admin/payroll/runs" class="hover:text-emerald-600 transition">Runs</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">New Payroll Wizard</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Payroll Execution Wizard</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Multi-step engine for attendance ingestion, overtime, statutory deductions, tax withholding, and approval.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin/payroll/runs"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 transition"
                    >
                        Cancel
                    </Link>
                </div>
            </div>

            <!-- Stepper Navigation Bar -->
            <div class="bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-xs">
                <div class="flex items-center justify-between">
                    <div
                        v-for="s in [
                            { step: 1, label: '1. Period & Group' },
                            { step: 2, label: '2. Eligible Roster' },
                            { step: 3, label: '3. Attendance & OT' },
                            { step: 4, label: '4. Statutory & Pre-Check' },
                            { step: 5, label: '5. Review & Submit' }
                        ]"
                        :key="s.step"
                        @click="currentStep = s.step as any"
                        :class="[
                            'flex items-center space-x-2 text-xs font-bold cursor-pointer transition py-1 px-3 rounded-xl',
                            currentStep === s.step
                                ? 'bg-emerald-600 text-white shadow-md'
                                : currentStep > s.step
                                ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40'
                                : 'text-zinc-400 hover:text-zinc-600'
                        ]"
                    >
                        <span>{{ s.label }}</span>
                    </div>
                </div>
            </div>

            <!-- STEP 1: Period & Payroll Group Selection -->
            <div v-if="currentStep === 1" class="space-y-6">
                <Card class="p-6 space-y-5">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Step 1: Select Payroll Group & Period Dates</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Payroll Run Title</label>
                            <input
                                v-model="runTitle"
                                type="text"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Target Payroll Group</label>
                            <select
                                v-model="selectedGroupId"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-medium"
                            >
                                <option v-for="g in groups" :key="g.id" :value="g.id">
                                    {{ g.name }} ({{ g.frequency }} • {{ g.active_headcount }} staff)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Disbursement / Pay Date</label>
                            <input
                                v-model="payDate"
                                type="date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Period Start Date</label>
                            <input
                                v-model="periodStart"
                                type="date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Period End Date</label>
                            <input
                                v-model="periodEnd"
                                type="date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Attendance Cutoff Date</label>
                            <input
                                v-model="attendanceCutoff"
                                type="date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Overtime Cutoff Date</label>
                            <input
                                v-model="overtimeCutoff"
                                type="date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                            />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- STEP 2: Eligible Roster & Salary Snapshots -->
            <div v-if="currentStep === 2" class="space-y-6">
                <Card class="overflow-hidden">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Step 2: Eligible Employee Roster & Historical Compensation</h3>
                            <p class="text-xs text-zinc-500">Locked salary snapshots at effective date without modifying employee profile</p>
                        </div>
                        <Badge variant="success">{{ roster.length }} Eligible Staff Loaded</Badge>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px]">
                                <tr>
                                    <th class="px-4 py-3">Employee</th>
                                    <th class="px-4 py-3">Department & Designation</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3 text-right">Base Salary</th>
                                    <th class="px-4 py-3 text-right">Fixed Allowances</th>
                                    <th class="px-4 py-3 text-right">Fixed Gross</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr v-for="emp in roster" :key="emp.id" class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-zinc-900 dark:text-white">{{ emp.name }}</div>
                                        <div class="font-mono text-[10px] text-zinc-500">{{ emp.employee_code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ emp.department }}</div>
                                        <div class="text-[11px]">{{ emp.designation }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="capitalize text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                                            {{ emp.employment_type.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-zinc-900 dark:text-white">
                                        {{ formatMoney(emp.base_salary) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                        {{ formatMoney(emp.housing_allowance + emp.transport_allowance + emp.meal_allowance) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ formatMoney(emp.base_salary + emp.housing_allowance + emp.transport_allowance + emp.meal_allowance) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- STEP 3: Attendance, Overtime & Adjustments -->
            <div v-if="currentStep === 3" class="space-y-6">
                <Card class="overflow-hidden">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Step 3: Ingested Attendance, OT Hours & Variable Additions</h3>
                            <p class="text-xs text-zinc-500">Overtime hours, sales commissions, spot bonuses, and unpaid leave proration</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px]">
                                <tr>
                                    <th class="px-3 py-3">Employee</th>
                                    <th class="px-3 py-3 text-center">Att. Days</th>
                                    <th class="px-3 py-3 text-center">Unpaid Leave</th>
                                    <th class="px-3 py-3 text-center">Normal OT (1.5x)</th>
                                    <th class="px-3 py-3 text-center">Rest Day OT (2.0x)</th>
                                    <th class="px-3 py-3 text-center">Commission (RM)</th>
                                    <th class="px-3 py-3 text-center">Bonus (RM)</th>
                                    <th class="px-3 py-3 text-right">Total Variable Pay</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr v-for="emp in roster" :key="emp.id" class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50">
                                    <td class="px-3 py-2.5">
                                        <div class="font-bold text-zinc-900 dark:text-white">{{ emp.name }}</div>
                                        <div class="font-mono text-[10px] text-zinc-500">{{ emp.employee_code }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-bold">
                                        <input
                                            v-model.number="emp.attendance_days"
                                            type="number"
                                            class="w-16 px-2 py-1 text-center bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <input
                                            v-model.number="emp.unpaid_leave_days"
                                            type="number"
                                            class="w-16 px-2 py-1 text-center bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs text-rose-600"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <input
                                            v-model.number="emp.overtime_hours_15"
                                            type="number"
                                            step="0.5"
                                            class="w-16 px-2 py-1 text-center bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <input
                                            v-model.number="emp.overtime_hours_20"
                                            type="number"
                                            step="0.5"
                                            class="w-16 px-2 py-1 text-center bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <input
                                            v-model.number="emp.commission"
                                            type="number"
                                            class="w-20 px-2 py-1 text-right bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <input
                                            v-model.number="emp.bonus"
                                            type="number"
                                            class="w-20 px-2 py-1 text-right bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ formatMoney(calculateEmployeeLine(emp).totalOT + Number(emp.commission || 0) + Number(emp.bonus || 0)) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- STEP 4: Statutory Calculation & Pre-Check -->
            <div v-if="currentStep === 4" class="space-y-6">
                <Card class="overflow-hidden">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Step 4: Statutory Contributions & Tax Withholdings (Pre-Check)</h3>
                            <p class="text-xs text-zinc-500">Employee & Employer EPF/KWSP, SOCSO, EIS, and Monthly Tax Deduction (PCB)</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px]">
                                <tr>
                                    <th class="px-3 py-3">Employee</th>
                                    <th class="px-3 py-3 text-right">Gross Pay</th>
                                    <th class="px-3 py-3 text-right">EPF (EE)</th>
                                    <th class="px-3 py-3 text-right">SOCSO + EIS</th>
                                    <th class="px-3 py-3 text-right">PCB Tax</th>
                                    <th class="px-3 py-3 text-right">Loan Ded.</th>
                                    <th class="px-3 py-3 text-right font-bold text-emerald-600">Net Pay</th>
                                    <th class="px-3 py-3 text-right text-purple-600">EPF (ER)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr v-for="emp in roster" :key="emp.id" class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50">
                                    <td class="px-3 py-2.5">
                                        <div class="font-bold text-zinc-900 dark:text-white">{{ emp.name }}</div>
                                        <div class="font-mono text-[10px] text-zinc-500">{{ emp.employee_code }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-zinc-900 dark:text-white">
                                        {{ formatMoney(calculateEmployeeLine(emp).gross) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-rose-600">
                                        -{{ formatMoney(calculateEmployeeLine(emp).epfEmployee) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-rose-600">
                                        -{{ formatMoney(emp.socso_employee + emp.eis_employee) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-rose-600">
                                        -{{ formatMoney(emp.tax_pcb) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-zinc-600">
                                        {{ formatMoney(emp.loan_deduction + emp.advance_recovery) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                        {{ formatMoney(calculateEmployeeLine(emp).net) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-semibold text-purple-600">
                                        +{{ formatMoney(calculateEmployeeLine(emp).epfEmployer) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- STEP 5: Review & Submit for Approval -->
            <div v-if="currentStep === 5" class="space-y-6">
                <!-- Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <Card class="p-4 space-y-1">
                        <div class="text-xs text-zinc-500 uppercase font-semibold">Total Gross Earnings</div>
                        <div class="text-xl font-black text-zinc-900 dark:text-white font-mono">
                            {{ formatMoney(summaryTotals.grossTotal) }}
                        </div>
                    </Card>

                    <Card class="p-4 space-y-1">
                        <div class="text-xs text-zinc-500 uppercase font-semibold">Total Employee Deductions</div>
                        <div class="text-xl font-black text-rose-600 font-mono">
                            -{{ formatMoney(summaryTotals.deductionsTotal) }}
                        </div>
                    </Card>

                    <Card class="p-4 space-y-1">
                        <div class="text-xs text-zinc-500 uppercase font-semibold">Total Net Payout</div>
                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ formatMoney(summaryTotals.netTotal) }}
                        </div>
                    </Card>

                    <Card class="p-4 space-y-1">
                        <div class="text-xs text-zinc-500 uppercase font-semibold">Cost to Company (CTC)</div>
                        <div class="text-xl font-black text-purple-600 dark:text-purple-400 font-mono">
                            {{ formatMoney(summaryTotals.employerCostTotal) }}
                        </div>
                    </Card>
                </div>

                <Card class="p-6 space-y-4">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Review Payroll Run Summary</h3>
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-1">
                            <div><strong>Run Reference:</strong> {{ runTitle }}</div>
                            <div><strong>Target Group:</strong> {{ selectedGroup.name }}</div>
                            <div><strong>Period:</strong> {{ periodStart }} to {{ periodEnd }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 space-y-1">
                            <div><strong>Disbursement Date:</strong> {{ payDate }}</div>
                            <div><strong>Total Employees:</strong> {{ summaryTotals.headcount }} Staff</div>
                            <div><strong>Payment Method:</strong> Bank Direct Credit / Autopay</div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Bottom Navigation / Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <Button
                    v-if="currentStep > 1"
                    variant="outline"
                    size="sm"
                    @click="handlePrevStep"
                >
                    ← Previous Step
                </Button>
                <div v-else></div>

                <div class="flex items-center space-x-3">
                    <Button
                        v-if="currentStep < 5"
                        variant="primary"
                        size="sm"
                        @click="handleNextStep"
                    >
                        Next Step →
                    </Button>
                    <Button
                        v-else
                        variant="primary"
                        size="sm"
                        @click="handleSubmitRun"
                    >
                        ✓ Create & Submit for Approval
                    </Button>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
