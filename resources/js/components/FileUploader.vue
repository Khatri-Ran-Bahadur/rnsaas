<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    validateFile,
    compressImage,
    formatFileSize,
    type CompressionResult,
} from '@/utils/fileCompressor';

const props = withDefaults(
    defineProps<{
        modelValue?: File | null;
        currentFileName?: string | null;
        currentFileSize?: number | null;
        currentFileUrl?: string | null;
        maxSizeMb?: number;
        allowedExtensions?: string[];
        enableCompression?: boolean;
        compressionQuality?: number;
        label?: string;
        hint?: string;
        error?: string;
        disabled?: boolean;
        required?: boolean;
    }>(),
    {
        modelValue: null,
        currentFileName: null,
        currentFileSize: null,
        currentFileUrl: null,
        maxSizeMb: 10,
        allowedExtensions: () => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        enableCompression: true,
        compressionQuality: 0.82,
        label: 'Upload Document / File',
        hint: '',
        error: '',
        disabled: false,
        required: false,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', val: File | null): void;
    (e: 'change', val: File | null): void;
    (e: 'remove'): void;
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const isCompressing = ref(false);
const localError = ref('');
const compressionInfo = ref<CompressionResult | null>(null);
const previewUrl = ref<string | null>(null);

const activeError = computed(() => props.error || localError.value);

const hasFile = computed(() => Boolean(props.modelValue || props.currentFileName));

const displayFileName = computed(() => {
    if (props.modelValue) return props.modelValue.name;
    if (props.currentFileName) return props.currentFileName;
    return '';
});

const displayFileSize = computed(() => {
    if (props.modelValue) return formatFileSize(props.modelValue.size);
    if (props.currentFileSize) return formatFileSize(props.currentFileSize);
    return '';
});

const isImageFile = computed(() => {
    if (props.modelValue) {
        return props.modelValue.type.startsWith('image/');
    }
    if (props.currentFileName) {
        return Boolean(props.currentFileName.match(/\.(jpe?g|png|webp|gif|svg)$/i));
    }
    return false;
});

const isPdfFile = computed(() => {
    if (props.modelValue) {
        return props.modelValue.type === 'application/pdf' || props.modelValue.name.endsWith('.pdf');
    }
    if (props.currentFileName) {
        return props.currentFileName.toLowerCase().endsWith('.pdf');
    }
    return false;
});

// Update preview URL when modelValue changes
watch(
    () => props.modelValue,
    (file) => {
        if (file && file.type.startsWith('image/')) {
            previewUrl.value = URL.createObjectURL(file);
        } else if (!file) {
            previewUrl.value = null;
            compressionInfo.value = null;
        }
    },
    { immediate: true }
);

const handleFiles = async (fileList: FileList | File[] | null) => {
    if (!fileList || fileList.length === 0 || props.disabled) return;

    const rawFile = fileList[0];
    localError.value = '';

    // Validate size and format
    const validation = validateFile(rawFile, {
        maxSizeMb: props.maxSizeMb,
        allowedExtensions: props.allowedExtensions,
    });

    if (!validation.valid) {
        localError.value = validation.error || 'Invalid file.';
        return;
    }

    let finalFile = rawFile;

    // Compress raster image if compression enabled
    if (props.enableCompression && rawFile.type.startsWith('image/')) {
        isCompressing.value = true;
        try {
            const comp = await compressImage(rawFile, {
                maxWidth: 2048,
                maxHeight: 2048,
                quality: props.compressionQuality,
            });
            compressionInfo.value = comp;
            finalFile = comp.file;
        } catch {
            // If compression fails, fallback to raw file
            finalFile = rawFile;
        } finally {
            isCompressing.value = false;
        }
    }

    emit('update:modelValue', finalFile);
    emit('change', finalFile);
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    if (e.dataTransfer?.files) {
        handleFiles(e.dataTransfer.files);
    }
};

const removeFile = () => {
    if (props.disabled) return;
    localError.value = '';
    compressionInfo.value = null;
    previewUrl.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
    emit('update:modelValue', null);
    emit('change', null);
    emit('remove');
};

const openFileDialog = () => {
    if (!props.disabled) {
        fileInputRef.value?.click();
    }
};
</script>

<template>
    <div class="space-y-1.5">
        <!-- Label & Optional Badge -->
        <div v-if="label" class="flex items-center justify-between">
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                {{ label }}
                <span v-if="required" class="text-rose-500 font-bold">*</span>
            </label>
            <span v-if="hint" class="text-[11px] text-zinc-400">
                {{ hint }}
            </span>
        </div>

        <!-- Hidden Input -->
        <input
            ref="fileInputRef"
            type="file"
            :accept="allowedExtensions.map((e) => `.${e}`).join(',')"
            :disabled="disabled"
            class="hidden"
            @change="(e) => handleFiles((e.target as HTMLInputElement).files)"
        />

        <!-- Empty / Dropzone State -->
        <div
            v-if="!hasFile"
            class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-8 text-center transition-all duration-200 cursor-pointer"
            :class="[
                isDragging
                    ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20 dark:bg-primary-950/20'
                    : 'border-zinc-300 bg-zinc-50/50 hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/40 dark:hover:border-zinc-600',
                activeError ? 'border-rose-400 bg-rose-50/20 dark:border-rose-700' : '',
                disabled ? 'cursor-not-allowed opacity-60' : '',
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="openFileDialog"
        >
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white shadow-xs border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700 text-primary-600 dark:text-primary-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>

            <div class="mt-3">
                <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                    <span class="text-primary-600 hover:underline dark:text-primary-400">Click to upload</span>
                    or drag & drop
                </p>
                <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                    {{ allowedExtensions.map((e) => e.toUpperCase()).join(', ') }} (Max {{ maxSizeMb }}MB)
                </p>
            </div>

            <div v-if="enableCompression" class="mt-2.5 flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Smart client-side compression enabled</span>
            </div>
        </div>

        <!-- Selected File State -->
        <div
            v-else
            class="relative rounded-xl border border-zinc-200 bg-white p-3.5 shadow-xs transition dark:border-zinc-800 dark:bg-zinc-900"
            :class="{ 'border-rose-400 ring-1 ring-rose-400/20': activeError }"
        >
            <div class="flex items-center gap-3">
                <!-- Preview Thumbnail / File Icon -->
                <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 flex items-center justify-center">
                    <img
                        v-if="previewUrl || (currentFileUrl && isImageFile)"
                        :src="previewUrl || currentFileUrl!"
                        alt="Preview"
                        class="h-full w-full object-cover"
                    />
                    <svg
                        v-else-if="isPdfFile"
                        class="h-6 w-6 text-rose-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                    </svg>
                    <svg
                        v-else
                        class="h-6 w-6 text-indigo-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>

                <!-- Info -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="truncate text-xs font-semibold text-zinc-900 dark:text-zinc-100" :title="displayFileName">
                            {{ displayFileName }}
                        </p>
                        <span v-if="!modelValue && currentFileName" class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                            Current
                        </span>
                    </div>

                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                        <span>{{ displayFileSize }}</span>

                        <!-- Compression Savings Badge -->
                        <span
                            v-if="compressionInfo?.wasCompressed"
                            class="inline-flex items-center gap-1 rounded bg-emerald-50 px-1.5 py-0.2 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                        >
                            <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Compressed (-{{ compressionInfo.reductionPercentage }}%)
                        </span>

                        <span v-if="isCompressing" class="inline-flex items-center gap-1 text-[11px] text-amber-600 dark:text-amber-400">
                            <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Optimizing image...
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="disabled"
                        class="rounded-lg border border-zinc-200 p-1.5 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 cursor-pointer"
                        title="Replace file"
                        @click="openFileDialog"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        :disabled="disabled"
                        class="rounded-lg border border-zinc-200 p-1.5 text-zinc-500 transition hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-rose-950/40 dark:hover:border-rose-800 dark:hover:text-rose-400 cursor-pointer"
                        title="Remove file"
                        @click="removeFile"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <p v-if="activeError" class="text-xs text-rose-500 font-medium">
            {{ activeError }}
        </p>
    </div>
</template>
