<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue?: boolean;
        label?: string;
        description?: string;
        disabled?: boolean;
    }>(),
    {
        modelValue: false,
        label: undefined,
        description: undefined,
        disabled: false,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'change', value: boolean): void;
}>();

const toggle = () => {
    if (props.disabled) return;
    const newValue = !props.modelValue;
    emit('update:modelValue', newValue);
    emit('change', newValue);
};
</script>

<template>
    <div class="flex items-start justify-between gap-4">
        <!-- Label and description -->
        <div v-if="label || description" class="flex flex-col cursor-pointer" @click="toggle">
            <span v-if="label" class="text-sm font-medium text-zinc-900 dark:text-white select-none">
                {{ label }}
            </span>
            <span v-if="description" class="text-xs text-zinc-500 dark:text-zinc-400 select-none">
                {{ description }}
            </span>
        </div>

        <!-- Switch Toggle Control -->
        <button
            type="button"
            role="switch"
            :aria-checked="modelValue"
            :disabled="disabled"
            :class="[
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:focus:ring-white dark:focus:ring-offset-zinc-950',
                modelValue
                    ? 'bg-zinc-900 dark:bg-white'
                    : 'bg-zinc-200 dark:bg-zinc-800',
                disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '',
            ]"
            @click="toggle"
        >
            <span
                :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out dark:bg-zinc-900',
                    modelValue ? 'translate-x-5' : 'translate-x-0',
                ]"
            />
        </button>
    </div>
</template>
