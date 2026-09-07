<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Switch from '@/components/Switch.vue';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import { formatFileSize } from '@/utils/fileCompressor';
import type { PaginatedDocuments, EmployeeDocumentItem } from '../../types/employee-documents';

defineProps<{
    documents: PaginatedDocuments;
    canManage: boolean;
    hasActiveFilters: boolean;
}>();

const emit = defineEmits<{
    (e: 'preview', doc: EmployeeDocumentItem): void;
    (e: 'delete', doc: EmployeeDocumentItem): void;
    (e: 'toggleActive', doc: EmployeeDocumentItem): void;
}>();

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '—';
    try {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};

const getDaysUntilExpiry = (expiryStr?: string | null) => {
    if (!expiryStr) return null;
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const expiry = new Date(expiryStr);
    expiry.setHours(0, 0, 0, 0);
    const diff = Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    return diff;
};

const getTypeBadgeVariant = (type: string) => {
    switch (type) {
        case 'employment_contract':
            return 'primary';
        case 'national_id':
        case 'passport':
        case 'driving_license':
            return 'info';
        case 'education_certificate':
        case 'professional_certificate':
            return 'success';
        case 'experience_letter':
            return 'warning';
        default:
            return 'neutral';
    }
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'verified':
            return 'success';
        case 'pending':
            return 'warning';
        case 'rejected':
            return 'danger';
        case 'expired':
            return 'neutral';
        default:
            return 'neutral';
    }
};

const getInitials = (name?: string) => {
    if (!name) return 'ST';
    return name
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Staff Member</th>
                        <th scope="col" class="px-5 py-3.5">Document Details</th>
                        <th scope="col" class="px-5 py-3.5">Type</th>
                        <th scope="col" class="px-5 py-3.5">Dates & Expiry</th>
                        <th scope="col" class="px-5 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5">File</th>
                        <th scope="col" class="px-5 py-3.5 text-center">Active</th>
                        <th scope="col" class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    <!-- Empty State -->
                    <tr v-if="documents.data.length === 0">
                        <td colspan="8" class="px-5 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                No employee documents found
                            </h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ hasActiveFilters ? 'Try adjusting your search criteria.' : 'Start by uploading staff contracts, certificates, or identity cards.' }}
                            </p>
                            <div v-if="canManage" class="mt-4">
                                <Link href="/admin/hrm/employee-documents/create">
                                    <Button variant="primary" size="sm">
                                        Upload Document
                                    </Button>
                                </Link>
                            </div>
                        </td>
                    </tr>

                    <!-- Row -->
                    <tr
                        v-for="doc in documents.data"
                        :key="doc.public_id"
                        class="transition-colors hover:bg-zinc-50/75 dark:hover:bg-zinc-800/50"
                    >
                        <!-- Staff Member -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[11px] font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                                    {{ getInitials(doc.staff?.name) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ doc.staff?.name || 'Staff' }}
                                    </p>
                                    <p class="text-[11px] text-zinc-500 font-mono">
                                        {{ doc.staff?.employee_code }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Document Details -->
                        <td class="px-5 py-4">
                            <div class="max-w-[220px]">
                                <Link
                                    :href="`/admin/hrm/employee-documents/${doc.public_id}`"
                                    class="font-medium text-zinc-900 hover:text-primary-600 dark:text-zinc-100 dark:hover:text-primary-400 truncate block"
                                    :title="doc.title"
                                >
                                    {{ doc.title }}
                                </Link>
                                <span v-if="doc.document_number" class="text-[11px] text-zinc-500 font-mono truncate block">
                                    #{{ doc.document_number }}
                                </span>
                            </div>
                        </td>

                        <!-- Type -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <Badge :variant="getTypeBadgeVariant(doc.document_type || doc.type?.value)" size="sm">
                                {{ doc.document_type_label || doc.type?.label || doc.document_type }}
                            </Badge>
                        </td>

                        <!-- Dates & Expiry -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="space-y-0.5">
                                <p class="text-zinc-700 dark:text-zinc-300">
                                    Exp: {{ formatDate(doc.expiry_date) }}
                                </p>
                                <div v-if="doc.expiry_date">
                                    <span
                                        v-if="getDaysUntilExpiry(doc.expiry_date)! < 0"
                                        class="inline-flex items-center text-[10px] font-bold text-rose-600 dark:text-rose-400"
                                    >
                                        Expired
                                    </span>
                                    <span
                                        v-else-if="getDaysUntilExpiry(doc.expiry_date)! <= 30"
                                        class="inline-flex items-center text-[10px] font-semibold text-amber-600 dark:text-amber-400"
                                    >
                                        Expires in {{ getDaysUntilExpiry(doc.expiry_date) }} days
                                    </span>
                                    <span v-else class="text-[10px] text-zinc-400">
                                        Valid
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <Badge :variant="getStatusBadgeVariant(doc.status || doc.status?.value)" size="sm">
                                {{ doc.status_label || doc.status?.label || doc.status }}
                            </Badge>
                        </td>

                        <!-- File Details & Download -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <a
                                    v-if="doc.file?.download_url || doc.file_url"
                                    :href="doc.file?.download_url || doc.file_url"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 cursor-pointer"
                                    title="Download File"
                                    target="_blank"
                                >
                                    <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span>{{ formatFileSize(doc.file?.size) }}</span>
                                </a>
                                <button
                                    type="button"
                                    class="p-1 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 cursor-pointer"
                                    title="Quick View"
                                    @click="emit('preview', doc)"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <!-- Active Toggle -->
                        <td class="px-5 py-4 whitespace-nowrap text-center">
                            <Switch
                                :model-value="doc.is_active"
                                :disabled="!canManage"
                                @update:model-value="emit('toggleActive', doc)"
                            />
                        </td>

                        <!-- Actions Dropdown -->
                        <td class="p-4 align-middle text-right whitespace-nowrap">
                            <Dropdown align="right" width="w-48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors cursor-pointer"
                                        title="Actions"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>

                                <template #default="{ close }">
                                    <div class="py-1 text-xs text-zinc-700 dark:text-zinc-200">
                                        <Link
                                            :href="`/admin/hrm/employee-documents/${doc.public_id}`"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>View Details</span>
                                        </Link>

                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left cursor-pointer"
                                            @click="emit('preview', doc); close()"
                                        >
                                            <svg class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>Quick Preview</span>
                                        </button>

                                        <a
                                            v-if="doc.file?.download_url || doc.file_url"
                                            :href="doc.file?.download_url || doc.file_url"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span>Download File</span>
                                        </a>

                                        <Link
                                            v-if="canManage"
                                            :href="`/admin/hrm/employee-documents/${doc.public_id}/edit`"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit Document</span>
                                        </Link>

                                        <div v-if="canManage" class="my-1 border-t border-zinc-200 dark:border-zinc-800" />

                                        <button
                                            v-if="canManage"
                                            type="button"
                                            class="flex w-full items-center gap-2.5 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-colors rounded-md text-left cursor-pointer"
                                            @click="emit('delete', doc); close()"
                                        >
                                            <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <Pagination :data="documents" />
    </div>
</template>
