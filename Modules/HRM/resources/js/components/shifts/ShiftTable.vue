<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Switch from '@/components/Switch.vue';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatedShifts, ShiftItem } from '../../types/shifts';

defineProps<{
    shifts: PaginatedShifts;
    canManage: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggleStatus', shift: ShiftItem): void;
}>();
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3.5">Shift Name & Code</th>
                        <th class="px-5 py-3.5">Working Hours</th>
                        <th class="px-5 py-3.5">Break</th>
                        <th class="px-5 py-3.5">Grace Window</th>
                        <th class="px-5 py-3.5">Type</th>
                        <th class="px-5 py-3.5 text-center">Active</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <tr v-if="shifts.data.length === 0">
                        <td colspan="7" class="px-5 py-12 text-center text-zinc-500">
                            No shifts found.
                        </td>
                    </tr>
                    <tr v-for="shift in shifts.data" :key="shift.public_id" class="transition-colors hover:bg-zinc-50/75 dark:hover:bg-zinc-800/50">
                        <td class="px-5 py-4">
                            <Link :href="`/admin/hrm/shifts/${shift.public_id}`" class="font-semibold text-zinc-900 hover:text-primary-600 dark:text-zinc-100 dark:hover:text-primary-400">
                                {{ shift.name }}
                            </Link>
                            <span class="ml-2 font-mono text-[11px] text-zinc-400 bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">
                                {{ shift.code }}
                            </span>
                        </td>
                        <td class="px-5 py-4 font-mono font-medium text-zinc-800 dark:text-zinc-200">
                            {{ shift.start_time }} — {{ shift.end_time }}
                        </td>
                        <td class="px-5 py-4 font-mono text-zinc-500">
                            {{ shift.break_minutes }} mins
                        </td>
                        <td class="px-5 py-4 text-zinc-500">
                            Late: {{ shift.late_grace_minutes }}m • Early: {{ shift.early_leave_grace_minutes }}m
                        </td>
                        <td class="px-5 py-4">
                            <Badge :variant="shift.is_overnight ? 'warning' : 'neutral'" size="sm">
                                {{ shift.is_overnight ? '🌙 Overnight' : '☀️ Day Shift' }}
                            </Badge>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <Switch :model-value="shift.is_active" :disabled="!canManage" @update:model-value="emit('toggleStatus', shift)" />
                        </td>
                        <td class="px-5 py-4 text-right">
                            <Dropdown align="right" width="w-44">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>
                                </template>
                                <template #default="{ close }">
                                    <div class="py-1">
                                        <Link
                                            :href="`/admin/hrm/shifts/${shift.public_id}`"
                                            class="flex items-center gap-2 px-4 py-2 text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Details
                                        </Link>
                                        <Link
                                            v-if="canManage"
                                            :href="`/admin/hrm/shifts/${shift.public_id}/edit`"
                                            class="flex items-center gap-2 px-4 py-2 text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit Shift
                                        </Link>
                                    </div>
                                </template>
                            </Dropdown>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <Pagination :data="shifts" />
    </div>
</template>
