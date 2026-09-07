<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import { usePermissions } from '@/composables/usePermissions';
import DocumentStats from '../../components/employee-documents/DocumentStats.vue';
import DocumentFilters from '../../components/employee-documents/DocumentFilters.vue';
import DocumentTable from '../../components/employee-documents/DocumentTable.vue';
import DocumentPreviewModal from '../../components/employee-documents/DocumentPreviewModal.vue';
import DocumentDeleteModal from '../../components/employee-documents/DocumentDeleteModal.vue';
import type {
    PaginatedDocuments,
    EmployeeDocumentItem,
    DocumentFilters as DocumentFiltersType,
    DocumentStaffOption,
    DocumentTypeOption,
} from '../../types/employee-documents';

const props = defineProps<{
    documents: PaginatedDocuments;
    filters: DocumentFiltersType;
    stats: {
        total: number;
        active: number;
        expiring_soon: number;
        pending: number;
    };
    staff_members: DocumentStaffOption[];
    document_types: DocumentTypeOption[];
    document_statuses: Array<{ value: string; label: string }>;
    can?: {
        manage?: boolean;
    };
}>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('employee_documents.manage'));

// Filters State
const search = ref(props.filters.search ?? '');
const selectedStaff = ref(props.filters.tenant_staff_id ?? '');
const selectedType = ref(props.filters.type ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const expiringSoonOnly = ref(Boolean(props.filters.expiring_within_days));
const perPage = ref(props.filters.per_page ?? 20);

const applyFilters = () => {
    router.get(
        '/admin/hrm/employee-documents',
        {
            search: search.value.trim() || undefined,
            tenant_staff_id: selectedStaff.value || undefined,
            type: selectedType.value || undefined,
            status: selectedStatus.value || undefined,
            expiring_within_days: expiringSoonOnly.value ? 30 : undefined,
            per_page: perPage.value !== 20 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedStaff.value = '';
    selectedType.value = '';
    selectedStatus.value = '';
    expiringSoonOnly.value = false;
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return !!(search.value || selectedStaff.value || selectedType.value || selectedStatus.value || expiringSoonOnly.value);
});

// Preview Modal State
const previewDoc = ref<EmployeeDocumentItem | null>(null);
const showPreviewModal = ref(false);

const openPreview = (doc: EmployeeDocumentItem) => {
    previewDoc.value = doc;
    showPreviewModal.value = true;
};

// Delete Modal State
const docToDelete = ref<EmployeeDocumentItem | null>(null);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const openDeleteModal = (doc: EmployeeDocumentItem) => {
    docToDelete.value = doc;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!docToDelete.value) return;
    isDeleting.value = true;
    router.delete(`/admin/hrm/employee-documents/${docToDelete.value.public_id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            docToDelete.value = null;
        },
    });
};

// Toggle Active State
const toggleDocumentActive = (doc: EmployeeDocumentItem) => {
    if (!canManage.value) return;
    router.patch(
        `/admin/hrm/employee-documents/${doc.public_id}/toggle-status`,
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <OrganizationLayout
        title="Employee Documents"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Employee Documents' },
        ]"
    >
        <Head title="Employee Documents - HRM" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Employee Documents
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Securely manage employee contracts, credentials, government IDs, and compliance documents.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        v-if="canManage"
                        href="/admin/hrm/employee-documents/create"
                    >
                        <Button variant="primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Upload Document
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Metric Cards -->
            <DocumentStats :stats="stats" />

            <!-- Filters Bar -->
            <DocumentFilters
                v-model:search="search"
                v-model:selected-staff="selectedStaff"
                v-model:selected-type="selectedType"
                v-model:selected-status="selectedStatus"
                v-model:expiring-soon-only="expiringSoonOnly"
                v-model:per-page="perPage"
                :staff_members="staff_members"
                :document_types="document_types"
                :document_statuses="document_statuses"
                :expiring-soon-count="stats.expiring_soon"
                :total-count="documents.data.length"
                :total-records="documents.total || documents.data.length"
                :has-active-filters="hasActiveFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- Documents Table -->
            <DocumentTable
                :documents="documents"
                :can-manage="canManage"
                :has-active-filters="hasActiveFilters"
                @preview="openPreview"
                @delete="openDeleteModal"
                @toggle-active="toggleDocumentActive"
            />
        </div>

        <!-- Preview Modal -->
        <DocumentPreviewModal
            :show="showPreviewModal"
            :doc="previewDoc"
            @close="showPreviewModal = false"
        />

        <!-- Delete Confirmation Modal -->
        <DocumentDeleteModal
            :show="showDeleteModal"
            :doc="docToDelete"
            :is-deleting="isDeleting"
            @close="showDeleteModal = false"
            @confirm="confirmDelete"
        />
    </OrganizationLayout>
</template>
