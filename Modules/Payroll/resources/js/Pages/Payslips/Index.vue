<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface PayslipSummary {
    id: number;
    slip_number: string;
    employee_id: number;
    employee_code: string;
    employee_name: string;
    department: string;
    designation: string;
    period_name: string;
    pay_date: string;
    gross_pay: number;
    deductions_total: number;
    net_pay: number;
    currency: string;
    payment_status: 'pending' | 'paid';
    payment_method: string;
}

const props = defineProps<{
    payslips: {
        data: PayslipSummary[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        search: string;
        department: string;
        period: string;
    };
}>();

const search = ref(props.filters?.search || '');
const departmentFilter = ref(props.filters?.department || '');

const handleFilter = () => {
    router.get('/admin/payroll/payslips', {
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
    <Head title="Employee Payslips - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Payslips</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Employee Payslips Directory</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        View, export, print, and distribute official salary slips with complete statutory breakdown and YTD accumulators.
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
                            placeholder="Search employee name, code, slip #..."
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
                            <option value="Software Engineering">Software Engineering</option>
                            <option value="Accounting & Finance">Accounting & Finance</option>
                            <option value="Sales & Business Dev">Sales & Business Dev</option>
                            <option value="Operations & Logistics">Operations & Logistics</option>
                        </select>
                    </div>
                </div>
            </Card>

            <!-- Payslips Table -->
            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5">Payslip # & Period</th>
                                <th class="px-4 py-3.5">Employee</th>
                                <th class="px-4 py-3.5">Department</th>
                                <th class="px-4 py-3.5 text-right">Gross Pay</th>
                                <th class="px-4 py-3.5 text-right">Deductions</th>
                                <th class="px-4 py-3.5 text-right font-bold">Net Salary</th>
                                <th class="px-4 py-3.5">Payment Method</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="slip in payslips.data"
                                :key="slip.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="font-mono font-bold text-zinc-900 dark:text-white">{{ slip.slip_number }}</div>
                                    <div class="text-[11px] text-zinc-500 font-medium">{{ slip.period_name }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ slip.employee_name }}</div>
                                    <div class="text-[10px] text-zinc-500 font-mono">{{ slip.employee_code }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-zinc-700 dark:text-zinc-300">
                                    <div>{{ slip.department }}</div>
                                    <div class="text-[10px] text-zinc-500">{{ slip.designation }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ formatMoney(slip.gross_pay) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-rose-600">
                                    -{{ formatMoney(slip.deductions_total) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    {{ formatMoney(slip.net_pay) }}
                                </td>
                                <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-400 text-[11px]">
                                    {{ slip.payment_method }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <Badge :variant="slip.payment_status === 'paid' ? 'success' : 'secondary'" class="uppercase text-[9px]">
                                        {{ slip.payment_status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <Link
                                        :href="`/admin/payroll/payslips/${slip.employee_id}`"
                                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 transition inline-flex items-center gap-1.5"
                                    >
                                        <span>📄 View Slip</span>
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
