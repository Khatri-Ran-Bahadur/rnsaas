<script setup lang="ts">
import { ref, computed } from 'vue';
import MediaLibraryModal from './MediaLibraryModal.vue';

interface Props {
    modelValue?: string | string[];
    label?: string;
    multiple?: boolean;
    placeholder?: string;
    showPreview?: boolean;
    readOnly?: boolean;
    disabled?: boolean;
    id?: string;
    required?: boolean;
    tenantId?: number | null;
    endpointPrefix?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    label: undefined,
    multiple: false,
    placeholder: 'Select file...',
    showPreview: true,
    readOnly: false,
    disabled: false,
    id: undefined,
    required: false,
    tenantId: null,
    endpointPrefix: '/superadmin/media',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | string[]): void;
    (e: 'change', value: string | string[]): void;
    (e: 'select', value: any): void;
}>();

const isModalOpen = ref(false);

const safeValue = computed(() => {
    if (props.multiple) {
        return Array.isArray(props.modelValue)
            ? props.modelValue
            : props.modelValue ? [props.modelValue] : [];
    }
    return Array.isArray(props.modelValue)
        ? props.modelValue[0] || ''
        : props.modelValue || '';
});

const displayString = computed(() => {
    if (props.multiple) {
        return Array.isArray(safeValue.value) ? safeValue.value.join(', ') : '';
    }
    return safeValue.value as string;
});

const previewUrls = computed(() => {
    if (props.multiple) {
        return (Array.isArray(safeValue.value) ? safeValue.value : []).filter(Boolean);
    }
    return safeValue.value ? [safeValue.value as string] : [];
});

const getFileType = (url: string): 'image' | 'video' | 'audio' | 'document' | 'file' => {
    const ext = url.split('.').pop()?.toLowerCase() || '';
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'].includes(ext)) {
        return 'image';
    }
    if (['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'].includes(ext)) {
        return 'video';
    }
    if (['mp3', 'wav', 'aac', 'flac'].includes(ext)) {
        return 'audio';
    }
    if (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'].includes(ext)) {
        return 'document';
    }
    return 'file';
};

const handleSelect = (selected: any) => {
    let result: string | string[];
    if (props.multiple) {
        const urls = Array.isArray(selected) ? selected.map((s: any) => s.url || s) : [selected.url || selected];
        result = urls;
    } else {
        const item = Array.isArray(selected) ? selected[0] : selected;
        result = item ? (item.url || item) : '';
    }

    emit('update:modelValue', result);
    emit('change', result);
    emit('select', selected);
    isModalOpen.value = false;
};

const handleClear = () => {
    const emptyVal = props.multiple ? [] : '';
    emit('update:modelValue', emptyVal);
    emit('change', emptyVal);
};

const handleRemoveItem = (index: number) => {
    if (props.multiple && Array.isArray(safeValue.value)) {
        const updated = [...safeValue.value];
        updated.splice(index, 1);
        emit('update:modelValue', updated);
        emit('change', updated);
    } else {
        handleClear();
    }
};
</script>

<template>
    <div class="space-y-1.5">
        <label
            v-if="label"
            :for="id"
            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <div class="flex gap-2">
            <div class="relative flex-1">
                <input
                    :id="id"
                    type="text"
                    :value="displayString"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    :readonly="readOnly || multiple"
                    :required="required"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm text-zinc-900 placeholder-zinc-400 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder-zinc-500"
                    @input="!multiple && emit('update:modelValue', ($event.target as HTMLInputElement).value)"
                />
            </div>

            <button
                type="button"
                :disabled="disabled || readOnly"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-xs transition-colors hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                @click="isModalOpen = true"
            >
                <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Browse</span>
            </button>

            <button
                v-if="previewUrls.length > 0"
                type="button"
                :disabled="disabled || readOnly"
                title="Clear selection"
                class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-zinc-200"
                @click="handleClear"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Previews -->
        <div v-if="showPreview && previewUrls.length > 0" class="mt-2.5 grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
            <div
                v-for="(url, index) in previewUrls"
                :key="index"
                class="group relative aspect-square overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 shadow-xs dark:border-zinc-700 dark:bg-zinc-800"
            >
                <template v-if="getFileType(url) === 'image'">
                    <img
                        :src="url"
                        :alt="'Preview ' + (index + 1)"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                </template>

                <template v-else-if="getFileType(url) === 'video'">
                    <div class="flex h-full w-full flex-col items-center justify-center p-2 text-zinc-500">
                        <svg class="h-6 w-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="mt-1 text-[10px] uppercase font-semibold">Video</span>
                    </div>
                </template>

                <template v-else>
                    <div class="flex h-full w-full flex-col items-center justify-center p-2 text-zinc-500">
                        <svg class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="mt-1 truncate max-w-[80px] text-[10px] uppercase font-semibold">
                            {{ url.split('.').pop() }}
                        </span>
                    </div>
                </template>

                <!-- Remove single preview overlay button -->
                <button
                    v-if="!disabled && !readOnly"
                    type="button"
                    title="Remove item"
                    class="absolute top-1 right-1 rounded-full bg-zinc-900/75 p-1 text-white opacity-0 transition-opacity hover:bg-rose-600 group-hover:opacity-100"
                    @click="handleRemoveItem(index)"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Embedded Modal Picker -->
        <MediaLibraryModal
            v-if="isModalOpen"
            :show="isModalOpen"
            :multiple="multiple"
            :tenant-id="tenantId"
            :endpoint-prefix="endpointPrefix"
            @close="isModalOpen = false"
            @select="handleSelect"
        />
    </div>
</template>
