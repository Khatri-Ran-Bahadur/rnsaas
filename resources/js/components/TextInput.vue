<script setup lang="ts">
withDefaults(
    defineProps<{
        modelValue?: string | number;
        type?: string;
        label?: string;
        placeholder?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        disabled?: boolean;
        readonly?: boolean;
        autofocus?: boolean;
        prefixAddon?: string;
        suffixAddon?: string;
        mono?: boolean;
        uppercase?: boolean;
    }>(),
    {
        modelValue: '',
        type: 'text',
        label: undefined,
        placeholder: undefined,
        error: undefined,
        hint: undefined,
        required: false,
        disabled: false,
        readonly: false,
        autofocus: false,
        prefixAddon: undefined,
        suffixAddon: undefined,
        mono: false,
        uppercase: false,
    }
);

defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'input', event: Event): void;
    (e: 'change', event: Event): void;
}>();
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

        <!-- Input Wrapper -->
        <div class="relative flex rounded-lg shadow-2xs transition-all">
            <!-- Prefix Addon (e.g. app.sathisaas.com/) -->
            <span
                v-if="prefixAddon"
                class="inline-flex items-center rounded-l-lg border border-r-0 border-zinc-200/90 bg-zinc-50/80 px-3 text-xs text-zinc-500 select-none dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400"
            >
                {{ prefixAddon }}
            </span>

            <!-- Prefix Slot / Icon -->
            <div
                v-if="$slots.prefix"
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400 dark:text-zinc-500"
            >
                <slot name="prefix" />
            </div>

            <!-- Input Element -->
            <input
                :value="modelValue"
                :type="type"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :readonly="readonly"
                :autofocus="autofocus"
                :class="[
                    'block w-full py-2.5 text-sm transition-all duration-150 border bg-white dark:bg-zinc-950',
                    prefixAddon ? 'rounded-r-lg rounded-l-none' : suffixAddon ? 'rounded-l-lg rounded-r-none' : 'rounded-lg',
                    $slots.prefix ? 'pl-10' : 'pl-3.5',
                    $slots.suffix ? 'pr-10' : 'pr-3.5',
                    mono ? 'font-mono' : '',
                    uppercase ? 'uppercase' : '',
                    disabled ? 'bg-zinc-50 opacity-60 cursor-not-allowed dark:bg-zinc-900' : 'hover:border-zinc-300 dark:hover:border-zinc-700',
                    error
                        ? 'border-rose-500 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 dark:text-rose-400'
                        : 'border-zinc-200/90 text-zinc-900 placeholder-zinc-400 focus:border-zinc-400 focus:ring-4 focus:ring-zinc-900/5 dark:border-zinc-800 dark:text-white dark:placeholder-zinc-500 dark:focus:border-zinc-600 dark:focus:ring-white/10',
                ]"
                @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value); $emit('input', $event)"
                @change="$emit('change', $event)"
            />

            <!-- Suffix Slot / Icon -->
            <div
                v-if="$slots.suffix"
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 dark:text-zinc-500"
            >
                <slot name="suffix" />
            </div>

            <!-- Suffix Addon -->
            <span
                v-if="suffixAddon"
                class="inline-flex items-center rounded-r-lg border border-l-0 border-zinc-200/90 bg-zinc-50/80 px-3 text-xs text-zinc-500 select-none dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400"
            >
                {{ suffixAddon }}
            </span>
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
