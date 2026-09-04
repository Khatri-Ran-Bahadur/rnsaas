<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';

export interface ComboboxOption {
    label: string;
    value: string | number;
    sublabel?: string;
    flag?: string;
    disabled?: boolean;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string | number;
        options: (ComboboxOption | string)[];
        label?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        disabled?: boolean;
        searchable?: boolean;
    }>(),
    {
        modelValue: '',
        label: undefined,
        placeholder: 'Select an option',
        searchPlaceholder: 'Search...',
        error: undefined,
        hint: undefined,
        required: false,
        disabled: false,
        searchable: true,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'change', value: string | number, option?: ComboboxOption): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

const formattedOptions = computed<ComboboxOption[]>(() => {
    return props.options.map((opt) => {
        if (typeof opt === 'string') {
            return { label: opt, value: opt };
        }
        return opt;
    });
});

const filteredOptions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return formattedOptions.value;

    return formattedOptions.value.filter((opt) => {
        const label = opt.label.toLowerCase();
        const value = String(opt.value).toLowerCase();
        const sublabel = (opt.sublabel || '').toLowerCase();
        return label.includes(q) || value.includes(q) || sublabel.includes(q);
    });
});

const selectedOption = computed(() => {
    return formattedOptions.value.find((opt) => opt.value === props.modelValue);
});

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        nextTick(() => {
            searchInputRef.value?.focus();
        });
    }
};

const selectOption = (opt: ComboboxOption) => {
    if (opt.disabled) return;
    emit('update:modelValue', opt.value);
    emit('change', opt.value, opt);
    isOpen.value = false;
    searchQuery.value = '';
};

const handleClickOutside = (event: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        searchQuery.value = '';
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Label -->
        <label
            v-if="label"
            class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5"
            @click="toggleDropdown"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <!-- Clean Input-Style Trigger Button (Matching Reference Image) -->
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'flex h-10 w-full items-center justify-between rounded-lg bg-white px-3.5 text-left text-sm transition-all duration-150 border border-zinc-200 dark:bg-zinc-950 dark:border-zinc-800',
                disabled ? 'opacity-50 cursor-not-allowed bg-zinc-50 dark:bg-zinc-900' : 'cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-700',
                error
                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                    : 'focus:border-primary-500 focus:ring-1 focus:ring-primary-500/20 dark:focus:border-primary-500',
                isOpen ? 'border-primary-500 ring-1 ring-primary-500/20 dark:border-primary-500' : 'shadow-2xs',
            ]"
            @click="toggleDropdown"
        >
            <div class="flex items-center gap-2.5 overflow-hidden truncate">
                <!-- Selected Flag / Icon -->
                <span v-if="selectedOption?.flag" class="text-base shrink-0 leading-none">
                    {{ selectedOption.flag }}
                </span>

                <!-- Selected Label or Placeholder -->
                <span
                    v-if="selectedOption"
                    class="truncate font-normal text-zinc-900 dark:text-zinc-100"
                >
                    {{ selectedOption.label }}
                </span>
                <span v-else class="truncate text-zinc-400 dark:text-zinc-500">
                    {{ placeholder }}
                </span>
            </div>

            <!-- Sleek Subtle Chevron -->
            <div class="shrink-0 ml-2 text-zinc-400 dark:text-zinc-500">
                <svg
                    class="h-4 w-4 transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <!-- Floating Dropdown Menu (Matching Image 2 Reference) -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 left-0 right-0 mt-1.5 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
            >
                <!-- Compact Search Box (when searchable) -->
                <div v-if="searchable && formattedOptions.length > 5" class="p-1 pb-1.5 border-b border-zinc-100 dark:border-zinc-800 mb-1">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            :placeholder="searchPlaceholder"
                            class="w-full rounded-md border border-zinc-200 bg-zinc-50/70 py-1.5 pl-8 pr-7 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-400 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-white dark:placeholder-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-500"
                            @click.stop
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            @click.stop="searchQuery = ''"
                        >
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Options List (Exact Item Layout with Checkmark, Flag, and Label) -->
                <div class="max-h-64 overflow-y-auto space-y-0.5 overscroll-contain">
                    <button
                        v-for="opt in filteredOptions"
                        :key="opt.value"
                        type="button"
                        :disabled="opt.disabled"
                        :class="[
                            'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-xs transition-colors',
                            modelValue === opt.value
                                ? 'bg-primary-50 text-primary-900 font-semibold dark:bg-primary-950/60 dark:text-primary-200'
                                : 'text-zinc-700 hover:bg-zinc-50/80 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800/60 dark:hover:text-white',
                            opt.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                        ]"
                        @click="selectOption(opt)"
                    >
                        <!-- Left Checkmark Indicator (Visible when selected) -->
                        <div class="w-4 shrink-0 flex items-center justify-center text-primary-600 dark:text-primary-400">
                            <svg
                                v-if="modelValue === opt.value"
                                class="h-3.5 w-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <!-- Flag Icon -->
                        <span v-if="opt.flag" class="text-sm shrink-0 leading-none">
                            {{ opt.flag }}
                        </span>

                        <!-- Label Text -->
                        <span class="truncate font-normal">
                            {{ opt.label }}
                        </span>
                    </button>

                    <!-- Empty State -->
                    <div v-if="filteredOptions.length === 0" class="py-5 text-center text-xs text-zinc-400 dark:text-zinc-500">
                        No results found
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Error Message -->
        <p v-if="error" class="mt-1.5 text-xs text-rose-500 font-medium">
            {{ error }}
        </p>

        <!-- Hint Message -->
        <p v-else-if="hint" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
            {{ hint }}
        </p>
    </div>
</template>
