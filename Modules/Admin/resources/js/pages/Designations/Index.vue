<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';

interface Designation {
    id: number;
    public_id: string;
    tenant_id: number;
    name: string;
    code: string;
    status: 'active' | 'inactive';
    staff_count?: number;
    created_at: string;
    updated_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedDesignations {
    data: Designation[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

const props = defineProps<{
    designations: PaginatedDesignations;
    filters: {
        search?: string;
        status?: string;
        per_page?: number;
    };
}>();

const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? props.designations.per_page ?? 15);
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        '/admin/designations',
        {
            search: search.value.trim() || undefined,
            status: selectedStatus.value || undefined,
            per_page: perPage.value !== 15 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
            },
        }
    );
};

const onSearchInput = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

const clearSearch = () => {
    search.value = '';
    applyFilters();
};

const onStatusChange = () => {
    applyFilters();
};

const onPerPageChange = (val: number) => {
    perPage.value = val;
    applyFilters();
};

// Create Modal State & Form
const isCreateModalOpen = ref(false);
const createForm = useForm({
    name: '',
    code: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post('/admin/designations', {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
};

// Edit Modal State & Form
const editingDesignation = ref<Designation | null>(null);
const editForm = useForm({
    name: '',
    code: '',
});

const openEditModal = (designation: Designation) => {
    editingDesignation.value = designation;
    editForm.name = designation.name;
    editForm.code = designation.code;
    editForm.clearErrors();
};

const closeEditModal = () => {
    editingDesignation.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingDesignation.value) return;

    editForm.put(`/admin/designations/${editingDesignation.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

// Deactivate / Activate Modals
const deactivatingDesignation = ref<Designation | null>(null);
const isDeactivating = ref(false);

const openDeactivateModal = (designation: Designation) => {
    deactivatingDesignation.value = designation;
};

const closeDeactivateModal = () => {
    deactivatingDesignation.value = null;
};

const confirmDeactivate = () => {
    if (!deactivatingDesignation.value) return;

    isDeactivating.value = true;
    router.patch(
        `/admin/designations/${deactivatingDesignation.value.id}/deactivate`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isDeactivating.value = false;
                closeDeactivateModal();
            },
        }
    );
};

const activatingDesignation = ref<Designation | null>(null);
const isActivating = ref(false);

const openActivateModal = (designation: Designation) => {
    activatingDesignation.value = designation;
};

const closeActivateModal = () => {
    activatingDesignation.value = null;
};

const confirmActivate = () => {
    if (!activatingDesignation.value) return;

    isActivating.value = true;
    router.patch(
        `/admin/designations/${activatingDesignation.value.id}/activate`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isActivating.value = false;
                closeActivateModal();
            },
        }
    );
};

const formatDate = (dateStr: string) => {
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

const cleanLabel = (label: string) => {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', '')
        .replace('Next', '')
        .trim();
};

const isPrevious = (label: string) => label.includes('Previous') || label.includes('&laquo;');
const isNext = (label: string) => label.includes('Next') || label.includes('&raquo;');
</script>

<template>
    <OrganizationLayout
        title="Designation Management"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Designations' },
        ]"
    >
        <Head title="Designations - Organization Admin" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Designations
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Manage job titles, organizational roles, and positions for your team members.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200/90 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" />
                        Total Designations: {{ designations.total }}
                    </span>

                    <Button
                        variant="primary"
                        size="sm"
                        @click="openCreateModal"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </template>
                        Add Designation
                    </Button>
                </div>
            </div>

            <!-- Unified DataTable Card Wrapper -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs transition-colors dark:border-zinc-800 dark:bg-zinc-900">
                <!-- Integrated Toolbar (Search, Filters, Per-Page) -->
                <div class="flex flex-col gap-3 border-b border-zinc-200/70 p-4 dark:border-zinc-800/80 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Left: Search Box -->
                    <div class="relative flex-1 max-w-md">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by designation title or code..."
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 pl-10 pr-9 py-2 text-xs text-zinc-900 placeholder-zinc-400 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100 dark:focus:border-indigo-500"
                            @input="onSearchInput"
                        />
                        <button
                            v-if="search"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            @click="clearSearch"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Right: Filters & Controls -->
                    <div class="flex flex-wrap items-center gap-3">
                        <select
                            v-model="selectedStatus"
                            class="rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-700 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-300 dark:focus:border-indigo-500"
                            @change="onStatusChange"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        <PerPageSelector
                            :model-value="perPage"
                            :options="[10, 15, 25, 50, 100]"
                            @update:model-value="onPerPageChange"
                        />
                    </div>
                </div>

                <!-- Table Content -->
                <div class="relative overflow-x-auto">
                    <!-- Loading overlay -->
                    <div
                        v-if="isLoading"
                        class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-2xs dark:bg-zinc-900/60"
                    >
                        <div class="flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-xs font-medium text-zinc-700 shadow-md dark:bg-zinc-800 dark:text-zinc-200">
                            <svg class="h-4 w-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Loading designations...
                        </div>
                    </div>

                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-zinc-200/70 bg-zinc-50/50 text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-950/30 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 font-semibold sm:pl-6">Designation Title</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Code</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Staff Count</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Status</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Created</th>
                                <th scope="col" class="py-3.5 pl-3 pr-4 text-right font-semibold sm:pr-6">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/80">
                            <!-- Empty State -->
                            <tr v-if="designations.data.length === 0">
                                <td colspan="6" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">
                                            No designations found
                                        </h3>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ search || selectedStatus ? 'Try adjusting your search criteria or filters.' : 'Get started by creating your first job title / designation.' }}
                                        </p>
                                        <div class="mt-4">
                                            <Button
                                                v-if="!search && !selectedStatus"
                                                variant="primary"
                                                size="sm"
                                                @click="openCreateModal"
                                            >
                                                Add Designation
                                            </Button>
                                            <Button
                                                v-else
                                                variant="outline"
                                                size="sm"
                                                @click="() => { search = ''; selectedStatus = ''; applyFilters(); }"
                                            >
                                                Reset Filters
                                            </Button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row Items -->
                            <tr
                                v-for="desig in designations.data"
                                :key="desig.id"
                                class="group transition-colors hover:bg-zinc-50/75 dark:hover:bg-zinc-800/40"
                            >
                                <td class="py-3.5 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                            {{ desig.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <span class="font-medium text-zinc-900 dark:text-white">
                                                {{ desig.name }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3.5 font-mono text-[11px] font-semibold text-zinc-600 dark:text-zinc-300">
                                    <span class="rounded-md bg-zinc-100 px-2 py-0.5 dark:bg-zinc-800">
                                        {{ desig.code }}
                                    </span>
                                </td>

                                <td class="px-3 py-3.5 text-zinc-600 dark:text-zinc-400">
                                    <span class="inline-flex items-center gap-1.5 font-medium">
                                        {{ desig.staff_count ?? 0 }} staff
                                    </span>
                                </td>

                                <td class="px-3 py-3.5">
                                    <Badge :variant="desig.status === 'active' ? 'success' : 'neutral'">
                                        {{ desig.status === 'active' ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>

                                <td class="px-3 py-3.5 text-zinc-500 dark:text-zinc-400">
                                    {{ formatDate(desig.created_at) }}
                                </td>

                                <td class="py-3.5 pl-3 pr-4 text-right sm:pr-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="xs"
                                            @click="openEditModal(desig)"
                                        >
                                            Edit
                                        </Button>

                                        <Button
                                            v-if="desig.status === 'active'"
                                            variant="outline"
                                            size="xs"
                                            class="text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                            @click="openDeactivateModal(desig)"
                                        >
                                            Deactivate
                                        </Button>

                                        <Button
                                            v-else
                                            variant="outline"
                                            size="xs"
                                            class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                            @click="openActivateModal(desig)"
                                        >
                                            Activate
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="designations.total > 0"
                    class="flex flex-col items-center justify-between gap-4 border-t border-zinc-200/70 p-4 dark:border-zinc-800/80 sm:flex-row"
                >
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Showing <span class="font-medium text-zinc-900 dark:text-white">{{ designations.from ?? 0 }}</span> to
                        <span class="font-medium text-zinc-900 dark:text-white">{{ designations.to ?? 0 }}</span> of
                        <span class="font-medium text-zinc-900 dark:text-white">{{ designations.total }}</span> designations
                    </p>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in designations.links" :key="index">
                            <!-- Prev Link -->
                            <template v-if="isPrevious(link.label)">
                                <span
                                    v-if="!link.url"
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200/80 bg-zinc-50/50 text-xs text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-600 cursor-not-allowed select-none"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <span class="hidden sm:inline">Previous</span>
                                </span>
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <span class="hidden sm:inline">Previous</span>
                                </Link>
                            </template>

                            <!-- Next Link -->
                            <template v-else-if="isNext(link.label)">
                                <span
                                    v-if="!link.url"
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200/80 bg-zinc-50/50 text-xs text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-600 cursor-not-allowed select-none"
                                >
                                    <span class="hidden sm:inline">Next</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                                >
                                    <span class="hidden sm:inline">Next</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </template>

                            <!-- Numeric Pages -->
                            <template v-else>
                                <span
                                    v-if="!link.url && !link.active"
                                    class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-xs text-zinc-400 dark:text-zinc-600 select-none"
                                >
                                    ...
                                </span>
                                <span
                                    v-else-if="link.active"
                                    class="inline-flex items-center justify-center h-8 min-w-8 px-2.5 rounded-md bg-indigo-600 font-semibold text-xs text-white shadow-xs shadow-indigo-500/25 select-none"
                                >
                                    {{ cleanLabel(link.label) }}
                                </span>
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center justify-center h-8 min-w-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                                >
                                    {{ cleanLabel(link.label) }}
                                </Link>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Designation Modal -->
        <Modal
            :show="isCreateModalOpen"
            title="Create New Designation"
            description="Add a new job position or title to your organization."
            @close="closeCreateModal"
        >
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Designation Title <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="createForm.name"
                        type="text"
                        placeholder="e.g. Senior Software Engineer"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        required
                    />
                    <p v-if="createForm.errors.name" class="mt-1 text-xs text-rose-500">
                        {{ createForm.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Designation Code <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="createForm.code"
                        type="text"
                        placeholder="e.g. SSE"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs uppercase text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        required
                    />
                    <p class="mt-1 text-[11px] text-zinc-400">
                        Short unique code for the role (e.g., MGR, SSE, HR-EXEC).
                    </p>
                    <p v-if="createForm.errors.code" class="mt-1 text-xs text-rose-500">
                        {{ createForm.errors.code }}
                    </p>
                </div>
            </form>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="createForm.processing"
                    @click="closeCreateModal"
                >
                    Cancel
                </Button>
                <Button
                    variant="primary"
                    size="sm"
                    :loading="createForm.processing"
                    @click="submitCreate"
                >
                    Create Designation
                </Button>
            </template>
        </Modal>

        <!-- Edit Designation Modal -->
        <Modal
            :show="!!editingDesignation"
            title="Edit Designation"
            description="Update the designation details."
            @close="closeEditModal"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Designation Title <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="editForm.name"
                        type="text"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        required
                    />
                    <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-500">
                        {{ editForm.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Designation Code <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="editForm.code"
                        type="text"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs uppercase text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        required
                    />
                    <p v-if="editForm.errors.code" class="mt-1 text-xs text-rose-500">
                        {{ editForm.errors.code }}
                    </p>
                </div>
            </form>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="editForm.processing"
                    @click="closeEditModal"
                >
                    Cancel
                </Button>
                <Button
                    variant="primary"
                    size="sm"
                    :loading="editForm.processing"
                    @click="submitEdit"
                >
                    Save Changes
                </Button>
            </template>
        </Modal>

        <!-- Deactivate Confirmation Modal -->
        <Modal
            :show="!!deactivatingDesignation"
            title="Deactivate Designation"
            description="Are you sure you want to deactivate this designation?"
            @close="closeDeactivateModal"
        >
            <div class="space-y-3">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    You are about to deactivate
                    <strong class="font-semibold text-zinc-900 dark:text-white">{{ deactivatingDesignation?.name }}</strong>
                    <span class="font-mono text-zinc-500">({{ deactivatingDesignation?.code }})</span>.
                </p>
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-300">
                    <p class="font-medium">Please note:</p>
                    <p class="mt-1">
                        An inactive designation will not be available when creating or assigning new staff members.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isDeactivating"
                    @click="closeDeactivateModal"
                >
                    Cancel
                </Button>
                <Button
                    variant="danger"
                    size="sm"
                    :loading="isDeactivating"
                    @click="confirmDeactivate"
                >
                    Deactivate Designation
                </Button>
            </template>
        </Modal>

        <!-- Activate Confirmation Modal -->
        <Modal
            :show="!!activatingDesignation"
            title="Activate Designation"
            description="Reactivate this designation for normal operations."
            @close="closeActivateModal"
        >
            <p class="text-xs text-zinc-600 dark:text-zinc-300">
                Are you sure you want to reactivate
                <strong class="font-semibold text-zinc-900 dark:text-white">{{ activatingDesignation?.name }}</strong>
                <span class="font-mono text-zinc-500">({{ activatingDesignation?.code }})</span>?
                It will immediately become available for staff assignments.
            </p>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isActivating"
                    @click="closeActivateModal"
                >
                    Cancel
                </Button>
                <Button
                    variant="success"
                    size="sm"
                    :loading="isActivating"
                    @click="confirmActivate"
                >
                    Activate Designation
                </Button>
            </template>
        </Modal>
    </OrganizationLayout>
</template>
