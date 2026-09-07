<script setup lang="ts">
import type { ShiftStats } from '../../types/shifts';

defineProps<{
    stats?: ShiftStats;
}>();
</script>

<template>
    <div v-if="stats" class="grid grid-cols-3 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4.5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Shifts</span>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ stats.total }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4.5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Active Shifts</span>
            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.active }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4.5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Overnight / Night Shifts</span>
            <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.night }}</p>
        </div>
    </div>
</template>
