<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: boolean | any[];
        value?: any;
        id?: string;
        label?: string;
        description?: string;
        error?: string;
        disabled?: boolean;
    }>(),
    {
        modelValue: false,
        value: undefined,
        id: undefined,
        label: undefined,
        description: undefined,
        error: undefined,
        disabled: false,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'change', value: any): void;
}>();

const checkboxId = computed(() => props.id || `checkbox-${Math.random().toString(36).substring(2, 9)}`);

const isChecked = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue.includes(props.value);
    }
    return Boolean(props.modelValue);
});

const handleChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (Array.isArray(props.modelValue)) {
        const newValue = [...props.modelValue];
        if (target.checked) {
            newValue.push(props.value);
        } else {
            const idx = newValue.indexOf(props.value);
            if (idx > -1) newValue.splice(idx, 1);
        }
        emit('update:modelValue', newValue);
        emit('change', newValue);
    } else {
        emit('update:modelValue', target.checked);
        emit('change', target.checked);
    }
};
</script>

<template>
    <div class="flex items-start gap-3">
        <div class="flex h-5 items-center">
            <input
                :id="checkboxId"
                type="checkbox"
                :checked="isChecked"
                :disabled="disabled"
                :value="value"
                :class="[
                    'h-4 w-4 rounded border border-zinc-300 text-zinc-900 transition-colors focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:border-zinc-700 dark:bg-zinc-950 dark:checked:bg-white dark:checked:text-zinc-900 dark:focus:ring-white dark:focus:ring-offset-zinc-950',
                    disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                    error ? 'border-rose-500 focus:ring-rose-500' : '',
                ]"
                @change="handleChange"
            />
        </div>

        <div v-if="label || description" class="text-sm">
            <label
                v-if="label"
                :for="checkboxId"
                :class="[
                    'font-medium select-none text-zinc-900 dark:text-zinc-100',
                    disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                ]"
            >
                {{ label }}
            </label>
            <p v-if="description" class="text-xs text-zinc-500 dark:text-zinc-400 select-none">
                {{ description }}
            </p>
            <p v-if="error" class="mt-1 text-xs text-rose-500 font-medium">
                {{ error }}
            </p>
        </div>
    </div>
</template>
