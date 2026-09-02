<script setup lang="ts">
defineProps<{
    title: string;
    value: string | number;
    subtitle?: string;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
    badgeColor?: 'emerald' | 'amber' | 'blue' | 'rose' | 'indigo' | 'zinc';
}>();
</script>

<template>
    <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-xs transition-all duration-200 hover:shadow-md hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                {{ title }}
            </p>
            <div
                v-if="$slots.icon"
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-50 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                :class="{
                    'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400': badgeColor === 'emerald',
                    'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400': badgeColor === 'amber',
                    'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400': badgeColor === 'blue',
                    'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400': badgeColor === 'indigo',
                    'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400': badgeColor === 'rose',
                }"
            >
                <slot name="icon" />
            </div>
        </div>

        <div class="mt-4 flex items-baseline gap-3">
            <h3 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ value }}
            </h3>

            <div
                v-if="trendValue"
                class="inline-flex items-center gap-1 text-xs font-semibold"
                :class="{
                    'text-emerald-600 dark:text-emerald-400': trend === 'up',
                    'text-rose-600 dark:text-rose-400': trend === 'down',
                    'text-zinc-500 dark:text-zinc-400': trend === 'neutral',
                }"
            >
                <svg
                    v-if="trend === 'up'"
                    class="h-3.5 w-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <svg
                    v-else-if="trend === 'down'"
                    class="h-3.5 w-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <span>{{ trendValue }}</span>
            </div>
        </div>

        <p v-if="subtitle" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            {{ subtitle }}
        </p>
    </div>
</template>
