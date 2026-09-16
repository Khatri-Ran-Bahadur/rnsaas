<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button } from '@/components';

export interface RunItem {
    id: number;
    run_number: string;
    period_name: string;
    start_date: string;
    end_date: string;
    pay_date: string;
    status: 'draft' | 'processing' | 'calculated' | 'pending_approval' | 'approved' | 'finalized' | 'paid' | 'cancelled';
    payroll_group: string;
    total_employees: number;
    gross_amount: number;
    deductions_amount: number;
    net_amount: number;
    employer_statutory: number;
    created_by: string;
    approved_by?: string | null;
    created_at: string;
}

export interface GroupOption {
    id: number;
    name: string;
}

const props = defineProps<{
    runs: {
        data: RunItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        search: string;
        status: string;
        group: string;
    };
    groups: GroupOption[];
}>();

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const groupFilter = ref(props.filters?.group || '');

const handleFilter = () => {
    router.get('/admin/payroll/runs', {
        search: search.value,
        status: statusFilter.value,
        group: groupFilter.value,
    }, { preserveState: true, replace: true });
};

import { useCurrency } from '@/composables/useCurrency';

const { currencySymbol } = useCurrency();

const formatMoney = (val: number) => {
    return currencySymbol.value + ' ' + (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getStatusVariant = (st: string) => {
    switch (st) {
        case 'paid': return 'success';
        case 'approved':
        case 'finalized': return 'success';
        case 'calculated':
        case 'pending_approval': return 'warning';
        case 'processing': return 'secondary';
        case 'draft': return 'secondary';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};
</script>

<template>
    <Head title="Payroll Runs & Batches - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/payroll" class="hover:text-emerald-600 transition">Payroll</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Payroll Runs</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Payroll Runs & Execution Cycles</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Manage periodic payroll batches, calculate statutory obligations, approve salary disbursements, and export bank giro files.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin/payroll/runs/create"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2 cursor-pointer"
                    >
                        <span>+ Run New Payroll Wizard</span>
                    </Link>
                </div>
            </div>

            <!-- Filters Bar -->
            <Card class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by Run #, Period name..."
                            @input="handleFilter"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs text-zinc-800 dark:text-white placeholder-zinc-400 focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <select
                            v-model="statusFilter"
                            @change="handleFilter"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="calculated">Calculated</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="approved">Approved</option>
                            <option value="finalized">Finalized</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div>
                        <select
                            v-model="groupFilter"
                            @change="handleFilter"
                            class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl text-xs text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">All Payroll Groups</option>
                            <option v-for="grp in groups" :key="grp.id" :value="grp.name">{{ grp.name }}</option>
                        </select>
                    </div>
                </div>
            </Card>

            <!-- Payroll Runs Table -->
            <Card class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5">Run Reference & Period</th>
                                <th class="px-4 py-3.5">Payroll Group</th>
                                <th class="px-4 py-3.5 text-center">Headcount</th>
                                <th class="px-4 py-3.5 text-right">Gross Amount</th>
                                <th class="px-4 py-3.5 text-right">Deductions</th>
                                <th class="px-4 py-3.5 text-right">Net Payout</th>
                                <th class="px-4 py-3.5 text-center">Pay Date</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="run in runs.data"
                                :key="run.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="font-mono font-bold text-zinc-900 dark:text-white">{{ run.run_number }}</div>
                                    <div class="text-[11px] text-zinc-500 font-medium">{{ run.period_name }}</div>
                                    <div class="text-[10px] text-zinc-400 font-mono">{{ run.start_date }} ~ {{ run.end_date }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-zinc-700 dark:text-zinc-300 font-medium">
                                    {{ run.payroll_group }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ run.total_employees }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ formatMoney(run.gross_amount) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-rose-600 dark:text-rose-400">
                                    -{{ formatMoney(run.deductions_amount) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatMoney(run.net_amount) }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-mono text-zinc-600 dark:text-zinc-400 font-semibold">
                                    {{ run.pay_date }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <Badge :variant="getStatusVariant(run.status)" class="uppercase text-[9px]">
                                        {{ run.status.replace('_', ' ') }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3.5 text-right space-x-2">
                                    <Link
                                        :href="`/admin/payroll/runs/${run.id}`"
                                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 transition"
                                    >
                                        View Sheet →
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
