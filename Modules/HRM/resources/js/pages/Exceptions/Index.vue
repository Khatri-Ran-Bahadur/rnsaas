<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import HRMStatusBadge from '../../components/common/HRMStatusBadge.vue';
import HRMEmptyState from '../../components/common/HRMEmptyState.vue';

type ExceptionDomain = 'attendance' | 'leave' | 'overtime' | 'employee';
const activeDomain = ref<ExceptionDomain>('attendance');

interface UnifiedException {
    id: string;
    domain: ExceptionDomain;
    employee_name: string;
    employee_code: string;
    exception_title: string;
    description: string;
    severity: 'critical' | 'high' | 'medium' | 'low';
    status: 'open' | 'under_review' | 'resolved';
    detected_at: string;
}

const exceptions = ref<UnifiedException[]>([
    {
        id: 'EXC-101',
        domain: 'attendance',
        employee_name: 'Aarav Sharma',
        employee_code: 'EMP-0012',
        exception_title: 'Missing Check Out',
        description: 'Punched in at 09:00 AM, but no checkout recorded after 10 hours.',
        severity: 'high',
        status: 'open',
        detected_at: '2026-09-07 19:30',
    },
    {
        id: 'EXC-102',
        domain: 'attendance',
        employee_name: 'Pooja Thapa',
        employee_code: 'EMP-0034',
        exception_title: 'Attendance During Leave',
        description: 'Biometric fingerprint punched while on approved Annual Leave.',
        severity: 'critical',
        status: 'open',
        detected_at: '2026-09-07 09:05',
    },
    {
        id: 'EXC-103',
        domain: 'leave',
        employee_name: 'Bikash Rana',
        employee_code: 'EMP-0045',
        exception_title: 'Approval Overdue (>48h)',
        description: 'Leave application submitted 3 days ago without manager review.',
        severity: 'medium',
        status: 'under_review',
        detected_at: '2026-09-06 14:00',
    },
    {
        id: 'EXC-104',
        domain: 'overtime',
        employee_name: 'Aarav Sharma',
        employee_code: 'EMP-0012',
        exception_title: 'Excessive Overtime (>4h)',
        description: 'Claimed 5.5 hours overtime exceeding daily threshold.',
        severity: 'medium',
        status: 'open',
        detected_at: '2026-09-06 22:30',
    },
]);

const resolve = (exc: UnifiedException) => {
    alert(`Marked exception ${exc.id} as resolved`);
    exc.status = 'resolved';
};
</script>

<template>
    <Head title="HR Exception Center" />

    <OrganizationLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <HRMPageHeader
                title="HR Exception Center"
                subtitle="Centralized triage for policy violations, missing punches, approval bottlenecks, and data anomalies."
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Exceptions' },
                ]"
            />

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs font-semibold text-slate-500 dark:text-zinc-400">Total Open</span>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">3</p>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50/60 p-4 shadow-xs dark:border-rose-900/60 dark:bg-rose-950/20">
                    <span class="text-xs font-semibold text-rose-700 dark:text-rose-400">Critical</span>
                    <p class="mt-1 text-2xl font-bold text-rose-800 dark:text-rose-200">1</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 shadow-xs dark:border-amber-900/60 dark:bg-amber-950/20">
                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">Under Review</span>
                    <p class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">1</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 shadow-xs dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Resolved Today</span>
                    <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">6</p>
                </div>
            </div>

            <!-- Domain Tabs -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-800 gap-6 overflow-x-auto text-xs font-semibold">
                <button
                    v-for="d in [
                        { key: 'attendance', label: 'Attendance Exceptions' },
                        { key: 'leave', label: 'Leave Exceptions' },
                        { key: 'overtime', label: 'Overtime Exceptions' },
                        { key: 'employee', label: 'Employee Compliance' },
                    ] as const"
                    :key="d.key"
                    type="button"
                    :class="[
                        'pb-3 whitespace-nowrap transition-colors border-b-2 capitalize',
                        activeDomain === d.key
                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                            : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200',
                    ]"
                    @click="activeDomain = d.key"
                >
                    {{ d.label }}
                </button>
            </div>

            <!-- Exceptions Table -->
            <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50/80 text-slate-600 font-semibold dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-300">
                                <th class="py-3 pl-5 pr-3">Employee</th>
                                <th class="px-3 py-3">Exception Title</th>
                                <th class="px-3 py-3">Details</th>
                                <th class="px-3 py-3">Severity</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="py-3 pl-3 pr-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                            <tr
                                v-for="exc in exceptions.filter((e) => e.domain === activeDomain)"
                                :key="exc.id"
                                class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40"
                            >
                                <td class="py-3.5 pl-5 pr-3 font-medium text-slate-900 dark:text-white">
                                    <div>{{ exc.employee_name }}</div>
                                    <span class="text-[11px] font-mono text-slate-400">#{{ exc.employee_code }}</span>
                                </td>
                                <td class="px-3 py-3.5 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ exc.exception_title }}
                                </td>
                                <td class="px-3 py-3.5 text-slate-600 dark:text-zinc-400 max-w-sm">
                                    {{ exc.description }}
                                </td>
                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    <HRMStatusBadge :status="exc.severity" />
                                </td>
                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ exc.status }}
                                    </span>
                                </td>
                                <td class="py-3.5 pl-3 pr-5 text-right whitespace-nowrap">
                                    <button
                                        v-if="exc.status !== 'resolved'"
                                        type="button"
                                        class="rounded bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300 transition-colors"
                                        @click="resolve(exc)"
                                    >
                                        Mark Resolved
                                    </button>
                                    <span v-else class="text-[11px] text-slate-400">Resolved</span>
                                </td>
                            </tr>

                            <tr v-if="exceptions.filter((e) => e.domain === activeDomain).length === 0">
                                <td colspan="6" class="p-8">
                                    <HRMEmptyState
                                        title="No exceptions found"
                                        description="There are no policy violations or anomalies currently flagged in this domain."
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
