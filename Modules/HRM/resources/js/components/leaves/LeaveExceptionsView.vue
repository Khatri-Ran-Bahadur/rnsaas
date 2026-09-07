<script setup lang="ts">
import { ref } from 'vue';
import HRMStatusBadge from '../common/HRMStatusBadge.vue';
import HRMEmptyState from '../common/HRMEmptyState.vue';
import type { LeaveExceptionItem } from '../../types/leaves';

// TODO BACKEND CONTRACT: Leave exceptions policy checker
const mockExceptions = ref<LeaveExceptionItem[]>([
    {
        id: 'LEX-101',
        employee_name: 'Aarav Sharma',
        employee_code: 'EMP-0012',
        exception_type: 'Approval Overdue (>48h)',
        details: 'Leave submitted 3 days ago without manager review.',
        severity: 'high',
        status: 'open',
        detected_at: '2026-09-07 08:00',
    },
    {
        id: 'LEX-102',
        employee_name: 'Pooja Thapa',
        employee_code: 'EMP-0034',
        exception_type: 'Concurrent Team Leave',
        details: '3 of 4 engineers in Engineering team are requested off on 2026-09-15.',
        severity: 'critical',
        status: 'under_review',
        detected_at: '2026-09-06 14:20',
    },
]);

const resolve = (exc: LeaveExceptionItem) => {
    alert(`Resolved leave alert ${exc.id}`);
    exc.status = 'resolved';
};
</script>

<template>
    <div class="space-y-5">
        <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
            <div class="border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-800">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900 dark:text-white">
                    Leave Policy Warnings & Anomalies
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/80 text-slate-600 font-semibold dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-300">
                            <th class="py-3 pl-5 pr-3">Employee</th>
                            <th class="px-3 py-3">Exception</th>
                            <th class="px-3 py-3">Details</th>
                            <th class="px-3 py-3">Severity</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="py-3 pl-3 pr-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/70">
                        <tr v-for="exc in mockExceptions" :key="exc.id" class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                            <td class="py-3.5 pl-5 pr-3 font-medium text-slate-900 dark:text-white">
                                <div>{{ exc.employee_name }}</div>
                                <span class="text-[11px] font-mono text-slate-400">#{{ exc.employee_code }}</span>
                            </td>
                            <td class="px-3 py-3.5 font-semibold text-slate-900 dark:text-white">
                                {{ exc.exception_type }}
                            </td>
                            <td class="px-3 py-3.5 text-slate-600 dark:text-zinc-400 max-w-sm">
                                {{ exc.details }}
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
                                    class="rounded bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300"
                                    @click="resolve(exc)"
                                >
                                    Resolve
                                </button>
                                <span v-else class="text-[11px] text-slate-400">Resolved</span>
                            </td>
                        </tr>

                        <tr v-if="mockExceptions.length === 0">
                            <td colspan="6" class="p-8">
                                <HRMEmptyState
                                    title="No leave exceptions"
                                    description="All leave requests satisfy scheduling and attendance policies."
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
