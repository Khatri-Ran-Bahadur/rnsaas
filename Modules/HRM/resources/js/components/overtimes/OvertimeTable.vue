<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import type { PaginatedOvertimes, OvertimeItem } from '../../types/overtimes';

defineProps<{
    overtimes: PaginatedOvertimes;
    canManage: boolean;
    approvingId: string | null;
    hasActiveFilters: boolean;
}>();

const emit = defineEmits<{
    (e: 'approve', item: OvertimeItem): void;
    (e: 'reject', item: OvertimeItem): void;
}>();

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'pending':
            return 'warning';
        case 'rejected':
            return 'danger';
        default:
            return 'neutral';
    }
};

const getTypeBadgeVariant = (type: string) => {
    switch (type) {
        case 'holiday':
            return 'danger';
        case 'weekend':
            return 'info';
        case 'regular':
        default:
            return 'neutral';
    }
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-left">
            <thead class="bg-zinc-50 dark:bg-zinc-950/50">
                <tr>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Employee</th>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Duration</th>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Type & Rate</th>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                <tr
                    v-for="item in overtimes.data"
                    :key="item.public_id"
                    class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors"
                >
                    <!-- Employee Details -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 font-semibold text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                {{ (item.staff?.name ?? 'E').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ item.staff?.name ?? 'Staff Member' }}
                                </div>
                                <div class="text-[11px] font-mono text-zinc-400">
                                    {{ item.staff?.employee_code }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Date & Time -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="text-xs font-medium text-zinc-800 dark:text-zinc-200">
                            {{ item.date }}
                        </div>
                        <div class="text-[11px] font-mono text-zinc-400">
                            {{ item.start_time }} — {{ item.end_time }}
                        </div>
                    </td>

                    <!-- Duration & Hours -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                            {{ item.total_hours }} hrs
                        </div>
                        <div class="text-[11px] text-zinc-400">
                            {{ item.total_minutes }} mins
                        </div>
                    </td>

                    <!-- Type & Multiplier -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <Badge :variant="getTypeBadgeVariant(item.type)">
                                {{ item.type_label }}
                            </Badge>
                            <span class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                {{ item.rate_multiplier }}x
                            </span>
                        </div>
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <Badge :variant="getStatusBadgeVariant(item.status)">
                            {{ item.status_label }}
                        </Badge>
                    </td>

                    <!-- Actions Dropdown -->
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <Dropdown align="right" width="w-48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300 cursor-pointer"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                </button>
                            </template>
                            <template #default="{ close }">
                                <div class="py-1">
                                    <Link
                                        :href="`/admin/hrm/overtimes/${item.public_id}`"
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
                                        :href="`/admin/hrm/overtimes/${item.public_id}/edit`"
                                        class="flex items-center gap-2 px-4 py-2 text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                        @click="close"
                                    >
                                        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit Record
                                    </Link>
                                    <template v-if="canManage && item.status === 'pending'">
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 px-4 py-2 text-xs text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 text-left cursor-pointer"
                                            :disabled="approvingId === item.public_id"
                                            @click="() => { close(); emit('approve', item); }"
                                        >
                                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve Request
                                        </button>
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 text-left cursor-pointer"
                                            @click="() => { close(); emit('reject', item); }"
                                        >
                                            <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Reject Request
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </Dropdown>
                    </td>
                </tr>

                <!-- Empty state -->
                <tr v-if="overtimes.data.length === 0">
                    <td colspan="6" class="px-5 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">No overtime records found</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ hasActiveFilters ? 'Try adjusting your search filters to find overtime entries.' : 'Log an overtime entry to begin tracking extra employee work hours.' }}
                        </p>
                        <div v-if="canManage && !hasActiveFilters" class="mt-4">
                            <Link href="/admin/hrm/overtimes/create">
                                <Button variant="primary" size="sm">Log Overtime</Button>
                            </Link>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination footer -->
        <Pagination :data="overtimes" />
    </div>
</template>
