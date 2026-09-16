<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge } from '@/components';

export interface EmployeeProfileRow {
    id: number;
    name: string;
    employee_code: string;
    department: string;
    designation: string;
    employment_type: string;
    payroll_group: string;
    base_salary: number;
    total_allowances: number;
    monthly_gross: number;
    payment_method: string;
    status: string;
    last_revision_date: string;
}

const props = defineProps<{
    employees: {
        data: EmployeeProfileRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        search: string;
        department: string;
        type: string;
    };
    departments: string[];
}>();

const search = ref(props.filters?.search || '');
const departmentFilter = ref(props.filters?.department || '');

const handleFilter = () => {
    router.get('/admin/payroll/employees', {
        search: search.value,
        department: departmentFilter.value,
    }, { preserveState: true, replace: true });
};

import { useCurrency } from '@/composables/useCurrency';

const { currencySymbol } = useCurrency();

const formatMoney = (val: number) => {
    return currencySymbol.value + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Employee Compensation Profiles - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Employee Profiles</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Employee Compensation & Salary Profiles</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Manage base pay rates, allowances packages, bank payout methods, and view complete chronological salary revision timelines.
                    </p>
                </div>
            </div>

            <!-- Filters Bar -->
            <Card class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search employee name, code..."
                            @input="handleFilter"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs text-zinc-800 dark:text-white placeholder-zinc-400 focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <select
                            v-model="departmentFilter"
                            @change="handleFilter"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">All Departments</option>
                            <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>
                </div>
            </Card>

            <!-- Employee Profiles Table -->
            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5">Employee Name & Code</th>
                                <th class="px-4 py-3.5">Department & Role</th>
                                <th class="px-4 py-3.5">Payroll Group</th>
                                <th class="px-4 py-3.5 text-right">Base Salary</th>
                                <th class="px-4 py-3.5 text-right">Allowances</th>
                                <th class="px-4 py-3.5 text-right font-bold">Monthly Gross</th>
                                <th class="px-4 py-3.5">Last Revision</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="emp in employees.data"
                                :key="emp.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ emp.name }}</div>
                                    <div class="text-[10px] text-zinc-500 font-mono">{{ emp.employee_code }} • {{ emp.employment_type.replace('_', ' ') }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-zinc-700 dark:text-zinc-300">
                                    <div class="font-medium">{{ emp.department }}</div>
                                    <div class="text-[10px] text-zinc-500">{{ emp.designation }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-zinc-700 dark:text-zinc-300 font-medium">
                                    {{ emp.payroll_group }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono font-bold text-zinc-900 dark:text-white">
                                    {{ formatMoney(emp.base_salary) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                    {{ formatMoney(emp.total_allowances) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    {{ formatMoney(emp.monthly_gross) }}
                                </td>
                                <td class="px-4 py-3.5 text-zinc-500 text-[11px] font-mono">
                                    {{ emp.last_revision_date }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <Badge variant="success" class="uppercase text-[9px]">{{ emp.status }}</Badge>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <Link
                                        :href="`/admin/payroll/employees/${emp.id}`"
                                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 transition inline-flex items-center gap-1.5"
                                    >
                                        <span>Compensation & History →</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </OrganizationLayout>
</template>
