<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BarChart from '@/components/charts/BarChart.vue';
import DoughnutChart from '@/components/charts/DoughnutChart.vue';
import type { ChartData } from 'chart.js';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge } from '@/components';

export interface DashboardStats {
    total_payroll_cost: number;
    total_net_pay: number;
    total_statutory_liability: number;
    total_active_employees: number;
    pending_approvals_count: number;
    active_run_status: string;
    currency: string;
    currency_symbol: string;
}

export interface ActiveRun {
    id: number;
    run_number: string;
    period_name: string;
    start_date: string;
    end_date: string;
    pay_date: string;
    status: string;
    payroll_group: string;
    total_employees: number;
    gross_amount: number;
    deductions_amount: number;
    net_amount: number;
    employer_statutory: number;
    cutoff_attendance: string;
    cutoff_overtime: string;
    approval_deadline: string;
}

export interface RecentRun {
    id: number;
    run_number: string;
    period_name: string;
    payroll_group: string;
    pay_date: string;
    total_employees: number;
    net_amount: number;
    status: string;
}

export interface DeadlineItem {
    event: string;
    date: string;
    group: string;
    type: 'cutoff' | 'approval' | 'payout' | 'compliance';
}

export interface DeptItem {
    department: string;
    headcount: number;
    amount: number;
    pct: number;
}

const props = defineProps<{
    stats: DashboardStats;
    activeRun: ActiveRun | null;
    recentRuns: RecentRun[];
    upcomingDeadlines: DeadlineItem[];
    departmentBreakdown: DeptItem[];
}>();

const formatMoney = (val: number) => {
    return (props.stats?.currency_symbol || 'RM') + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const deptCostChartData = computed<ChartData<'doughnut'>>(() => {
    const list = props.departmentBreakdown && props.departmentBreakdown.length > 0
        ? props.departmentBreakdown
        : [
            { department: 'Operations & Plant', amount: 32000 },
            { department: 'Sales & Marketing', amount: 18000 },
            { department: 'Engineering & IT', amount: 24000 },
            { department: 'Administration & HR', amount: 12000 },
        ];
    return {
        labels: list.map(d => d.department),
        datasets: [
            {
                data: list.map(d => d.amount),
                backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'],
                borderWidth: 0,
            }
        ]
    };
});

const payrollTrendChartData = computed<ChartData<'bar'>>(() => {
    const runs = props.recentRuns && props.recentRuns.length > 0
        ? props.recentRuns
        : [
            { period_name: 'May 2026', net_amount: 68000 },
            { period_name: 'Jun 2026', net_amount: 72000 },
            { period_name: 'Jul 2026', net_amount: 74500 },
            { period_name: 'Aug 2026', net_amount: 76000 },
        ];
    return {
        labels: runs.map(r => r.period_name),
        datasets: [
            {
                label: 'Disbursed Net Pay',
                data: runs.map(r => r.net_amount),
                backgroundColor: '#10b981',
                borderRadius: 6,
            }
        ]
    };
});

</script>

<template>
    <Head title="Payroll Cockpit & Executive Overview - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <span>Organization</span>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Universal Payroll Engine</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Payroll Operations & Cockpit</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Manage multi-group pay runs, statutory liabilities (EPF, SOCSO, EIS, PCB), employee loans, and bank direct credit.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin/payroll/runs"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 transition"
                    >
                        Payroll Runs History
                    </Link>
                    <Link
                        href="/admin/payroll/runs/create"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2 cursor-pointer"
                    >
                        <span>+ Run New Payroll Wizard</span>
                    </Link>
                </div>
            </div>

            <!-- Top Metric KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="p-5">
                    <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                        <span class="font-semibold uppercase tracking-wider">Total Monthly Payroll Cost</span>
                        <span class="text-emerald-600 font-bold bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded-full">September</span>
                    </div>
                    <div class="text-2xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ formatMoney(stats.total_payroll_cost) }}
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 flex items-center gap-1.5">
                        <span>Includes Gross Pay + Employer Statutory</span>
                    </div>
                </Card>

                <Card class="p-5">
                    <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                        <span class="font-semibold uppercase tracking-wider">Total Net Pay to Employees</span>
                        <span class="text-blue-600 font-bold bg-blue-50 dark:bg-blue-950/50 px-2 py-0.5 rounded-full">Bank Payout</span>
                    </div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">
                        {{ formatMoney(stats.total_net_pay) }}
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 flex items-center gap-1.5">
                        <span>Direct Credit to {{ stats.total_active_employees }} staff accounts</span>
                    </div>
                </Card>

                <Card class="p-5">
                    <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                        <span class="font-semibold uppercase tracking-wider">Statutory Liabilities</span>
                        <span class="text-purple-600 font-bold bg-purple-50 dark:bg-purple-950/50 px-2 py-0.5 rounded-full">Due 15th</span>
                    </div>
                    <div class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">
                        {{ formatMoney(stats.total_statutory_liability) }}
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 flex items-center gap-1.5">
                        <span>EPF (KWSP), SOCSO, EIS & PCB Tax</span>
                    </div>
                </Card>

                <Card class="p-5">
                    <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                        <span class="font-semibold uppercase tracking-wider">Active Employees</span>
                        <span class="text-zinc-600 font-bold bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-full">3 Groups</span>
                    </div>
                    <div class="text-2xl font-black text-zinc-900 dark:text-white font-mono">
                        {{ stats.total_active_employees }} <span class="text-xs font-medium text-zinc-400">Headcount</span>
                    </div>
                    <div class="mt-2 text-[11px] text-zinc-500 flex items-center gap-1.5">
                        <span v-if="stats.pending_approvals_count > 0" class="text-amber-600 font-semibold">
                            ⚠️ {{ stats.pending_approvals_count }} runs pending approval
                        </span>
                        <span v-else class="text-emerald-600 font-semibold">All payrolls up-to-date</span>
                    </div>
                </Card>
            </div>

            <!-- Active / Ongoing Payroll Run Banner (If in progress) -->
            <Card v-if="activeRun" class="p-6 border-l-4 border-emerald-500 bg-gradient-to-r from-emerald-50/40 via-white to-slate-50 dark:from-emerald-950/20 dark:via-zinc-900 dark:to-zinc-900">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950 px-2 py-0.5 rounded-md">
                                ACTIVE RUN: {{ activeRun.run_number }}
                            </span>
                            <Badge variant="warning" class="capitalize">{{ activeRun.status }}</Badge>
                        </div>
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                            {{ activeRun.period_name }} ({{ activeRun.payroll_group }})
                        </h2>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-600 dark:text-zinc-400 pt-1">
                            <div>📅 Pay Date: <strong class="text-zinc-800 dark:text-zinc-200">{{ activeRun.pay_date }}</strong></div>
                            <div>👥 Headcount: <strong class="text-zinc-800 dark:text-zinc-200">{{ activeRun.total_employees }} Staff</strong></div>
                            <div>💰 Net Pay: <strong class="text-emerald-600 font-mono">{{ formatMoney(activeRun.net_amount) }}</strong></div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <Link
                            :href="`/admin/payroll/runs/${activeRun.id}`"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md transition flex items-center gap-2 cursor-pointer"
                        >
                            <span>Review & Finalize Run</span>
                            <span>→</span>
                        </Link>
                    </div>
                </div>
            </Card>

            <!-- Middle Split: Left (Upcoming Deadlines & Compliance), Right (Department Payroll Distribution) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Pay Calendar & Compliance Deadlines (6 cols) -->
                <div class="lg:col-span-6 space-y-4">
                    <Card class="p-5 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                <span>📅 Upcoming Cutoffs & Pay Calendar</span>
                            </h3>
                            <Link href="/admin/payroll/groups" class="text-xs text-emerald-600 hover:underline">Manage Calendars</Link>
                        </div>

                        <div class="space-y-2.5">
                            <div
                                v-for="(item, idx) in upcomingDeadlines"
                                :key="idx"
                                class="p-3 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-xs"
                            >
                                <div class="space-y-0.5">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ item.event }}</div>
                                    <div class="text-[11px] text-zinc-500">{{ item.group }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono font-bold text-zinc-800 dark:text-zinc-200">{{ item.date }}</div>
                                    <span
                                        :class="[
                                            'text-[10px] uppercase font-bold px-1.5 py-0.5 rounded',
                                            item.type === 'payout' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' :
                                            item.type === 'approval' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' :
                                            item.type === 'compliance' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' :
                                            'bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300'
                                        ]"
                                    >
                                        {{ item.type }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Right: Department Cost Distribution (6 cols) -->
                <div class="lg:col-span-6 space-y-4">
                    <Card class="p-5 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                <span>📊 Department Payroll Allocation</span>
                            </h3>
                            <Link href="/admin/payroll/reports" class="text-xs text-emerald-600 hover:underline">View Reports</Link>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(dept, idx) in departmentBreakdown"
                                :key="idx"
                                class="space-y-1 text-xs"
                            >
                                <div class="flex justify-between font-semibold">
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ dept.department }} ({{ dept.headcount }} staff)</span>
                                    <span class="font-mono text-zinc-900 dark:text-white">{{ formatMoney(dept.amount) }} ({{ dept.pct }}%)</span>
                                </div>
                                <!-- Progress Bar -->
                                <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full transition-all" :style="{ width: `${dept.pct}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Recent Completed Runs Table -->
            <Card class="overflow-hidden">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Recent Payroll Runs</h3>
                    <Link href="/admin/payroll/runs" class="text-xs text-emerald-600 hover:underline">View All Runs</Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Run Ref & Period</th>
                                <th class="px-4 py-3">Payroll Group</th>
                                <th class="px-4 py-3 text-center">Headcount</th>
                                <th class="px-4 py-3 text-right">Net Payout</th>
                                <th class="px-4 py-3 text-center">Pay Date</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="run in recentRuns"
                                :key="run.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-mono font-bold text-zinc-900 dark:text-white">{{ run.run_number }}</div>
                                    <div class="text-[11px] text-zinc-500">{{ run.period_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 font-medium">
                                    {{ run.payroll_group }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ run.total_employees }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatMoney(run.net_amount) }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-zinc-600 dark:text-zinc-400">
                                    {{ run.pay_date }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="run.status === 'paid' ? 'success' : 'secondary'" class="uppercase text-[9px]">
                                        {{ run.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <Link
                                        :href="`/admin/payroll/runs/${run.id}`"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-emerald-50 dark:hover:bg-zinc-700 text-zinc-700 hover:text-emerald-700 dark:text-zinc-300 transition"
                                    >
                                        View Sheet
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
