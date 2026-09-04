<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue?: 'list' | 'grid';
    }>(),
    {
        modelValue: 'list',
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: 'list' | 'grid'): void;
    (e: 'change', value: 'list' | 'grid'): void;
}>();

const setView = (view: 'list' | 'grid') => {
    emit('update:modelValue', view);
    emit('change', view);
};
</script>

<template>
    <div class="inline-flex items-center rounded-lg border border-zinc-200/90 bg-white p-0.5 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
        <!-- List View Button -->
        <button
            type="button"
            :class="[
                'flex h-8 w-8 items-center justify-center rounded-md transition-colors cursor-pointer',
                modelValue === 'list'
                    ? 'bg-primary-600 text-white shadow-xs shadow-primary-500/20'
                    : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
            ]"
            title="List View"
            @click="setView('list')"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span class="sr-only">List view</span>
        </button>

        <!-- Grid View Button -->
        <button
            type="button"
            :class="[
                'flex h-8 w-8 items-center justify-center rounded-md transition-colors cursor-pointer',
                modelValue === 'grid'
                    ? 'bg-primary-600 text-white shadow-xs shadow-primary-500/20'
                    : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
            ]"
            title="Grid View"
            @click="setView('grid')"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            <span class="sr-only">Grid view</span>
        </button>
    </div>
</template>
