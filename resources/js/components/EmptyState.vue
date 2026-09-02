<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700 bg-white/50 dark:bg-zinc-900/50">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 mb-4">
            <slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </slot>
        </div>

        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
            {{ title }}
        </h3>

        <p v-if="description" class="mt-1.5 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
            {{ description }}
        </p>

        <div v-if="$slots.actions" class="mt-6">
            <slot name="actions" />
        </div>
    </div>
</template>
