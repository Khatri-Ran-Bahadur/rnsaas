<script setup lang="ts">
import { computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { useTheme } from '@/composables/useTheme';

const props = withDefaults(
    defineProps<{
        modelValue?: string | Date | null;
        label?: string;
        placeholder?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        disabled?: boolean;
        readonly?: boolean;
        format?: string;
        modelType?: string;
    }>(),
    {
        modelValue: null,
        label: undefined,
        placeholder: 'Select date...',
        error: undefined,
        hint: undefined,
        required: false,
        disabled: false,
        readonly: false,
        format: 'yyyy-MM-dd',
        modelType: 'yyyy-MM-dd',
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const { isDark } = useTheme();

const internalValue = computed({
    get: () => props.modelValue ?? null,
    set: (val: any) => {
        emit('update:modelValue', val ? String(val) : null);
    },
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

        <!-- Datepicker Wrapper -->
        <div class="date-picker-custom-wrapper">
            <VueDatePicker
                v-model="internalValue"
                :dark="isDark"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :format="format"
                :model-type="modelType"
                :auto-apply="true"
                :enable-time-picker="false"
                :action-row="{ showNow: false, showPreview: false, showCancel: false, showSelect: false }"
                teleport="body"
            >
                <template #input-icon>
                    <svg
                        class="h-4 w-4 ml-3 text-zinc-400 dark:text-zinc-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </template>
            </VueDatePicker>
        </div>

        <!-- Hint or Error Message -->
        <p v-if="error" class="mt-1.5 text-xs text-rose-500 dark:text-rose-400">
            {{ error }}
        </p>
        <p v-else-if="hint" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
            {{ hint }}
        </p>
    </div>
</template>

<style>
.date-picker-custom-wrapper .dp__input {
    border-radius: 0.375rem;
    border-color: var(--color-zinc-200, #e4e4e7);
    font-size: 0.875rem;
    height: 2.5rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    padding-left: 2.5rem;
    font-family: inherit;
    transition: all 0.15s ease-in-out;
}

.date-picker-custom-wrapper .dp__theme_dark {
    --dp-background-color: var(--color-zinc-950, #09090b);
    --dp-text-color: #ffffff;
    --dp-hover-color: var(--color-zinc-800, #27272a);
    --dp-hover-text-color: #ffffff;
    --dp-hover-icon-color: #ffffff;
    --dp-primary-color: var(--primary-600, #4f46e5);
    --dp-primary-text-color: #ffffff;
    --dp-border-color: var(--color-zinc-800, #27272a);
    --dp-menu-border-color: var(--color-zinc-800, #27272a);
}

.date-picker-custom-wrapper .dp__theme_light {
    --dp-background-color: #ffffff;
    --dp-text-color: var(--color-zinc-900, #18181b);
    --dp-hover-color: var(--color-zinc-100, #f4f4f5);
    --dp-hover-text-color: var(--color-zinc-900, #18181b);
    --dp-hover-icon-color: var(--color-zinc-700, #3f3f46);
    --dp-primary-color: var(--primary-600, #4f46e5);
    --dp-primary-text-color: #ffffff;
    --dp-border-color: var(--color-zinc-200, #e4e4e7);
    --dp-menu-border-color: var(--color-zinc-200, #e4e4e7);
}

.date-picker-custom-wrapper .dp__input:focus {
    border-color: var(--primary-500, #6366f1);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

/* Hide clock and action row completely */
.dp__action_row,
.dp__selection_preview,
.dp__action_buttons {
    display: none !important;
}
</style>
