<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        showFilters?: boolean;
        label?: string;
        count?: number;
    }>(),
    {
        showFilters: false,
        label: 'Filters',
        count: 0,
    }
);

const emit = defineEmits<{
    (e: 'toggle'): void;
}>();
</script>

<template>
    <div class="relative inline-block">
        <button
            type="button"
            class="inline-flex h-9 items-center gap-2 rounded-lg border border-zinc-200/90 bg-white px-3 text-xs font-medium text-zinc-700 shadow-2xs transition-colors hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800 cursor-pointer"
            :class="{ 'border-zinc-900 dark:border-white font-semibold': showFilters || count > 0 }"
            @click="emit('toggle')"
        >
            <svg class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <span>{{ label }}</span>
            <svg
                class="h-3.5 w-3.5 text-zinc-400 transition-transform duration-200"
                :class="{ 'rotate-180': showFilters }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Active Filter Count Badge -->
        <span
            v-if="count > 0"
            class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary-600 text-[10px] font-bold text-white shadow-2xs"
        >
            {{ count }}
        </span>
    </div>
</template>
