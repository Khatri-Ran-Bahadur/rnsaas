<script setup lang="ts">
import { ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        placeholder?: string;
        showButton?: boolean;
        buttonText?: string;
    }>(),
    {
        modelValue: '',
        placeholder: 'Search...',
        showButton: true,
        buttonText: 'Search',
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'search', value: string): void;
    (e: 'clear'): void;
}>();

const searchTerm = ref(props.modelValue);

watch(
    () => props.modelValue,
    (newVal) => {
        searchTerm.value = newVal;
    }
);

const handleInput = () => {
    emit('update:modelValue', searchTerm.value);
};

const handleSearch = () => {
    emit('search', searchTerm.value);
};

const handleClear = () => {
    searchTerm.value = '';
    emit('update:modelValue', '');
    emit('clear');
    emit('search', '');
};
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="relative flex-1">
            <!-- Search Icon -->
            <svg
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400 dark:text-zinc-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>

            <!-- Input -->
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="placeholder"
                class="w-full rounded-lg border border-zinc-200 bg-white py-2 pl-9 pr-8 text-sm text-zinc-900 placeholder-zinc-400 shadow-2xs transition-colors hover:border-zinc-300 focus:border-primary-500 focus:outline-hidden focus:ring-1 focus:ring-primary-500/20 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:hover:border-zinc-700 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                @input="handleInput"
                @keyup.enter="handleSearch"
            />

            <!-- Clear Button -->
            <button
                v-if="searchTerm"
                type="button"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                title="Clear search"
                @click="handleClear"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Search Button (themed primary) -->
        <button
            v-if="showButton"
            type="button"
            class="inline-flex h-9 items-center justify-center rounded-lg bg-primary-600 px-4 text-xs font-semibold text-white shadow-xs shadow-primary-500/25 transition-colors hover:bg-primary-700 active:scale-[0.98] cursor-pointer shrink-0"
            @click="handleSearch"
        >
            {{ buttonText }}
        </button>
    </div>
</template>
