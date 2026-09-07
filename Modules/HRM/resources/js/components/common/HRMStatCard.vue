<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        value: string | number;
        change?: string;
        trend?: 'up' | 'down' | 'neutral';
        color?: 'indigo' | 'emerald' | 'amber' | 'rose' | 'sky' | 'purple';
        iconBg?: string;
    }>(),
    {
        change: '',
        trend: 'neutral',
        color: 'indigo',
        iconBg: '',
    },
);

const colorStyles = computed(() => {
    switch (props.color) {
        case 'emerald':
            return {
                iconBg: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
                border: 'hover:border-emerald-200 dark:hover:border-emerald-800/60',
            };
        case 'amber':
            return {
                iconBg: 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
                border: 'hover:border-amber-200 dark:hover:border-amber-800/60',
            };
        case 'rose':
            return {
                iconBg: 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400',
                border: 'hover:border-rose-200 dark:hover:border-rose-800/60',
            };
        case 'sky':
            return {
                iconBg: 'bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400',
                border: 'hover:border-sky-200 dark:hover:border-sky-800/60',
            };
        case 'purple':
            return {
                iconBg: 'bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400',
                border: 'hover:border-purple-200 dark:hover:border-purple-800/60',
            };
        case 'indigo':
        default:
            return {
                iconBg: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400',
                border: 'hover:border-indigo-200 dark:hover:border-indigo-800/60',
            };
    }
});
</script>

<template>
    <div
        :class="[
            'rounded-xl border border-zinc-200/90 bg-white p-4 shadow-sm transition-all duration-200 dark:border-zinc-800/90 dark:bg-zinc-900',
            colorStyles.border,
        ]"
    >
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-medium text-slate-500 truncate dark:text-zinc-400">
                {{ title }}
            </span>
            <div
                v-if="$slots.icon"
                :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', colorStyles.iconBg]"
            >
                <slot name="icon" />
            </div>
        </div>

        <div class="mt-3 flex items-baseline justify-between gap-2">
            <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ value }}
            </span>

            <span
                v-if="change"
                :class="[
                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium',
                    trend === 'up'
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                        : trend === 'down'
                          ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                          : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                ]"
            >
                <svg
                    v-if="trend === 'up'"
                    class="h-3 w-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <svg
                    v-else-if="trend === 'down'"
                    class="h-3 w-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                {{ change }}
            </span>
        </div>
    </div>
</template>
