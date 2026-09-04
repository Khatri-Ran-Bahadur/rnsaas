<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

export interface MediaItem {
    id: number;
    tenant_id?: number | null;
    name: string;
    file_name: string;
    url: string;
    thumb_url: string;
    size: number;
    human_size?: string;
    mime_type: string;
    file_type?: string;
    directory_id: number | null;
    created_at: string;
}

export interface DirectoryItem {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
    media_count?: number;
    children?: DirectoryItem[];
}

interface Props {
    show: boolean;
    multiple?: boolean;
    tenantId?: number | null;
    endpointPrefix?: string;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    multiple: false,
    tenantId: null,
    endpointPrefix: '/superadmin/media',
});

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'select', value: MediaItem | MediaItem[]): void;
}>();

// State
const loading = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const media = ref<MediaItem[]>([]);
const directories = ref<DirectoryItem[]>([]);
const currentDirectory = ref<number | null>(null);
const searchTerm = ref('');
const filterType = ref<'all' | 'image' | 'video' | 'document' | 'audio'>('all');
const sortBy = ref<'date' | 'name' | 'size'>('date');
const viewMode = ref<'grid' | 'list'>('grid');
const selectedItems = ref<MediaItem[]>([]);
const isDragOver = ref(false);
const showCreateFolder = ref(false);
const newFolderName = ref('');
const fileInputRef = ref<HTMLInputElement | null>(null);

function getCsrfToken(): string {
    const fromPage = (page.props as any)?.csrf_token;
    if (fromPage) return fromPage;
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) return meta;
    const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : '';
}

function getXsrfCookie(): string {
    const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : '';
}

// Fetch Media from API
const fetchMedia = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (currentDirectory.value !== null) {
            params.append('directory_id', currentDirectory.value.toString());
        }
        if (props.tenantId !== null) {
            params.append('tenant_id', props.tenantId.toString());
        }

        const url = `${props.endpointPrefix}/index?${params.toString()}`;
        const res = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (res.ok) {
            const data = await res.json();
            media.value = Array.isArray(data.media) ? data.media : [];
            directories.value = Array.isArray(data.directories) ? data.directories : [];
        }
    } catch (err) {
        console.error('Failed to load media:', err);
    } finally {
        loading.value = false;
    }
};

// Filtered and sorted media
const filteredMedia = computed(() => {
    return media.value
        .filter((item) => {
            const matchesSearch =
                !searchTerm.value ||
                item.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                item.file_name.toLowerCase().includes(searchTerm.value.toLowerCase());

            const matchesType =
                filterType.value === 'all' ||
                item.file_type === filterType.value;

            return matchesSearch && matchesType;
        })
        .sort((a, b) => {
            if (sortBy.value === 'name') {
                return a.name.localeCompare(b.name);
            }
            if (sortBy.value === 'size') {
                return b.size - a.size;
            }
            return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        });
});

// Selection handling
const isSelected = (item: MediaItem) => {
    return selectedItems.value.some((s) => s.id === item.id);
};

const toggleSelect = (item: MediaItem) => {
    if (props.multiple) {
        const idx = selectedItems.value.findIndex((s) => s.id === item.id);
        if (idx > -1) {
            selectedItems.value.splice(idx, 1);
        } else {
            selectedItems.value.push(item);
        }
    } else {
        if (isSelected(item)) {
            selectedItems.value = [];
        } else {
            selectedItems.value = [item];
        }
    }
};

const handleDoubleClick = (item: MediaItem) => {
    emit('select', props.multiple ? [item] : item);
    emit('close');
};

const confirmSelection = () => {
    if (selectedItems.value.length === 0) return;
    emit('select', props.multiple ? selectedItems.value : selectedItems.value[0]);
    emit('close');
};

// Upload handling
const handleFilesUpload = async (files: FileList | File[]) => {
    if (!files || files.length === 0) return;

    const token = getCsrfToken();
    const xsrf = getXsrfCookie();

    const formData = new FormData();
    if (token) {
        formData.append('_token', token);
    }
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }

    if (currentDirectory.value !== null) {
        formData.append('directory_id', currentDirectory.value.toString());
    }
    if (props.tenantId !== null) {
        formData.append('tenant_id', props.tenantId.toString());
    }

    uploading.value = true;
    uploadProgress.value = 30;

    try {
        const headers: Record<string, string> = {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        };
        if (token) headers['X-CSRF-TOKEN'] = token;
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

        const res = await fetch(`${props.endpointPrefix}/batch`, {
            method: 'POST',
            headers,
            body: formData,
            credentials: 'same-origin',
        });

        uploadProgress.value = 90;

        if (res.ok) {
            const data = await res.json();
            await fetchMedia();
            if (data.media && data.media.length > 0) {
                if (props.multiple) {
                    selectedItems.value.push(...data.media);
                } else {
                    selectedItems.value = [data.media[0]];
                }
            }
        }
    } catch (e) {
        console.error('Upload failed:', e);
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    }
};

const onFileInputChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files) {
        handleFilesUpload(target.files);
    }
};

const onDrop = (e: DragEvent) => {
    isDragOver.value = false;
    if (e.dataTransfer?.files) {
        handleFilesUpload(e.dataTransfer.files);
    }
};

// Folder Creation
const createFolder = async () => {
    if (!newFolderName.value.trim()) return;
    try {
        const token = getCsrfToken();
        const xsrf = getXsrfCookie();
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        };
        if (token) headers['X-CSRF-TOKEN'] = token;
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

        const res = await fetch(`${props.endpointPrefix}/directories`, {
            method: 'POST',
            headers,
            body: JSON.stringify({
                _token: token,
                name: newFolderName.value.trim(),
                parent_id: currentDirectory.value,
                tenant_id: props.tenantId,
            }),
            credentials: 'same-origin',
        });

        if (res.ok) {
            newFolderName.value = '';
            showCreateFolder.value = false;
            await fetchMedia();
        }
    } catch (e) {
        console.error('Error creating folder:', e);
    }
};

// Keyboard listener
const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        emit('close');
    }
};

watch(
    () => props.show,
    (val) => {
        if (val) {
            fetchMedia();
            selectedItems.value = [];
            window.addEventListener('keydown', handleKeyDown);
            document.body.style.overflow = 'hidden';
        } else {
            window.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = '';
        }
    },
    { immediate: true }
);

watch(currentDirectory, () => {
    fetchMedia();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-zinc-950/70 backdrop-blur-xs transition-opacity"
                    @click="emit('close')"
                />

                <!-- Modal Dialog -->
                <div
                    class="relative flex h-[88vh] w-full max-w-5xl flex-col rounded-2xl border border-zinc-200 bg-white shadow-2xl transition-all dark:border-zinc-800 dark:bg-zinc-900"
                    @dragover.prevent="isDragOver = true"
                    @dragleave.prevent="isDragOver = false"
                    @drop.prevent="onDrop"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                    Media Library
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Select or upload media assets
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Hidden File Input -->
                            <input
                                ref="fileInputRef"
                                type="file"
                                multiple
                                class="hidden"
                                @change="onFileInputChange"
                            />

                            <!-- Upload Button -->
                            <button
                                type="button"
                                :disabled="uploading"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-primary-500 disabled:opacity-50"
                                @click="fileInputRef?.click()"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>Upload Files</span>
                            </button>

                            <!-- Close Button -->
                            <button
                                type="button"
                                class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                @click="emit('close')"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body (Sidebar + Content) -->
                    <div class="flex min-h-0 flex-1 overflow-hidden">
                        <!-- Sidebar: Folders -->
                        <div class="w-56 shrink-0 border-r border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-900/50 flex flex-col">
                            <div class="flex items-center justify-between pb-2">
                                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Folders</span>
                                <button
                                    type="button"
                                    class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                                    @click="showCreateFolder = !showCreateFolder"
                                >
                                    + New
                                </button>
                            </div>

                            <!-- Inline create folder form -->
                            <div v-if="showCreateFolder" class="mb-3 space-y-1.5 rounded-lg border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-800">
                                <input
                                    v-model="newFolderName"
                                    type="text"
                                    placeholder="Folder name"
                                    class="w-full rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-100"
                                    @keyup.enter="createFolder"
                                />
                                <div class="flex justify-end gap-1">
                                    <button
                                        type="button"
                                        class="rounded px-2 py-0.5 text-[11px] text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                        @click="showCreateFolder = false"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded bg-primary-600 px-2 py-0.5 text-[11px] font-medium text-white hover:bg-primary-500"
                                        @click="createFolder"
                                    >
                                        Create
                                    </button>
                                </div>
                            </div>

                            <!-- Directory List -->
                            <nav class="space-y-1 overflow-y-auto flex-1 text-xs">
                                <button
                                    type="button"
                                    :class="[
                                        'flex w-full items-center justify-between rounded-lg px-2.5 py-2 font-medium transition',
                                        currentDirectory === null
                                            ? 'bg-primary-100/80 text-primary-700 font-semibold dark:bg-primary-950/60 dark:text-primary-300'
                                            : 'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-400 dark:hover:bg-zinc-800'
                                    ]"
                                    @click="currentDirectory = null"
                                >
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                        <span>All Files</span>
                                    </div>
                                    <span class="rounded-full bg-zinc-200/80 px-1.5 py-0.5 text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ media.length }}
                                    </span>
                                </button>

                                <div v-for="dir in directories" :key="dir.id" class="space-y-1">
                                    <button
                                        type="button"
                                        :class="[
                                            'flex w-full items-center justify-between rounded-lg px-2.5 py-2 font-medium transition',
                                            currentDirectory === dir.id
                                                ? 'bg-primary-100/80 text-primary-700 font-semibold dark:bg-primary-950/60 dark:text-primary-300'
                                                : 'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-400 dark:hover:bg-zinc-800'
                                        ]"
                                        @click="currentDirectory = dir.id"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                            </svg>
                                            <span class="truncate">{{ dir.name }}</span>
                                        </div>
                                        <span class="rounded-full bg-zinc-200/80 px-1.5 py-0.5 text-[10px] text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                            {{ dir.media_count || 0 }}
                                        </span>
                                    </button>

                                    <!-- Subdirectories -->
                                    <button
                                        v-for="sub in dir.children || []"
                                        :key="sub.id"
                                        type="button"
                                        :class="[
                                            'flex w-full items-center justify-between rounded-lg pl-6 pr-2.5 py-1.5 font-medium transition',
                                            currentDirectory === sub.id
                                                ? 'bg-primary-100/80 text-primary-700 font-semibold dark:bg-primary-950/60 dark:text-primary-300'
                                                : 'text-zinc-500 hover:bg-zinc-200/50 dark:text-zinc-400 dark:hover:bg-zinc-800'
                                        ]"
                                        @click="currentDirectory = sub.id"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="text-zinc-400">↳</span>
                                            <span class="truncate">{{ sub.name }}</span>
                                        </div>
                                        <span class="text-[10px] text-zinc-400">
                                            {{ sub.media_count || 0 }}
                                        </span>
                                    </button>
                                </div>
                            </nav>
                        </div>

                        <!-- Main Content Area -->
                        <div class="flex flex-1 flex-col overflow-hidden bg-white dark:bg-zinc-900">
                            <!-- Toolbar -->
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800">
                                <!-- Search -->
                                <div class="relative w-64">
                                    <input
                                        v-model="searchTerm"
                                        type="text"
                                        placeholder="Search files..."
                                        class="w-full rounded-lg border border-zinc-300 bg-white py-1.5 pl-8 pr-3 text-xs text-zinc-900 placeholder-zinc-400 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                                    />
                                    <svg class="absolute left-2.5 top-2 h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>

                                <!-- Filters -->
                                <div class="flex items-center gap-2">
                                    <!-- Type buttons -->
                                    <div class="inline-flex rounded-lg border border-zinc-200 p-0.5 text-xs dark:border-zinc-700">
                                        <button
                                            v-for="t in ['all', 'image', 'video', 'document', 'audio'] as const"
                                            :key="t"
                                            type="button"
                                            :class="[
                                                'rounded-md px-2.5 py-1 capitalize transition',
                                                filterType === t
                                                    ? 'bg-zinc-900 font-medium text-white dark:bg-zinc-100 dark:text-zinc-900'
                                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100'
                                            ]"
                                            @click="filterType = t"
                                        >
                                            {{ t }}
                                        </button>
                                    </div>

                                    <!-- Sort Dropdown -->
                                    <select
                                        v-model="sortBy"
                                        class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                    >
                                        <option value="date">Date</option>
                                        <option value="name">Name</option>
                                        <option value="size">Size</option>
                                    </select>

                                    <!-- View Mode -->
                                    <div class="inline-flex rounded-lg border border-zinc-200 p-0.5 dark:border-zinc-700">
                                        <button
                                            type="button"
                                            :class="[
                                                'rounded-md p-1',
                                                viewMode === 'grid' ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-400'
                                            ]"
                                            @click="viewMode = 'grid'"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            :class="[
                                                'rounded-md p-1',
                                                viewMode === 'list' ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-400'
                                            ]"
                                            @click="viewMode = 'list'"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload drag overlay indicator -->
                            <div
                                v-if="isDragOver"
                                class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-primary-600/10 backdrop-blur-xs border-2 border-dashed border-primary-500 rounded-2xl pointer-events-none"
                            >
                                <svg class="h-12 w-12 text-primary-600 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm font-semibold text-primary-700">Drop files anywhere to upload</p>
                            </div>

                            <!-- Upload Progress Bar -->
                            <div v-if="uploading" class="h-1 w-full bg-zinc-100 dark:bg-zinc-800">
                                <div
                                    class="h-full bg-primary-600 transition-all duration-300"
                                    :style="{ width: uploadProgress + '%' }"
                                />
                            </div>

                            <!-- Media Items List/Grid Container -->
                            <div class="flex-1 overflow-y-auto p-4">
                                <!-- Loading State -->
                                <div v-if="loading" class="flex h-64 items-center justify-center">
                                    <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary-600 border-t-transparent" />
                                </div>

                                <!-- Empty State -->
                                <div
                                    v-else-if="filteredMedia.length === 0"
                                    class="flex h-64 flex-col items-center justify-center text-center"
                                >
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800 text-zinc-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300">No media files found</p>
                                    <p class="mt-1 text-xs text-zinc-500">Upload new files or drag and drop here</p>
                                </div>

                                <!-- Grid View -->
                                <div
                                    v-else-if="viewMode === 'grid'"
                                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                                >
                                    <div
                                        v-for="item in filteredMedia"
                                        :key="item.id"
                                        :class="[
                                            'group relative flex flex-col overflow-hidden rounded-xl border bg-white transition-all cursor-pointer dark:bg-zinc-800/80',
                                            isSelected(item)
                                                ? 'border-primary-500 ring-2 ring-primary-500/20 shadow-md'
                                                : 'border-zinc-200 hover:border-zinc-300 hover:shadow-xs dark:border-zinc-700 dark:hover:border-zinc-600'
                                        ]"
                                        @click="toggleSelect(item)"
                                        @dblclick="handleDoubleClick(item)"
                                    >
                                        <!-- Thumbnail -->
                                        <div class="relative aspect-square w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                            <img
                                                v-if="item.file_type === 'image'"
                                                :src="item.thumb_url || item.url"
                                                :alt="item.name"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                                loading="lazy"
                                            />
                                            <div v-else-if="item.file_type === 'video'" class="flex flex-col items-center justify-center text-zinc-400">
                                                <svg class="h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div v-else class="flex flex-col items-center justify-center text-zinc-400">
                                                <svg class="h-8 w-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="mt-1 text-[9px] font-bold uppercase text-zinc-500">
                                                    {{ item.name.split('.').pop() }}
                                                </span>
                                            </div>

                                            <!-- Checkmark badge -->
                                            <div
                                                v-if="isSelected(item)"
                                                class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary-600 text-white shadow-md"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Metadata -->
                                        <div class="p-2.5">
                                            <p class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-200" :title="item.name">
                                                {{ item.name }}
                                            </p>
                                            <p class="mt-0.5 text-[10px] text-zinc-400">
                                                {{ item.human_size }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- List View -->
                                <div v-else class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                                    <div
                                        v-for="item in filteredMedia"
                                        :key="item.id"
                                        :class="[
                                            'flex items-center justify-between p-3 cursor-pointer transition',
                                            isSelected(item) ? 'bg-primary-50/70 dark:bg-primary-950/40' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50'
                                        ]"
                                        @click="toggleSelect(item)"
                                        @dblclick="handleDoubleClick(item)"
                                    >
                                        <div class="flex items-center gap-3 truncate">
                                            <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                                <img
                                                    v-if="item.file_type === 'image'"
                                                    :src="item.thumb_url || item.url"
                                                    :alt="item.name"
                                                    class="h-full w-full object-cover"
                                                />
                                                <svg v-else class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="truncate">
                                                <p class="truncate text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ item.name }}</p>
                                                <p class="text-[10px] text-zinc-400">{{ item.human_size }} • {{ new Date(item.created_at).toLocaleDateString() }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <div
                                                v-if="isSelected(item)"
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-white"
                                            >
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between border-t border-zinc-200 px-6 py-3.5 dark:border-zinc-800">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            <span v-if="selectedItems.length > 0" class="font-medium text-primary-600">
                                {{ selectedItems.length }} file(s) selected
                            </span>
                            <span v-else>
                                Double click an item to select immediately
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                                @click="emit('close')"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                :disabled="selectedItems.length === 0"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="confirmSelection"
                            >
                                Choose {{ selectedItems.length > 0 ? `(${selectedItems.length})` : '' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
