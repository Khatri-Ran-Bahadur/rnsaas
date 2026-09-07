<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Switch from '@/components/Switch.vue';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatedWorkSchedules, WorkScheduleItem } from '../../types/work-schedules';

defineProps<{
    workSchedules: PaginatedWorkSchedules;
    canManage: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggleStatus', schedule: WorkScheduleItem): void;
}>();
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3.5">Schedule Name</th>
                        <th class="px-5 py-3.5">Timezone</th>
                        <th class="px-5 py-3.5">Default Hours</th>
                        <th class="px-5 py-3.5">Break / Grace</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <tr v-if="workSchedules.data.length === 0">
                        <td colspan="6" class="px-5 py-12 text-center text-zinc-500">
                            No work schedules found.
                        </td>
                    </tr>
                    <tr
                        v-for="schedule in workSchedules.data"
                        :key="schedule.public_id"
                        class="transition-colors hover:bg-zinc-50/75 dark:hover:bg-zinc-800/50"
                    >
                        <td class="px-5 py-4">
                            <Link
                                :href="`/admin/hrm/work-schedules/${schedule.public_id}`"
                                class="font-semibold text-zinc-900 hover:text-primary-600 dark:text-zinc-100 dark:hover:text-primary-400"
                            >
                                {{ schedule.name }}
                            </Link>
                            <p v-if="schedule.description" class="text-[11px] text-zinc-400 truncate max-w-xs">
                                {{ schedule.description }}
                            </p>
                        </td>
                        <td class="px-5 py-4 font-mono text-zinc-500">
                            {{ schedule.timezone }}
                        </td>
                        <td class="px-5 py-4 font-medium text-zinc-800 dark:text-zinc-200">
                            {{ schedule.default_start_time || '09:00' }} — {{ schedule.default_end_time || '17:00' }}
                        </td>
                        <td class="px-5 py-4 text-zinc-500">
                            {{ schedule.break_minutes ?? 60 }}m break • {{ schedule.late_grace_minutes ?? 15 }}m grace
                        </td>
                        <td class="px-5 py-4 text-center">
                            <Switch
                                :model-value="schedule.is_active"
                                :disabled="!canManage"
                                @update:model-value="emit('toggleStatus', schedule)"
                            />
                        </td>
                        <td class="p-4 align-middle text-right whitespace-nowrap">
                            <Dropdown align="right" width="w-44">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors cursor-pointer"
                                        title="Actions"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>

                                <template #default="{ close }">
                                    <div class="py-1 text-xs text-zinc-700 dark:text-zinc-200">
                                        <Link
                                            :href="`/admin/hrm/work-schedules/${schedule.public_id}`"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>View Details</span>
                                        </Link>

                                        <Link
                                            v-if="canManage"
                                            :href="`/admin/hrm/work-schedules/${schedule.public_id}/edit`"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit Schedule</span>
                                        </Link>
                                    </div>
                                </template>
                            </Dropdown>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination footer -->
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <Pagination :data="workSchedules" />
        </div>
    </div>
</template>
