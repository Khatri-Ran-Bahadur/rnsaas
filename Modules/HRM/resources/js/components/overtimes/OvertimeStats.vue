<script setup lang="ts">
import { computed } from 'vue';
import type { OvertimeStats } from '../../types/overtimes';

const props = defineProps<{
    stats: OvertimeStats;
}>();

const totalHoursFormatted = computed(() => {
    const hours = (props.stats.total_minutes / 60).toFixed(1);
    return `${hours} hrs`;
});
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-500">Total Entries</span>
                <div class="rounded-lg bg-zinc-100 p-2 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ stats.total }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5 shadow-xs dark:border-amber-900/40 dark:bg-amber-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-amber-700 dark:text-amber-400">Pending Approvals</span>
                <div class="rounded-lg bg-amber-100 p-2 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ stats.pending }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-xs dark:border-emerald-900/40 dark:bg-emerald-950/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Approved</span>
                <div class="rounded-lg bg-emerald-100 p-2 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ stats.approved }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-500">Cumulative Time</span>
                <div class="rounded-lg bg-blue-100 p-2 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ totalHoursFormatted }}</span>
                <span class="ml-1 text-xs text-zinc-400">({{ stats.total_minutes }} mins)</span>
            </div>
        </div>
    </div>
</template>
