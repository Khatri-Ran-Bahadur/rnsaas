<script setup lang="ts">
import { ref } from 'vue';
import HRMStatusBadge from '../common/HRMStatusBadge.vue';
import HRMEmptyState from '../common/HRMEmptyState.vue';
import type { AttendanceExceptionItem } from '../../types/attendances';

// TODO BACKEND CONTRACT: Exception detection engine
const mockExceptions = ref<AttendanceExceptionItem[]>([
    {
        id: 'EXC-201',
        employee_name: 'Aarav Sharma',
        employee_code: 'EMP-0012',
        date: '2026-09-07',
        exception_type: 'Missing Check Out',
        details: 'Checked in at 09:00 AM, but no checkout recorded after 10 hours.',
        severity: 'high',
        status: 'open',
        detected_at: '2026-09-07 19:30',
    },
    {
        id: 'EXC-202',
        employee_name: 'Pooja Thapa',
        employee_code: 'EMP-0034',
        date: '2026-09-07',
        exception_type: 'Attendance During Leave',
        details: 'Employee punched biometric in while on approved Annual Leave.',
        severity: 'critical',
        status: 'open',
        detected_at: '2026-09-07 09:05',
    },
    {
        id: 'EXC-203',
        employee_name: 'Bikash Rana',
        employee_code: 'EMP-0045',
        date: '2026-09-06',
        exception_type: 'Late Arrival (>45m)',
        details: 'Checked in at 09:50 AM against scheduled 09:00 AM shift start.',
        severity: 'medium',
        status: 'under_review',
        detected_at: '2026-09-06 09:51',
    },
]);

const resolveException = (exc: AttendanceExceptionItem) => {
    alert(`Marked exception ${exc.id} as Resolved`);
    exc.status = 'resolved';
};

const ignoreException = (exc: AttendanceExceptionItem) => {
    alert(`Ignored exception ${exc.id}`);
    exc.status = 'ignored';
};
</script>

<template>
    <div class="space-y-5">
        <!-- Summary Severity Cards -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 dark:border-rose-900/60 dark:bg-rose-950/20">
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400">Critical Alerts</span>
                <p class="mt-1 text-2xl font-bold text-rose-800 dark:text-rose-200">1</p>
                <span class="text-[11px] text-rose-600/80">Requires immediate HR action</span>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">High Severity</span>
                <p class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-200">1</p>
                <span class="text-[11px] text-amber-600/80">Missing punch or mismatch</span>
            </div>

            <div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/20">
                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-400">Medium Severity</span>
                <p class="mt-1 text-2xl font-bold text-indigo-800 dark:text-indigo-200">1</p>
                <span class="text-[11px] text-indigo-600/80">Late arrivals / early departures</span>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Resolved Today</span>
                <p class="mt-1 text-2xl font-bold text-emerald-800 dark:text-emerald-200">4</p>
                <span class="text-[11px] text-emerald-600/80">Closed by manager</span>
            </div>
        </div>

        <!-- Exceptions Table -->
        <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
            <div class="border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-800">
                <h3 class="text-xs font-semibold text-slate-900 dark:text-white uppercase tracking-wider">
                    Flagged Anomalies
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/80 text-slate-600 font-semibold dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-300">
                            <th class="py-3 pl-5 pr-3">Employee</th>
                            <th class="px-3 py-3">Date</th>
                            <th class="px-3 py-3">Exception Type</th>
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
                            <td class="px-3 py-3.5 whitespace-nowrap text-slate-700 dark:text-zinc-300">
                                {{ exc.date }}
                            </td>
                            <td class="px-3 py-3.5 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ exc.exception_type }}
                            </td>
                            <td class="px-3 py-3.5 text-slate-600 dark:text-zinc-400 max-w-xs truncate">
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
                                <div v-if="exc.status === 'open' || exc.status === 'under_review'" class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        class="rounded bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300 transition-colors"
                                        @click="resolveException(exc)"
                                    >
                                        Resolve
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 transition-colors"
                                        @click="ignoreException(exc)"
                                    >
                                        Ignore
                                    </button>
                                </div>
                                <span v-else class="text-[11px] text-slate-400">Closed</span>
                            </td>
                        </tr>

                        <tr v-if="mockExceptions.length === 0">
                            <td colspan="7" class="p-8">
                                <HRMEmptyState
                                    title="No attendance exceptions"
                                    description="No anomalies or policy violations detected."
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
