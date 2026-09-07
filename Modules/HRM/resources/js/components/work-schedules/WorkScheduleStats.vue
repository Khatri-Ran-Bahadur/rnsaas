<script setup lang="ts">
import type { WorkScheduleStats } from '../../types/work-schedules';

defineProps<{
    stats?: WorkScheduleStats | { total: number; active: number };
}>();
</script>

<template>
    <div v-if="stats" class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4.5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Schedules</span>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ stats.total }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4.5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Active Schedules</span>
            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.active }}</p>
        </div>
    </div>
</template>
