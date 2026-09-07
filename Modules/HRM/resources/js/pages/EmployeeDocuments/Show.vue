<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import Modal from '@/components/Modal.vue';
import Switch from '@/components/Switch.vue';
import { formatFileSize } from '@/utils/fileCompressor';
import { usePermissions } from '@/composables/usePermissions';
import type { EmployeeDocumentItem } from '../../types/employee-documents';

const props = defineProps<{
    document: { data: EmployeeDocumentItem };
    can?: {
        manage?: boolean;
    };
}>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('employee_documents.manage'));

const doc = computed(() => props.document.data);

// Delete Modal
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`/admin/hrm/employee-documents/${doc.value.public_id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};

const toggleActive = () => {
    if (!canManage.value) return;
    router.patch(
        `/admin/hrm/employee-documents/${doc.value.public_id}/toggle-status`,
        {},
        {
            preserveScroll: true,
        }
    );
};

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

const formatDateTime = (dateStr?: string | null) => {
    if (!dateStr) return '—';
    try {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
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
    return Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
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
</script>

<template>
    <OrganizationLayout
        title="Employee Document Details"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Employee Documents', href: '/admin/hrm/employee-documents' },
            { label: doc.title },
        ]"
    >
        <Head :title="`${doc.title} - Employee Documents`" />

        <div class="px-4 py-6 sm:px-8 max-w-5xl mx-auto space-y-6">
            <!-- Top Action Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ doc.title }}
                        </h1>
                        <Badge :variant="getStatusBadgeVariant(doc.status.value)">
                            {{ doc.status.label }}
                        </Badge>
                        <span
                            v-if="doc.is_active"
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            Active
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                        >
                            Inactive
                        </span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Employee document for {{ doc.staff?.name }} ({{ doc.staff?.employee_code }})
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a
                        v-if="doc.file.download_url"
                        :href="doc.file.download_url"
                        target="_blank"
                    >
                        <Button variant="primary" size="sm">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download File
                        </Button>
                    </a>

                    <Link
                        v-if="canManage"
                        :href="`/admin/hrm/employee-documents/${doc.public_id}/edit`"
                    >
                        <Button variant="secondary" size="sm">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </Button>
                    </Link>

                    <Button
                        v-if="canManage"
                        variant="danger"
                        size="sm"
                        @click="showDeleteModal = true"
                    >
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Expiry Warning Alert (if expiring soon or expired) -->
            <div
                v-if="doc.expiry_date && getDaysUntilExpiry(doc.expiry_date)! <= 30"
                class="flex items-center gap-3 rounded-xl border p-4 shadow-xs"
                :class="[
                    getDaysUntilExpiry(doc.expiry_date)! < 0
                        ? 'border-rose-200 bg-rose-50/80 text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200'
                        : 'border-amber-200 bg-amber-50/80 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200'
                ]"
            >
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="getDaysUntilExpiry(doc.expiry_date)! < 0 ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/50' : 'bg-amber-100 text-amber-600 dark:bg-amber-900/50'">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold">
                        {{ getDaysUntilExpiry(doc.expiry_date)! < 0 ? 'This document has expired' : 'This document is expiring soon' }}
                    </h4>
                    <p class="text-[11px] mt-0.5">
                        {{ getDaysUntilExpiry(doc.expiry_date)! < 0 ? `Expired on ${formatDate(doc.expiry_date)}. Please request an updated copy from the employee.` : `Expires in ${getDaysUntilExpiry(doc.expiry_date)} days on ${formatDate(doc.expiry_date)}.` }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Specifications & Notes (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Document Metadata Card -->
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-4">
                            Document Specifications
                        </h3>

                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
                            <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                                <dt class="text-zinc-400">Category / Type</dt>
                                <dd class="mt-1">
                                    <Badge :variant="getTypeBadgeVariant(doc.type.value)">
                                        {{ doc.type.label }}
                                    </Badge>
                                </dd>
                            </div>

                            <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                                <dt class="text-zinc-400">Document / ID Number</dt>
                                <dd class="mt-1 font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ doc.document_number || '—' }}
                                </dd>
                            </div>

                            <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                                <dt class="text-zinc-400">Issue Date</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ formatDate(doc.issue_date) }}
                                </dd>
                            </div>

                            <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                                <dt class="text-zinc-400">Expiry Date</dt>
                                <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ formatDate(doc.expiry_date) }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Notes -->
                        <div class="mt-5 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <h4 class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Notes & Remarks
                            </h4>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed bg-zinc-50 p-3 rounded-lg dark:bg-zinc-800">
                                {{ doc.notes || 'No notes provided for this document.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Attached File Card -->
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-4">
                            Storage & File Asset
                        </h3>

                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                        {{ doc.file.name }}
                                    </p>
                                    <p class="text-[11px] text-zinc-500">
                                        {{ doc.file.mime_type }} • {{ formatFileSize(doc.file.size) }} • Stored securely on private disk
                                    </p>
                                </div>
                            </div>

                            <a
                                v-if="doc.file.download_url"
                                :href="doc.file.download_url"
                                target="_blank"
                            >
                                <Button variant="secondary" size="sm">
                                    Download
                                </Button>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Staff Card & Audit Info -->
                <div class="space-y-6">
                    <!-- Staff Card -->
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3">
                            Assigned Staff
                        </h3>

                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-300">
                                {{ doc.staff?.name ? doc.staff.name.slice(0, 2).toUpperCase() : 'ST' }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ doc.staff?.name }}
                                </h4>
                                <p class="text-xs font-mono text-zinc-500">
                                    Employee ID: {{ doc.staff?.employee_code }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <Link href="/admin/staff" class="text-xs text-primary-600 hover:underline dark:text-primary-400">
                                View Staff Directory Profile →
                            </Link>
                        </div>
                    </div>

                    <!-- Status Controls Card -->
                    <div v-if="canManage" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3">
                            Document Management
                        </h3>

                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200">Active Record</p>
                                <p class="text-[11px] text-zinc-400">Enable or disable document validity.</p>
                            </div>
                            <Switch :model-value="doc.is_active" @update:model-value="toggleActive" />
                        </div>
                    </div>

                    <!-- Audit Timestamps -->
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 text-xs space-y-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">
                            Audit Trail
                        </h3>

                        <div class="flex justify-between text-zinc-500">
                            <span>Uploaded:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ formatDateTime(doc.created_at) }}</span>
                        </div>

                        <div class="flex justify-between text-zinc-500">
                            <span>Last Updated:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ formatDateTime(doc.updated_at) }}</span>
                        </div>

                        <div v-if="doc.verified_at" class="flex justify-between text-zinc-500">
                            <span>Verified At:</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ formatDateTime(doc.verified_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Modal
            :show="showDeleteModal"
            title="Delete Document"
            description="Are you sure you want to delete this employee document? The attached file will be permanently removed from storage."
            max-width="md"
            @close="showDeleteModal = false"
        >
            <div class="space-y-4">
                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" :disabled="isDeleting" @click="showDeleteModal = false">
                        Cancel
                    </Button>
                    <Button variant="danger" :disabled="isDeleting" @click="confirmDelete">
                        {{ isDeleting ? 'Deleting...' : 'Delete Permanently' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </OrganizationLayout>
</template>
