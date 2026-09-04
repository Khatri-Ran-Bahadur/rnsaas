<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Badge from '@/components/Badge.vue';

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

// Data States
const media = ref<MediaItem[]>([]);
const directories = ref<DirectoryItem[]>([]);
const currentDirectory = ref<number | null>(null);
const loading = ref(false);
const search = ref('');
const filterType = ref<'all' | 'image' | 'video' | 'document' | 'audio' | 'archive'>('all');
const sortBy = ref<'date' | 'name' | 'size'>('date');
const sortOrder = ref<'asc' | 'desc'>('desc');
const viewMode = ref<'grid' | 'list'>('grid');
const selectedIds = ref<number[]>([]);
const isDragOver = ref(false);

// Modals
const showUploadModal = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const fileInputRef = ref<HTMLInputElement | null>(null);

const showCreateFolderModal = ref(false);
const newFolderName = ref('');

const showRenameFolderModal = ref(false);
const folderToRename = ref<DirectoryItem | null>(null);
const renameFolderName = ref('');

const showPreviewModal = ref(false);
const previewItem = ref<MediaItem | null>(null);

const showMoveModal = ref(false);
const targetDirectoryId = ref<number | null>(null);

const showDeleteConfirmModal = ref(false);
const deleteTarget = ref<{ type: 'single-file' | 'bulk-files' | 'folder'; id?: number; name?: string } | null>(null);

const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

function showToast(message: string, type: 'success' | 'error' = 'success') {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 3500);
}

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

// Fetch API
const fetchMedia = async (showLoader = true) => {
    if (showLoader) loading.value = true;
    try {
        const params = new URLSearchParams();
        if (currentDirectory.value !== null) {
            params.append('directory_id', currentDirectory.value.toString());
        }

        const res = await fetch(`/superadmin/media/index?${params.toString()}`, {
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
        } else {
            showToast('Failed to load media assets.', 'error');
        }
    } catch (err) {
        showToast('Error connecting to media server.', 'error');
    } finally {
        if (showLoader) loading.value = false;
    }
};

// Filtered and sorted
const filteredMedia = computed(() => {
    return media.value
        .filter((item) => {
            const matchesSearch =
                !search.value ||
                item.name.toLowerCase().includes(search.value.toLowerCase()) ||
                item.file_name.toLowerCase().includes(search.value.toLowerCase());

            const matchesType =
                filterType.value === 'all' || item.file_type === filterType.value;

            return matchesSearch && matchesType;
        })
        .sort((a, b) => {
            let result = 0;
            if (sortBy.value === 'name') {
                result = a.name.localeCompare(b.name);
            } else if (sortBy.value === 'size') {
                result = a.size - b.size;
            } else {
                result = new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
            }
            return sortOrder.value === 'asc' ? result : -result;
        });
});

// Stats
const totalBytes = computed(() => media.value.reduce((acc, item) => acc + (item.size || 0), 0));
const totalFormattedStorage = computed(() => {
    const bytes = totalBytes.value;
    if (bytes <= 0) return '0 MB';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const p = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, p)).toFixed(1) + ' ' + units[p];
});

// Active directory helper
const currentDirectoryItem = computed(() => {
    if (currentDirectory.value === null) return null;
    return directories.value.find((d) => d.id === currentDirectory.value);
});

// Selection handling
const isSelected = (id: number) => selectedIds.value.includes(id);

const toggleSelection = (id: number) => {
    const idx = selectedIds.value.indexOf(id);
    if (idx > -1) {
        selectedIds.value.splice(idx, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const toggleSelectAll = () => {
    if (selectedIds.value.length === filteredMedia.value.length) {
        selectedIds.value = [];
    } else {
        selectedIds.value = filteredMedia.value.map((m) => m.id);
    }
};

// Actions
const copyPublicUrl = async (url: string) => {
    try {
        await navigator.clipboard.writeText(url);
        showToast('Media URL copied to clipboard!');
    } catch {
        showToast('Unable to copy URL automatically.', 'error');
    }
};

const handleUploadFiles = async (files: FileList | File[]) => {
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

    uploading.value = true;
    uploadProgress.value = 35;

    try {
        const headers: Record<string, string> = {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        };
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }
        if (xsrf) {
            headers['X-XSRF-TOKEN'] = xsrf;
        }

        const res = await fetch('/superadmin/media/batch', {
            method: 'POST',
            headers,
            body: formData,
            credentials: 'same-origin',
        });

        uploadProgress.value = 95;

        if (res.ok) {
            const data = await res.json();
            showToast(data.message || 'Files uploaded successfully.');
            showUploadModal.value = false;
            await fetchMedia(false);
        } else {
            const err = await res.json();
            showToast(err.message || 'Upload failed. Check file size limits.', 'error');
        }
    } catch {
        showToast('Network error while uploading files.', 'error');
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
        if (fileInputRef.value) fileInputRef.value.value = '';
    }
};

// Create Folder
const handleCreateFolder = async () => {
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

        const res = await fetch('/superadmin/media/directories', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                _token: token,
                name: newFolderName.value.trim(),
                parent_id: currentDirectory.value,
            }),
            credentials: 'same-origin',
        });

        if (res.ok) {
            newFolderName.value = '';
            showCreateFolderModal.value = false;
            showToast('Folder created successfully.');
            await fetchMedia(false);
        } else {
            showToast('Failed to create folder.', 'error');
        }
    } catch {
        showToast('Error creating folder.', 'error');
    }
};

// Rename Folder
const openRenameFolderModal = (dir: DirectoryItem) => {
    folderToRename.value = dir;
    renameFolderName.value = dir.name;
    showRenameFolderModal.value = true;
};

const handleRenameFolder = async () => {
    if (!folderToRename.value || !renameFolderName.value.trim()) return;

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

        const res = await fetch(`/superadmin/media/directories/${folderToRename.value.id}`, {
            method: 'PUT',
            headers,
            body: JSON.stringify({
                _token: token,
                name: renameFolderName.value.trim(),
            }),
            credentials: 'same-origin',
        });

        if (res.ok) {
            showRenameFolderModal.value = false;
            showToast('Folder renamed successfully.');
            await fetchMedia(false);
        } else {
            showToast('Failed to rename folder.', 'error');
        }
    } catch {
        showToast('Error renaming folder.', 'error');
    }
};

// Move selected items to folder
const handleMoveItems = async () => {
    if (selectedIds.value.length === 0) return;

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

        for (const id of selectedIds.value) {
            await fetch(`/superadmin/media/${id}/directory`, {
                method: 'PATCH',
                headers,
                body: JSON.stringify({
                    _token: token,
                    directory_id: targetDirectoryId.value,
                }),
                credentials: 'same-origin',
            });
        }
        showMoveModal.value = false;
        selectedIds.value = [];
        showToast('File(s) moved successfully.');
        await fetchMedia(false);
    } catch {
        showToast('Error moving files.', 'error');
    }
};

// Delete Handlers
const confirmDeleteSingle = (item: MediaItem) => {
    deleteTarget.value = { type: 'single-file', id: item.id, name: item.name };
    showDeleteConfirmModal.value = true;
};

const confirmDeleteBulk = () => {
    if (selectedIds.value.length === 0) return;
    deleteTarget.value = { type: 'bulk-files', name: `${selectedIds.value.length} selected files` };
    showDeleteConfirmModal.value = true;
};

const confirmDeleteFolder = (dir: DirectoryItem) => {
    deleteTarget.value = { type: 'folder', id: dir.id, name: dir.name };
    showDeleteConfirmModal.value = true;
};

const executeDelete = async () => {
    if (!deleteTarget.value) return;

    try {
        const token = getCsrfToken();
        const xsrf = getXsrfCookie();
        const headers: Record<string, string> = {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        };
        if (token) headers['X-CSRF-TOKEN'] = token;
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

        if (deleteTarget.value.type === 'single-file' && deleteTarget.value.id) {
            const res = await fetch(`/superadmin/media/${deleteTarget.value.id}`, {
                method: 'DELETE',
                headers,
                credentials: 'same-origin',
            });
            if (res.ok) {
                showToast('File deleted successfully.');
                selectedIds.value = selectedIds.value.filter((id) => id !== deleteTarget.value?.id);
                if (previewItem.value?.id === deleteTarget.value.id) {
                    showPreviewModal.value = false;
                }
            }
        } else if (deleteTarget.value.type === 'bulk-files') {
            const res = await fetch('/superadmin/media/batch-destroy', {
                method: 'POST',
                headers: {
                    ...headers,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    _token: token,
                    ids: selectedIds.value,
                }),
                credentials: 'same-origin',
            });
            if (res.ok) {
                showToast(`${selectedIds.value.length} files deleted successfully.`);
                selectedIds.value = [];
            }
        } else if (deleteTarget.value.type === 'folder' && deleteTarget.value.id) {
            const res = await fetch(`/superadmin/media/directories/${deleteTarget.value.id}`, {
                method: 'DELETE',
                headers,
                credentials: 'same-origin',
            });
            if (res.ok) {
                showToast('Folder deleted successfully.');
                if (currentDirectory.value === deleteTarget.value.id) {
                    currentDirectory.value = null;
                }
            }
        }

        showDeleteConfirmModal.value = false;
        await fetchMedia(false);
    } catch {
        showToast('Error executing delete action.', 'error');
    }
};

onMounted(() => {
    fetchMedia();
});
</script>

<template>
    <AdminLayout>
        <Head title="Media Library - SuperAdmin" />

        <!-- Notification Toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="notification"
                :class="[
                    'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium shadow-xl text-white',
                    notification.type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'
                ]"
            >
                <svg v-if="notification.type === 'success'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ notification.message }}</span>
            </div>
        </Transition>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <!-- Breadcrumbs -->
                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>SuperAdmin</span>
                        <span>/</span>
                        <span class="text-zinc-800 font-medium dark:text-zinc-200">Media Library</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Media Library
                    </h1>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        Central storage and management for all uploaded platform and tenant media files.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm font-semibold text-zinc-700 shadow-xs hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        @click="showCreateFolderModal = true"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        <span>New Folder</span>
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-primary-500"
                        @click="showUploadModal = true"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Upload Files</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Media Files</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ media.length }}
                    </p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Storage Used</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ totalFormattedStorage }}
                    </p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Folders</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ directories.length }}
                    </p>
                </div>
            </div>

            <!-- Main Layout: Folder Sidebar + Content -->
            <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 lg:flex-row">
                <!-- Sidebar: Folders -->
                <div class="w-full shrink-0 border-b border-zinc-200 p-4 dark:border-zinc-800 lg:w-64 lg:border-b-0 lg:border-r">
                    <div class="flex items-center justify-between pb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            Directories
                        </span>
                        <button
                            type="button"
                            class="text-xs font-semibold text-primary-600 hover:text-primary-700"
                            @click="showCreateFolderModal = true"
                        >
                            + Add
                        </button>
                    </div>

                    <nav class="space-y-1 text-sm">
                        <!-- All Files Link -->
                        <button
                            type="button"
                            :class="[
                                'flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium transition',
                                currentDirectory === null
                                    ? 'bg-primary-600 text-white font-semibold shadow-xs'
                                    : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'
                            ]"
                            @click="currentDirectory = null; fetchMedia();"
                        >
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <span>All Files</span>
                            </div>
                            <span :class="['rounded-full px-1.5 py-0.5 text-[10px]', currentDirectory === null ? 'bg-primary-700 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400']">
                                {{ media.length }}
                            </span>
                        </button>

                        <!-- Folders -->
                        <div v-for="dir in directories" :key="dir.id" class="group/dir relative space-y-1">
                            <div
                                :class="[
                                    'flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-medium transition cursor-pointer',
                                    currentDirectory === dir.id
                                        ? 'bg-primary-600 text-white font-semibold shadow-xs'
                                        : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'
                                ]"
                                @click="currentDirectory = dir.id; fetchMedia();"
                            >
                                <div class="flex items-center gap-2 truncate">
                                    <svg :class="['h-4 w-4 shrink-0', currentDirectory === dir.id ? 'text-white' : 'text-amber-500']" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                    </svg>
                                    <span class="truncate">{{ dir.name }}</span>
                                </div>

                                <div class="flex items-center gap-1">
                                    <!-- Context Actions -->
                                    <div class="opacity-0 group-hover/dir:opacity-100 transition-opacity flex items-center">
                                        <button
                                            type="button"
                                            title="Rename folder"
                                            class="p-1 hover:text-amber-300"
                                            @click.stop="openRenameFolderModal(dir)"
                                        >
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            title="Delete folder"
                                            class="p-1 hover:text-rose-300"
                                            @click.stop="confirmDeleteFolder(dir)"
                                        >
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span :class="['rounded-full px-1.5 py-0.5 text-[10px]', currentDirectory === dir.id ? 'bg-primary-700 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400']">
                                        {{ dir.media_count || 0 }}
                                    </span>
                                </div>
                            </div>

                            <!-- Nested Children -->
                            <div v-if="dir.children && dir.children.length > 0" class="pl-4 space-y-1">
                                <div
                                    v-for="sub in dir.children"
                                    :key="sub.id"
                                    :class="[
                                        'flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition cursor-pointer',
                                        currentDirectory === sub.id
                                            ? 'bg-primary-600 text-white font-semibold'
                                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'
                                    ]"
                                    @click="currentDirectory = sub.id; fetchMedia();"
                                >
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="text-zinc-400">↳</span>
                                        <span class="truncate">{{ sub.name }}</span>
                                    </div>
                                    <span class="text-[10px] opacity-70">
                                        {{ sub.media_count || 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- Right Side: Toolbar, Bulk Actions, Media Grid/List -->
                <div class="flex min-w-0 flex-1 flex-col">
                    <!-- Toolbar -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by name..."
                                class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-9 pr-3 text-xs text-zinc-900 placeholder-zinc-400 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                            />
                            <svg class="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Type Filters -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex rounded-lg border border-zinc-200 p-1 text-xs dark:border-zinc-700">
                                <button
                                    v-for="t in ['all', 'image', 'video', 'document', 'audio', 'archive'] as const"
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
                                class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-xs text-zinc-700 focus:border-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                            >
                                <option value="date">Date</option>
                                <option value="name">Name</option>
                                <option value="size">Size</option>
                            </select>

                            <!-- View Mode Toggle -->
                            <div class="inline-flex rounded-lg border border-zinc-200 p-1 dark:border-zinc-700">
                                <button
                                    type="button"
                                    :class="[
                                        'rounded p-1 transition',
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
                                        'rounded p-1 transition',
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

                    <!-- Bulk Actions Bar (Shown when items selected) -->
                    <div
                        v-if="selectedIds.length > 0"
                        class="flex items-center justify-between bg-primary-50 px-4 py-2 text-xs text-primary-900 dark:bg-primary-950/40 dark:text-primary-200 border-b border-primary-200 dark:border-primary-900"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-semibold">{{ selectedIds.length }} file(s) selected</span>
                            <button
                                type="button"
                                class="text-primary-700 underline hover:text-primary-800 dark:text-primary-300"
                                @click="toggleSelectAll"
                            >
                                {{ selectedIds.length === filteredMedia.length ? 'Deselect all' : 'Select all' }}
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-primary-300 bg-white px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                                @click="showMoveModal = true"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span>Move</span>
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 font-medium text-white hover:bg-rose-500"
                                @click="confirmDeleteBulk"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Delete ({{ selectedIds.length }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Breadcrumbs & Directory Header -->
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2 text-xs text-zinc-500 dark:border-zinc-800">
                        <div class="flex items-center gap-1.5">
                            <span class="cursor-pointer hover:underline" @click="currentDirectory = null; fetchMedia();">Home</span>
                            <span v-if="currentDirectoryItem">/</span>
                            <span v-if="currentDirectoryItem" class="font-semibold text-zinc-800 dark:text-zinc-200">
                                {{ currentDirectoryItem.name }}
                            </span>
                        </div>
                        <span>{{ filteredMedia.length }} items</span>
                    </div>

                    <!-- Media Grid / List Content -->
                    <div class="flex-1 p-4">
                        <!-- Loader -->
                        <div v-if="loading" class="flex h-64 items-center justify-center">
                            <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary-600 border-t-transparent" />
                        </div>

                        <!-- Empty State -->
                        <div
                            v-else-if="filteredMedia.length === 0"
                            class="flex h-72 flex-col items-center justify-center text-center"
                        >
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200">No media assets found</h3>
                            <p class="mt-1 text-xs text-zinc-500">Upload new files or switch directories.</p>
                            <button
                                type="button"
                                class="mt-4 rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold text-white hover:bg-primary-500"
                                @click="showUploadModal = true"
                            >
                                Upload Files
                            </button>
                        </div>

                        <!-- Grid View -->
                        <div
                            v-else-if="viewMode === 'grid'"
                            class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5"
                        >
                            <div
                                v-for="item in filteredMedia"
                                :key="item.id"
                                :class="[
                                    'group relative flex flex-col overflow-hidden rounded-xl border bg-white shadow-xs transition dark:bg-zinc-800/80',
                                    isSelected(item.id)
                                        ? 'border-primary-500 ring-2 ring-primary-500/20'
                                        : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700'
                                ]"
                            >
                                <!-- Thumbnail Box -->
                                <div
                                    class="relative aspect-square w-full cursor-pointer overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center"
                                    @click="previewItem = item; showPreviewModal = true;"
                                >
                                    <img
                                        v-if="item.file_type === 'image'"
                                        :src="item.thumb_url || item.url"
                                        :alt="item.name"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                    <div v-else-if="item.file_type === 'video'" class="flex flex-col items-center justify-center text-zinc-400">
                                        <svg class="h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div v-else class="flex flex-col items-center justify-center text-zinc-400">
                                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="mt-1 text-[10px] font-bold uppercase text-zinc-500">
                                            {{ item.name.split('.').pop() }}
                                        </span>
                                    </div>

                                    <!-- Selection Checkbox -->
                                    <div
                                        class="absolute top-2 left-2 z-10"
                                        @click.stop="toggleSelection(item.id)"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="isSelected(item.id)"
                                            class="h-4 w-4 rounded border-zinc-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                        />
                                    </div>

                                    <!-- Hover Overlay Actions -->
                                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-around bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button
                                            type="button"
                                            title="Quick Preview"
                                            class="rounded-full bg-white/90 p-1.5 text-zinc-700 hover:bg-white"
                                            @click.stop="previewItem = item; showPreviewModal = true;"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            title="Copy URL"
                                            class="rounded-full bg-white/90 p-1.5 text-zinc-700 hover:bg-white"
                                            @click.stop="copyPublicUrl(item.url)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                        </button>
                                        <a
                                            :href="`/superadmin/media/${item.id}/download`"
                                            title="Download"
                                            class="rounded-full bg-white/90 p-1.5 text-zinc-700 hover:bg-white"
                                            @click.stop
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                        <button
                                            type="button"
                                            title="Delete"
                                            class="rounded-full bg-rose-600 p-1.5 text-white hover:bg-rose-700"
                                            @click.stop="confirmDeleteSingle(item)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div class="p-2.5">
                                    <p class="truncate text-xs font-medium text-zinc-900 dark:text-zinc-100" :title="item.name">
                                        {{ item.name }}
                                    </p>
                                    <div class="mt-1 flex items-center justify-between text-[10px] text-zinc-400">
                                        <span>{{ item.human_size }}</span>
                                        <span>{{ new Date(item.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div v-else class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                            <div
                                v-for="item in filteredMedia"
                                :key="item.id"
                                :class="[
                                    'flex items-center justify-between p-3 transition',
                                    isSelected(item.id) ? 'bg-primary-50/70 dark:bg-primary-950/40' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50'
                                ]"
                            >
                                <div class="flex items-center gap-3 truncate">
                                    <input
                                        type="checkbox"
                                        :checked="isSelected(item.id)"
                                        class="h-4 w-4 rounded border-zinc-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                        @change="toggleSelection(item.id)"
                                    />
                                    <div
                                        class="h-10 w-10 shrink-0 cursor-pointer overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center"
                                        @click="previewItem = item; showPreviewModal = true;"
                                    >
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
                                    <div class="truncate cursor-pointer" @click="previewItem = item; showPreviewModal = true;">
                                        <p class="truncate text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ item.name }}</p>
                                        <p class="text-[10px] text-zinc-400">{{ item.human_size }} • {{ new Date(item.created_at).toLocaleDateString() }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700"
                                        title="Copy URL"
                                        @click="copyPublicUrl(item.url)"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                        </svg>
                                    </button>
                                    <a
                                        :href="`/superadmin/media/${item.id}/download`"
                                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700"
                                        title="Download"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                    <button
                                        type="button"
                                        class="rounded-lg p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950/40"
                                        title="Delete"
                                        @click="confirmDeleteSingle(item)"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Files Modal -->
        <Modal
            :show="showUploadModal"
            title="Upload Media Files"
            description="Add files to current directory"
            max-width="lg"
            @close="showUploadModal = false"
        >
            <div class="space-y-4">
                <input
                    ref="fileInputRef"
                    type="file"
                    multiple
                    class="hidden"
                    @change="(e) => handleUploadFiles((e.target as HTMLInputElement).files || [])"
                />

                <div
                    class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 p-8 text-center cursor-pointer transition hover:border-primary-500 hover:bg-primary-50/20 dark:border-zinc-700 dark:hover:border-primary-500"
                    @click="fileInputRef?.click()"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                        Click to select or drag & drop files here
                    </p>
                    <p class="mt-1 text-xs text-zinc-500">
                        Supports images, documents, videos, and archives (Max 50MB per file)
                    </p>
                </div>

                <div v-if="uploading" class="space-y-1.5">
                    <div class="flex justify-between text-xs text-zinc-600">
                        <span>Uploading files...</span>
                        <span>{{ uploadProgress }}%</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-primary-600 transition-all duration-300" :style="{ width: uploadProgress + '%' }" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showUploadModal = false">
                        Cancel
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Create Folder Modal -->
        <Modal
            :show="showCreateFolderModal"
            title="Create New Folder"
            description="Create a directory to organize your media files"
            max-width="md"
            @close="showCreateFolderModal = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Folder Name</label>
                    <input
                        v-model="newFolderName"
                        type="text"
                        placeholder="e.g. Logos, Invoices, Banners"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 px-3.5 py-2 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        @keyup.enter="handleCreateFolder"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showCreateFolderModal = false">
                        Cancel
                    </Button>
                    <Button @click="handleCreateFolder">
                        Create Folder
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Rename Folder Modal -->
        <Modal
            :show="showRenameFolderModal"
            title="Rename Folder"
            max-width="md"
            @close="showRenameFolderModal = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Folder Name</label>
                    <input
                        v-model="renameFolderName"
                        type="text"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 px-3.5 py-2 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        @keyup.enter="handleRenameFolder"
                    />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showRenameFolderModal = false">
                        Cancel
                    </Button>
                    <Button @click="handleRenameFolder">
                        Save
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Move To Folder Modal -->
        <Modal
            :show="showMoveModal"
            title="Move Files"
            description="Choose a destination folder"
            max-width="md"
            @close="showMoveModal = false"
        >
            <div class="space-y-4">
                <div class="max-h-60 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                    <button
                        type="button"
                        :class="[
                            'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition',
                            targetDirectoryId === null ? 'bg-primary-600 text-white' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        ]"
                        @click="targetDirectoryId = null"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <span>Root / Home</span>
                    </button>

                    <button
                        v-for="dir in directories"
                        :key="dir.id"
                        type="button"
                        :class="[
                            'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition',
                            targetDirectoryId === dir.id ? 'bg-primary-600 text-white' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        ]"
                        @click="targetDirectoryId = dir.id"
                    >
                        <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                        </svg>
                        <span>{{ dir.name }}</span>
                    </button>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showMoveModal = false">
                        Cancel
                    </Button>
                    <Button @click="handleMoveItems">
                        Move Files
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Preview & Info Modal -->
        <Modal
            :show="showPreviewModal"
            :title="previewItem?.name || 'Media Details'"
            max-width="2xl"
            @close="showPreviewModal = false"
        >
            <div v-if="previewItem" class="space-y-4">
                <!-- Media Preview -->
                <div class="flex max-h-96 items-center justify-center overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <img
                        v-if="previewItem.file_type === 'image'"
                        :src="previewItem.url"
                        :alt="previewItem.name"
                        class="max-h-96 w-auto object-contain"
                    />
                    <video
                        v-else-if="previewItem.file_type === 'video'"
                        :src="previewItem.url"
                        controls
                        class="max-h-96 w-full"
                    />
                    <audio
                        v-else-if="previewItem.file_type === 'audio'"
                        :src="previewItem.url"
                        controls
                        class="w-full p-6"
                    />
                    <div v-else class="flex flex-col items-center justify-center p-12 text-zinc-400">
                        <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-sm font-semibold uppercase">{{ previewItem.name.split('.').pop() }} Document</p>
                    </div>
                </div>

                <!-- Info Table -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/50">
                        <span class="text-zinc-400">File Name</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ previewItem.name }}</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/50">
                        <span class="text-zinc-400">File Size</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ previewItem.human_size }}</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/50">
                        <span class="text-zinc-400">MIME Type</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ previewItem.mime_type || 'Unknown' }}</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/50">
                        <span class="text-zinc-400">Uploaded On</span>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ new Date(previewItem.created_at).toLocaleString() }}</p>
                    </div>
                </div>

                <!-- Public URL Field -->
                <div>
                    <label class="block text-xs font-medium text-zinc-500">Public Link</label>
                    <div class="mt-1 flex gap-2">
                        <input
                            type="text"
                            readonly
                            :value="previewItem.url"
                            class="flex-1 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                        />
                        <button
                            type="button"
                            class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900"
                            @click="copyPublicUrl(previewItem.url)"
                        >
                            Copy
                        </button>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-800">
                    <button
                        type="button"
                        class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                        @click="confirmDeleteSingle(previewItem)"
                    >
                        Delete File
                    </button>

                    <div class="flex items-center gap-2">
                        <a
                            :href="`/superadmin/media/${previewItem.id}/download`"
                            class="rounded-lg border border-zinc-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            Download
                        </a>
                        <Button variant="secondary" @click="showPreviewModal = false">
                            Close
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal
            :show="showDeleteConfirmModal"
            title="Confirm Deletion"
            description="Are you sure you want to delete this item? This action cannot be undone."
            max-width="md"
            @close="showDeleteConfirmModal = false"
        >
            <div class="space-y-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Deleting: <strong class="text-zinc-900 dark:text-zinc-100">{{ deleteTarget?.name }}</strong>
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showDeleteConfirmModal = false">
                        Cancel
                    </Button>
                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-500"
                        @click="executeDelete"
                    >
                        Delete Permanently
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
