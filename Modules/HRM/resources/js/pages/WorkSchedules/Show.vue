<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import Switch from '@/components/Switch.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { WorkScheduleItem } from '../../types/work-schedules';

const props = defineProps<{
    workSchedule: { data: WorkScheduleItem } | WorkScheduleItem;
    can?: {
        manage?: boolean;
    };
}>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('work_schedules.manage'));

const schedule = computed(() => props.workSchedule.data);

const toggleStatus = () => {
    if (!canManage.value) return;
    router.patch(
        `/admin/hrm/work-schedules/${schedule.value.public_id}/toggle-status`,
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <OrganizationLayout
        title="Work Schedule Details"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Work Schedules', href: '/admin/hrm/work-schedules' },
            { label: schedule.name },
        ]"
    >
        <Head :title="`${schedule.name} - HRM`" />

        <div class="w-full space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ schedule.name }}
                        </h1>
                        <Badge :variant="schedule.is_active ? 'success' : 'neutral'">
                            {{ schedule.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Timezone: {{ schedule.timezone }} • Default Hours: {{ schedule.default_start_time }} to {{ schedule.default_end_time }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/hrm/work-schedules">
                        <Button variant="secondary" size="sm">Back to List</Button>
                    </Link>
                    <Link v-if="canManage" :href="`/admin/hrm/work-schedules/${schedule.public_id}/edit`">
                        <Button variant="primary" size="sm">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Schedule
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Default Working Hours</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ schedule.default_start_time }} — {{ schedule.default_end_time }}
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Daily Break</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ schedule.break_minutes }} Minutes
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Late Grace Window</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ schedule.late_grace_minutes }} Minutes
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Early Leave Grace</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ schedule.early_leave_grace_minutes }} Minutes
                    </p>
                </div>
            </div>

            <!-- Days Table -->
            <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="border-b border-zinc-100 p-4 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Weekly Shift Breakdown
                    </h3>
                    <div v-if="canManage" class="flex items-center gap-2 text-xs">
                        <span class="text-zinc-500">Active status:</span>
                        <Switch :model-value="schedule.is_active" @update:model-value="toggleStatus" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
                        <thead class="bg-zinc-50/80 text-[11px] uppercase font-semibold text-zinc-400 dark:bg-zinc-900/80">
                            <tr>
                                <th class="px-5 py-3">Day</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Start Time</th>
                                <th class="px-5 py-3">End Time</th>
                                <th class="px-5 py-3 text-right">Break</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr v-for="day in schedule.days" :key="day.day_of_week">
                                <td class="px-5 py-3.5 font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ day.day_name }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <Badge :variant="day.is_working_day ? 'success' : 'neutral'" size="sm">
                                        {{ day.is_working_day ? 'Working Day' : 'Day Off' }}
                                    </Badge>
                                </td>
                                <td class="px-5 py-3.5 font-mono">
                                    {{ day.is_working_day ? day.start_time : '—' }}
                                </td>
                                <td class="px-5 py-3.5 font-mono">
                                    {{ day.is_working_day ? day.end_time : '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono">
                                    {{ day.is_working_day ? `${day.break_minutes}m` : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
