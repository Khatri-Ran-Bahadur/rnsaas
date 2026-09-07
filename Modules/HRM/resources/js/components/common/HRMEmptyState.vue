<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        actionText?: string;
    }>(),
    {
        description: '',
        actionText: '',
    },
);

const emit = defineEmits<{
    (e: 'action'): void;
}>();
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/50 p-12 text-center dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            <slot name="icon">
                <svg class="h-6 w-6 text-slate-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </slot>
        </div>

        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
            {{ title }}
        </h3>
        <p v-if="description" class="mt-1 max-w-sm text-xs text-slate-500 dark:text-zinc-400">
            {{ description }}
        </p>

        <div v-if="actionText" class="mt-6">
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                @click="emit('action')"
            >
                <slot name="action-icon">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </slot>
                {{ actionText }}
            </button>
        </div>
    </div>
</template>
