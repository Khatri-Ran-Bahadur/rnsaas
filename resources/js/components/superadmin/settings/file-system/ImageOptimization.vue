<script setup lang="ts">
import type { ImageOptimizationConfig, ImageConvertFormat } from '@/types/superadmin/file-system';
import Switch from '@/components/Switch.vue';

const props = defineProps<{
    modelValue: ImageOptimizationConfig;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', val: ImageOptimizationConfig): void;
}>();

const updateField = <K extends keyof ImageOptimizationConfig>(key: K, value: ImageOptimizationConfig[K]) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
};

const formatOptions: Array<{ id: ImageConvertFormat; label: string; desc: string }> = [
    { id: 'original', label: 'Keep Original', desc: 'Preserves the uploaded source image extension.' },
    { id: 'webp', label: 'Convert to WebP', desc: 'Modern web format with superior compression ratio.' },
    { id: 'jpeg', label: 'Convert to JPEG', desc: 'Standard high-compatibility format.' },
];
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 mb-8">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-6">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                        Image Optimization & Compression
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Automatically resize, compress, and generate thumbnails upon upload.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">Enable Compression</span>
                <Switch
                    :model-value="modelValue.enabled"
                    @update:model-value="updateField('enabled', $event)"
                />
            </div>
        </div>

        <div class="space-y-6" :class="{ 'opacity-60 pointer-events-none': !modelValue.enabled }">
            <!-- 1. Compression Quality Slider -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            Compression Quality
                        </label>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Recommended 80% for optimal balance of quality and storage efficiency.</p>
                    </div>
                    <span class="rounded-md bg-zinc-100 px-2 py-0.5 font-mono text-xs font-bold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700">
                        {{ modelValue.quality }}%
                    </span>
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-[10px] text-zinc-400 font-medium">Low Size (50%)</span>
                    <input
                        :value="modelValue.quality"
                        type="range"
                        min="40"
                        max="100"
                        step="5"
                        class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-zinc-200 accent-primary-600 dark:bg-zinc-700"
                        @input="updateField('quality', Number(($event.target as HTMLInputElement).value))"
                    />
                    <span class="text-[10px] text-zinc-400 font-medium">Lossless (100%)</span>
                </div>
            </div>

            <!-- 2. Max Dimensions (Width & Height) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Maximum Image Width
                    </label>
                    <div class="relative">
                        <input
                            :value="modelValue.maxWidth"
                            type="number"
                            min="400"
                            max="7680"
                            step="128"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-10 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            @input="updateField('maxWidth', Number(($event.target as HTMLInputElement).value))"
                        />
                        <span class="absolute right-3 top-2 text-xs font-medium text-zinc-400">px</span>
                    </div>
                    <p class="text-[11px] text-zinc-400 mt-1">Images wider than this threshold will be downscaled.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Maximum Image Height
                    </label>
                    <div class="relative">
                        <input
                            :value="modelValue.maxHeight"
                            type="number"
                            min="400"
                            max="7680"
                            step="128"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-10 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            @input="updateField('maxHeight', Number(($event.target as HTMLInputElement).value))"
                        />
                        <span class="absolute right-3 top-2 text-xs font-medium text-zinc-400">px</span>
                    </div>
                    <p class="text-[11px] text-zinc-400 mt-1">Aspect ratio is strictly preserved during resize.</p>
                </div>
            </div>

            <!-- 3. Convert Images To -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                    Convert Images To
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label
                        v-for="fmt in formatOptions"
                        :key="fmt.id"
                        :class="[
                            'flex flex-col p-3 rounded-xl border cursor-pointer transition-all',
                            modelValue.convertFormat === fmt.id
                                ? 'border-primary-500 bg-primary-50/40 text-primary-950 dark:bg-primary-950/30 dark:border-primary-800 dark:text-white ring-1 ring-primary-500/30'
                                : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-800/60 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300',
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold">{{ fmt.label }}</span>
                            <input
                                type="radio"
                                name="convertFormat"
                                :value="fmt.id"
                                :checked="modelValue.convertFormat === fmt.id"
                                class="text-primary-600 focus:ring-primary-500"
                                @change="updateField('convertFormat', fmt.id)"
                            />
                        </div>
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ fmt.desc }}</span>
                    </label>
                </div>
            </div>

            <!-- 4. Thumbnail Generation -->
            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-white">Generate Thumbnail</span>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Creates a small optimized thumbnail for quick previewing across tables and avatars.</p>
                    </div>
                    <Switch
                        :model-value="modelValue.generateThumbnail"
                        @update:model-value="updateField('generateThumbnail', $event)"
                    />
                </div>

                <div v-if="modelValue.generateThumbnail" class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                            Thumbnail Width
                        </label>
                        <div class="relative">
                            <input
                                :value="modelValue.thumbnailWidth"
                                type="number"
                                min="50"
                                max="1000"
                                class="w-full rounded-md border border-zinc-300 bg-white px-2.5 py-1.5 pr-8 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                @input="updateField('thumbnailWidth', Number(($event.target as HTMLInputElement).value))"
                            />
                            <span class="absolute right-2.5 top-1.5 text-[11px] text-zinc-400">px</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                            Thumbnail Height
                        </label>
                        <div class="relative">
                            <input
                                :value="modelValue.thumbnailHeight"
                                type="number"
                                min="50"
                                max="1000"
                                class="w-full rounded-md border border-zinc-300 bg-white px-2.5 py-1.5 pr-8 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                @input="updateField('thumbnailHeight', Number(($event.target as HTMLInputElement).value))"
                            />
                            <span class="absolute right-2.5 top-1.5 text-[11px] text-zinc-400">px</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informative Note -->
            <div class="flex items-center gap-2 rounded-lg bg-primary-50/60 px-3.5 py-2.5 border border-primary-200/60 text-xs text-primary-800 dark:bg-primary-950/40 dark:border-primary-900/40 dark:text-primary-300">
                <svg class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Image optimization reduces storage usage and improves application performance.</span>
            </div>
        </div>
    </div>
</template>
