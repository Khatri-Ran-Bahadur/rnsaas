<script setup lang="ts">
import { computed } from 'vue';

export interface SelectOption {
    label: string;
    value: string | number;
    disabled?: boolean;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string | number;
        options: (SelectOption | string)[];
        label?: string;
        placeholder?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        disabled?: boolean;
    }>(),
    {
        modelValue: '',
        label: undefined,
        placeholder: 'Select an option',
        error: undefined,
        hint: undefined,
        required: false,
        disabled: false,
    }
);

defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'change', event: Event): void;
}>();

const formattedOptions = computed<SelectOption[]>(() => {
    return props.options.map((opt) => {
        if (typeof opt === 'string') {
            return { label: opt, value: opt };
        }
        return opt;
    });
});
</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <label
            v-if="label"
            class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <!-- Select Wrapper -->
        <div class="relative rounded-lg shadow-2xs">
            <select
                :value="modelValue"
                :disabled="disabled"
                :required="required"
                :class="[
                    'block w-full appearance-none rounded-lg bg-white py-2.5 pl-3.5 pr-10 text-sm transition-all duration-150 border cursor-pointer dark:bg-zinc-950',
                    disabled ? 'bg-zinc-50 opacity-60 cursor-not-allowed dark:bg-zinc-900' : 'hover:border-zinc-300 dark:hover:border-zinc-700',
                    error
                        ? 'border-rose-500 text-rose-900 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:text-rose-400'
                        : 'border-zinc-200/90 text-zinc-900 focus:border-zinc-400 focus:ring-4 focus:ring-zinc-900/5 dark:border-zinc-800 dark:text-white dark:focus:border-zinc-600 dark:focus:ring-white/10',
                ]"
                @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value); $emit('change', $event)"
            >
                <option v-if="placeholder" value="" disabled :selected="!modelValue">
                    {{ placeholder }}
                </option>
                <option
                    v-for="opt in formattedOptions"
                    :key="opt.value"
                    :value="opt.value"
                    :disabled="opt.disabled"
                >
                    {{ opt.label }}
                </option>
            </select>

            <!-- Chevron Icon -->
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 dark:text-zinc-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

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
