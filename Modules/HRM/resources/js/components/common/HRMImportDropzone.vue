<script setup lang="ts">
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: File | null;
        accept?: string;
        maxSizeMb?: number;
    }>(),
    {
        modelValue: null,
        accept: '.xlsx,.xls,.csv',
        maxSizeMb: 10,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
    (e: 'download-template'): void;
}>();

const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const handleFiles = (files: FileList | null) => {
    if (!files || files.length === 0) return;
    const file = files[0];
    if (file.size > props.maxSizeMb * 1024 * 1024) {
        alert(`File size exceeds ${props.maxSizeMb}MB.`);
        return;
    }
    emit('update:modelValue', file);
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    if (e.dataTransfer?.files) {
        handleFiles(e.dataTransfer.files);
    }
};

const removeFile = () => {
    emit('update:modelValue', null);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const formatSize = (bytes: number): string => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};
</script>

<template>
    <div class="space-y-4">
        <!-- Dropzone Box -->
        <div
            v-if="!modelValue"
            :class="[
                'flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-8 text-center transition-colors cursor-pointer',
                isDragging
                    ? 'border-indigo-500 bg-indigo-50/50 dark:border-indigo-500 dark:bg-indigo-950/20'
                    : 'border-zinc-300 hover:border-zinc-400 bg-white dark:border-zinc-700 dark:hover:border-zinc-600 dark:bg-zinc-900',
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="fileInput?.click()"
        >
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                :accept="accept"
                @change="handleFiles(($event.target as HTMLInputElement).files)"
            />

            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 mb-4">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>

            <p class="text-sm font-semibold text-slate-800 dark:text-white">
                Click to upload <span class="font-normal text-slate-500 dark:text-zinc-400">or drag and drop</span>
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                Excel files (.xlsx, .xls) up to {{ maxSizeMb }}MB
            </p>

            <div class="mt-4 flex items-center gap-3">
                <button
                    type="button"
                    class="rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                    @click.stop="fileInput?.click()"
                >
                    Choose File
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                    @click.stop="emit('download-template')"
                >
                    <svg class="h-4 w-4 text-slate-400 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Template
                </button>
            </div>
        </div>

        <!-- Selected File Preview Card -->
        <div
            v-else
            class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="flex items-center gap-3 min-w-0">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                        {{ modelValue.name }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        {{ formatSize(modelValue.size) }} • Ready for mapping
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="rounded-lg p-1.5 text-slate-400 hover:bg-zinc-100 hover:text-rose-600 dark:hover:bg-zinc-800 dark:hover:text-rose-400 transition-colors"
                title="Remove file"
                @click="removeFile"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</template>
