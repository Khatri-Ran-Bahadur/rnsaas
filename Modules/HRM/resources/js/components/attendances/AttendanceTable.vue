<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import HRMStatusBadge from '../common/HRMStatusBadge.vue';
import HRMEmptyState from '../common/HRMEmptyState.vue';
import type { PaginatedAttendances, AttendanceItem } from '../../types/attendances';

defineProps<{
    attendances: PaginatedAttendances;
    perPage?: number;
}>();

const emit = defineEmits<{
    (e: 'update:perPage', val: number): void;
    (e: 'toggleStatus', item: AttendanceItem): void;
    (e: 'inspect', item: AttendanceItem): void;
}>();
</script>

<template>
    <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <!-- Table Head Controls -->
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
            <span class="text-xs font-semibold text-slate-700 dark:text-zinc-300">
                Attendance Records
                <span class="ml-1 text-slate-400 font-normal">({{ attendances?.total ?? attendances?.data?.length ?? 0 }})</span>
            </span>

            <div class="flex items-center gap-2">
                <PerPageSelector
                    :model-value="perPage || 20"
                    @update:model-value="emit('update:perPage', $event)"
                />
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 bg-zinc-50/70 text-slate-500 font-medium dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400">
                        <th class="py-3 pl-5 pr-3 font-semibold">Employee</th>
                        <th class="px-3 py-3 font-semibold">Date</th>
                        <th class="px-3 py-3 font-semibold">Check In</th>
                        <th class="px-3 py-3 font-semibold">Check Out</th>
                        <th class="px-3 py-3 font-semibold">Worked</th>
                        <th class="px-3 py-3 font-semibold">Late / Early</th>
                        <th class="px-3 py-3 font-semibold">Status</th>
                        <th class="px-3 py-3 font-semibold">Source</th>
                        <th class="py-3 pl-3 pr-5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                    <tr
                        v-for="row in (attendances?.data || [])"
                        :key="row.public_id"
                        class="group transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 cursor-pointer"
                        @click="emit('inspect', row)"
                    >
                        <!-- Staff Profile Cell -->
                        <td class="py-3 pl-5 pr-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                    {{ row.staff?.name ? row.staff.name.substring(0, 2).toUpperCase() : 'NA' }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-900 dark:text-zinc-100 truncate">
                                        {{ row.staff?.name || 'Unknown Staff' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 dark:text-zinc-500 truncate">
                                        {{ row.staff?.employee_code || 'EMP-??' }} • {{ row.staff?.department || 'General' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Date -->
                        <td class="px-3 py-3 text-slate-700 dark:text-zinc-300 whitespace-nowrap font-medium">
                            {{ row.attendance_date }}
                        </td>

                        <!-- Check In -->
                        <td class="px-3 py-3 text-slate-600 dark:text-zinc-400 whitespace-nowrap">
                            <span :class="row.check_in ? 'font-medium text-slate-900 dark:text-zinc-100' : 'text-slate-400 italic'">
                                {{ row.check_in || '—' }}
                            </span>
                        </td>

                        <!-- Check Out -->
                        <td class="px-3 py-3 text-slate-600 dark:text-zinc-400 whitespace-nowrap">
                            <span :class="row.check_out ? 'font-medium text-slate-900 dark:text-zinc-100' : 'text-slate-400 italic'">
                                {{ row.check_out || '—' }}
                            </span>
                        </td>

                        <!-- Worked Hours -->
                        <td class="px-3 py-3 text-slate-700 dark:text-zinc-300 whitespace-nowrap font-medium">
                            {{ row.worked_hours > 0 ? `${row.worked_hours.toFixed(1)} hrs` : '—' }}
                        </td>

                        <!-- Late / Early Deviations -->
                        <td class="px-3 py-3 whitespace-nowrap text-[11px]">
                            <div v-if="row.late_minutes > 0" class="text-amber-600 dark:text-amber-400 font-medium">
                                +{{ row.late_minutes }}m late
                            </div>
                            <div v-if="row.early_leave_minutes > 0" class="text-rose-600 dark:text-rose-400 font-medium">
                                -{{ row.early_leave_minutes }}m early
                            </div>
                            <div v-if="!row.late_minutes && !row.early_leave_minutes" class="text-slate-400">
                                On Time
                            </div>
                        </td>

                        <!-- Status Badge -->
                        <td class="px-3 py-3 whitespace-nowrap">
                            <HRMStatusBadge :status="row.status?.value || 'present'" />
                        </td>

                        <!-- Source Badge -->
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 capitalize">
                                {{ row.source?.value || 'web' }}
                            </span>
                        </td>

                        <!-- Actions Dropdown -->
                        <td class="py-3 pl-3 pr-5 text-right whitespace-nowrap" @click.stop>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-zinc-100 hover:text-slate-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>

                                <template #default="{ close }">
                                    <div class="py-1">
                                        <Link
                                            :href="`/admin/hrm/attendances/${row.public_id}`"
                                            class="flex w-full items-center gap-2 px-3.5 py-1.5 text-xs text-slate-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            @click="close"
                                        >
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Details
                                        </Link>

                                        <Link
                                            :href="`/admin/hrm/attendances/${row.public_id}/edit`"
                                            class="flex w-full items-center gap-2 px-3.5 py-1.5 text-xs text-slate-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            @click="close"
                                        >
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </Link>

                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 px-3.5 py-1.5 text-xs text-slate-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            @click="close(); emit('toggleStatus', row)"
                                        >
                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                            {{ row.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </td>
                    </tr>

                    <!-- Empty state row -->
                    <tr v-if="!attendances?.data || attendances.data.length === 0">
                        <td colspan="9" class="p-8">
                            <HRMEmptyState
                                title="No attendance records found"
                                description="No records matched your search or filters. Try adjusting your criteria or add an entry."
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Pagination -->
        <div v-if="(attendances?.total ?? attendances?.data?.length ?? 0) > 0" class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <Pagination :data="attendances" />
        </div>
    </div>
</template>
