<script setup lang="ts">
import { ref } from 'vue';
import type { FileUploadPolicyConfig } from '@/types/superadmin/file-system';

const props = defineProps<{
    modelValue: FileUploadPolicyConfig;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: FileUploadPolicyConfig): void;
}>();

const newExtensionInput = ref('');

const defaultQuickPresets = [5, 10, 25, 50, 100];

const popularExtensions = [
    'PDF', 'JPG', 'JPEG', 'PNG', 'WEBP',
    'DOC', 'DOCX', 'XLS', 'XLSX', 'CSV', 'ZIP'
];

const toggleExtension = (ext: string) => {
    const upper = ext.toUpperCase().trim();
    const current = [...props.modelValue.allowedFileTypes];
    const index = current.indexOf(upper);

    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(upper);
    }

    emit('update:modelValue', {
        ...props.modelValue,
        allowedFileTypes: current,
    });
};

const removeExtension = (ext: string) => {
    const current = props.modelValue.allowedFileTypes.filter((e) => e !== ext);
    emit('update:modelValue', {
        ...props.modelValue,
        allowedFileTypes: current,
    });
};

const addCustomExtension = () => {
    const raw = newExtensionInput.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().trim();
    if (!raw) return;

    if (!props.modelValue.allowedFileTypes.includes(raw)) {
        emit('update:modelValue', {
            ...props.modelValue,
            allowedFileTypes: [...props.modelValue.allowedFileTypes, raw],
        });
    }

    newExtensionInput.value = '';
};

const setQuickSize = (size: number) => {
    emit('update:modelValue', {
        ...props.modelValue,
        maxFileSizeMb: size,
    });
};
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 mb-8">
        <div class="flex items-center gap-3 pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                    File Upload Policy
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Define global upload restrictions, maximum payload sizes, and accepted file extensions.
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- 1. Maximum File Size -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Global Maximum File Size
                    </label>
                    <span class="text-xs font-bold text-primary-600 dark:text-primary-400 font-mono">
                        {{ modelValue.maxFileSizeMb }} MB
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="relative flex-1">
                        <input
                            :value="modelValue.maxFileSizeMb"
                            type="number"
                            min="1"
                            max="500"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-12 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            @input="emit('update:modelValue', { ...modelValue, maxFileSizeMb: Number(($event.target as HTMLInputElement).value) })"
                        />
                        <span class="absolute right-3 top-2 text-xs font-medium text-zinc-400">MB</span>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="flex items-center gap-1.5 overflow-x-auto">
                        <button
                            v-for="preset in defaultQuickPresets"
                            :key="preset"
                            type="button"
                            :class="[
                                'rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors cursor-pointer',
                                modelValue.maxFileSizeMb === preset
                                    ? 'bg-primary-600 text-white font-semibold shadow-xs'
                                    : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700',
                            ]"
                            @click="setQuickSize(preset)"
                        >
                            {{ preset }}MB
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Allowed File Types -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                    Allowed File Types
                </label>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-3">
                    Click to toggle popular formats or append custom file extensions.
                </p>

                <!-- Chips Container -->
                <div class="flex flex-wrap items-center gap-2 p-3.5 rounded-xl border border-zinc-200 bg-zinc-50/60 dark:border-zinc-800 dark:bg-zinc-950/50 min-h-[56px]">
                    <div
                        v-for="ext in modelValue.allowedFileTypes"
                        :key="ext"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-zinc-800 shadow-2xs border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 transition-all hover:border-zinc-300"
                    >
                        <span>{{ ext }}</span>
                        <button
                            type="button"
                            class="text-zinc-400 hover:text-rose-500 dark:hover:text-rose-400 cursor-pointer p-0.5"
                            title="Remove format"
                            @click="removeExtension(ext)"
                        >
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Input to add custom extension -->
                    <div class="flex items-center gap-1.5 ml-auto">
                        <input
                            v-model="newExtensionInput"
                            type="text"
                            placeholder="Add ext..."
                            class="w-24 rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 placeholder:text-zinc-400 shadow-2xs focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                            @keydown.enter.prevent="addCustomExtension"
                        />
                        <button
                            type="button"
                            class="rounded-md bg-zinc-200 px-2 py-1 text-xs font-bold text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 cursor-pointer"
                            @click="addCustomExtension"
                        >
                            +
                        </button>
                    </div>
                </div>

                <!-- Quick toggles for popular list -->
                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] text-zinc-400 mr-1">Quick Select:</span>
                    <button
                        v-for="popular in popularExtensions"
                        :key="popular"
                        type="button"
                        :class="[
                            'rounded-md px-2 py-0.5 text-[11px] font-medium border transition-colors cursor-pointer',
                            modelValue.allowedFileTypes.includes(popular)
                                ? 'bg-primary-50 border-primary-300 text-primary-700 dark:bg-primary-950/60 dark:border-primary-800 dark:text-primary-300'
                                : 'bg-white border-zinc-200 text-zinc-500 hover:border-zinc-300 dark:bg-zinc-900 dark:border-zinc-800 dark:text-zinc-400',
                        ]"
                        @click="toggleExtension(popular)"
                    >
                        {{ popular }}
                    </button>
                </div>
            </div>

            <!-- Policy notice -->
            <div class="flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 border border-zinc-200/70 text-xs text-zinc-500 dark:bg-zinc-800/40 dark:border-zinc-800 dark:text-zinc-400">
                <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>These limits apply globally unless an individual module defines stricter tenant rules.</span>
            </div>
        </div>
    </div>
</template>
