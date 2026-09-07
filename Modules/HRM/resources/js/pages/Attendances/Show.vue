<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import HRMStatusBadge from '../../components/common/HRMStatusBadge.vue';
import type { AttendanceItem } from '../../types/attendances';

const props = defineProps<{
    attendance: {
        data: AttendanceItem;
    };
}>();

const record = computed(() => props.attendance.data);

const toggleStatus = () => {
    router.patch(`/admin/hrm/attendances/${record.value.public_id}/toggle-status`);
};
</script>

<template>
    <OrganizationLayout
        :title="`Attendance - ${record.staff?.name || 'Staff'}`"
        :breadcrumbs="[
            { label: 'HRM' },
            { label: 'Attendance', href: '/admin/hrm/attendances' },
            { label: record.attendance_date },
        ]"
    >
        <Head :title="`Attendance - ${record.staff?.name || 'Staff'}`" />

        <div class="w-full space-y-6">
            <HRMPageHeader
                :title="`${record.staff?.name || 'Staff'} - Attendance`"
                :subtitle="`Date: ${record.attendance_date}`"
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Attendance', href: '/admin/hrm/attendances' },
                    { label: record.attendance_date },
                ]"
            >
                <template #actions>
                    <Link
                        :href="`/admin/hrm/attendances/${record.public_id}/edit`"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Record
                    </Link>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                        @click="toggleStatus"
                    >
                        {{ record.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </template>
            </HRMPageHeader>

            <div class="space-y-6">
                <!-- Employee Summary Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 text-lg">
                            {{ (record.staff?.name || 'S').charAt(0) }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ record.staff?.name }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 font-mono">
                                Employee Code: #{{ record.staff?.employee_code || '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <HRMStatusBadge :status="record.status?.value" :label="record.status?.label" size="md" />
                        <span
                            :class="[
                                'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                record.is_active
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                            ]"
                        >
                            {{ record.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <!-- Timing & Duration Metric Grid -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Check In</span>
                        <p class="mt-1 text-lg font-mono font-bold text-slate-900 dark:text-white">
                            {{ record.check_in || '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Check Out</span>
                        <p class="mt-1 text-lg font-mono font-bold text-slate-900 dark:text-white">
                            {{ record.check_out || '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Worked Hours</span>
                        <p class="mt-1 text-lg font-bold text-indigo-600 dark:text-indigo-400">
                            {{ record.worked_hours }}h
                        </p>
                        <span class="text-[10px] text-slate-400">({{ record.worked_minutes }} minutes)</span>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Punch Source</span>
                        <p class="mt-1 text-sm font-semibold uppercase tracking-wider text-slate-800 dark:text-zinc-200">
                            {{ record.source?.label }}
                        </p>
                    </div>
                </div>

                <!-- Exceptions and Policy Flags -->
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-3">
                        Policy & Overtime Indicators
                    </h4>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs">
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500">Late Arrival:</span>
                            <span class="ml-1.5 font-bold text-amber-600">{{ record.late_minutes }} minutes</span>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500">Early Departure:</span>
                            <span class="ml-1.5 font-bold text-rose-600">{{ record.early_leave_minutes }} minutes</span>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500">Overtime Recorded:</span>
                            <span class="ml-1.5 font-bold text-emerald-600">{{ record.overtime_minutes }} minutes</span>
                        </div>
                    </div>
                </div>

                <!-- Notes Card -->
                <div v-if="record.notes" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-2">
                        Notes & Remarks
                    </h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 whitespace-pre-line">
                        {{ record.notes }}
                    </p>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
