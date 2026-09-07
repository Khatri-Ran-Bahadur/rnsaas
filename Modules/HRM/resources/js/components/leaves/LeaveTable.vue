<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import HRMStatusBadge from '../common/HRMStatusBadge.vue';
import HRMEmptyState from '../common/HRMEmptyState.vue';
import type { PaginatedLeaves, LeaveItem } from '../../types/leaves';

defineProps<{
    leaves: PaginatedLeaves;
    perPage?: number;
}>();

const emit = defineEmits<{
    (e: 'update:perPage', val: number): void;
    (e: 'approve', item: LeaveItem): void;
    (e: 'reject', item: LeaveItem): void;
    (e: 'toggleStatus', item: LeaveItem): void;
    (e: 'inspect', item: LeaveItem): void;
}>();
</script>

<template>
    <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <!-- Table Controls -->
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
            <span class="text-xs font-semibold text-slate-700 dark:text-zinc-300">
                Leave Requests
                <span class="ml-1 text-slate-400 font-normal">({{ leaves?.total ?? leaves?.data?.length ?? 0 }})</span>
            </span>

            <div class="flex items-center gap-2">
                <PerPageSelector
                    :model-value="perPage || 20"
                    @update:model-value="emit('update:perPage', $event)"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200/80 bg-zinc-50/70 text-slate-500 font-medium dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400">
                        <th class="py-3 pl-5 pr-3 font-semibold">Employee</th>
                        <th class="px-3 py-3 font-semibold">Leave Type</th>
                        <th class="px-3 py-3 font-semibold">Dates</th>
                        <th class="px-3 py-3 font-semibold">Duration</th>
                        <th class="px-3 py-3 font-semibold">Reason</th>
                        <th class="px-3 py-3 font-semibold">Status</th>
                        <th class="py-3 pl-3 pr-5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                    <tr
                        v-for="row in (leaves?.data || [])"
                        :key="row.public_id"
                        class="group transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 cursor-pointer"
                        @click="emit('inspect', row)"
                    >
                        <!-- Staff Cell -->
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
                                        {{ row.staff?.employee_code || 'EMP-??' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Leave Type -->
                        <td class="px-3 py-3 text-slate-700 dark:text-zinc-300 whitespace-nowrap font-medium">
                            {{ row.leave_type?.label || row.leave_type?.value }}
                        </td>

                        <!-- Date Range -->
                        <td class="px-3 py-3 text-slate-600 dark:text-zinc-400 whitespace-nowrap">
                            {{ row.start_date }} → {{ row.end_date }}
                        </td>

                        <!-- Total Days -->
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ row.total_days }} {{ row.total_days === 1 ? 'day' : 'days' }}
                            </span>
                        </td>

                        <!-- Reason -->
                        <td class="px-3 py-3 text-slate-600 dark:text-zinc-400 max-w-xs truncate">
                            {{ row.reason || '—' }}
                        </td>

                        <!-- Status Badge -->
                        <td class="px-3 py-3 whitespace-nowrap">
                            <HRMStatusBadge :status="row.status?.value || 'pending'" />
                        </td>

                        <!-- Actions Dropdown -->
                        <td class="py-3 pl-3 pr-5 text-right whitespace-nowrap" @click.stop>
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Fast Action: Approve Button -->
                                <button
                                    v-if="row.status?.value === 'pending'"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900 transition-colors"
                                    @click="emit('approve', row)"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Approve
                                </button>

                                <!-- Fast Action: Reject Button -->
                                <button
                                    v-if="row.status?.value === 'pending'"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300 dark:hover:bg-rose-900 transition-colors"
                                    @click="emit('reject', row)"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reject
                                </button>

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
                                                :href="`/admin/hrm/leaves/${row.public_id}`"
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
                                                v-if="row.status?.value === 'pending'"
                                                :href="`/admin/hrm/leaves/${row.public_id}/edit`"
                                                class="flex w-full items-center gap-2 px-3.5 py-1.5 text-xs text-slate-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                @click="close"
                                            >
                                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edit Request
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
                            </div>
                        </td>
                    </tr>

                    <!-- Empty state -->
                    <tr v-if="!leaves?.data || leaves.data.length === 0">
                        <td colspan="7" class="p-8">
                            <HRMEmptyState
                                title="No leave requests found"
                                description="No requests match your current filters. Try changing or clearing them."
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Pagination -->
        <div v-if="(leaves?.total ?? leaves?.data?.length ?? 0) > 0" class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <Pagination :data="leaves" />
        </div>
    </div>
</template>
