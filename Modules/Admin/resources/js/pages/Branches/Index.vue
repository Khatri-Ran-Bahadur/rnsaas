<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';

interface Branch {
    id: number;
    public_id: string;
    tenant_id: number;
    name: string;
    code: string;
    status: 'active' | 'inactive';
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country_code: string | null;
    created_at: string;
    updated_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedBranches {
    data: Branch[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

const props = defineProps<{
    branches: PaginatedBranches;
    filters: {
        search?: string;
        status?: string;
        per_page?: number;
    };
}>();

const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? props.branches.per_page ?? 15);
const isLoading = ref(false);

// Deactivate modal state
const deactivatingBranch = ref<Branch | null>(null);
const isDeactivating = ref(false);

// Activate modal state
const activatingBranch = ref<Branch | null>(null);
const isActivating = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        '/admin/branches',
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

const resetFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    perPage.value = 15;
    applyFilters();
};

const openDeactivateModal = (branch: Branch) => {
    deactivatingBranch.value = branch;
};

const closeDeactivateModal = () => {
    deactivatingBranch.value = null;
    isDeactivating.value = false;
};

const confirmDeactivate = () => {
    if (!deactivatingBranch.value) return;

    isDeactivating.value = true;
    router.patch(
        `/admin/branches/${deactivatingBranch.value.public_id}/deactivate`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeDeactivateModal();
            },
            onFinish: () => {
                isDeactivating.value = false;
            },
        }
    );
};

const openActivateModal = (branch: Branch) => {
    activatingBranch.value = branch;
};

const closeActivateModal = () => {
    activatingBranch.value = null;
    isActivating.value = false;
};

const confirmActivate = () => {
    if (!activatingBranch.value) return;

    isActivating.value = true;
    router.patch(
        `/admin/branches/${activatingBranch.value.public_id}/activate`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeActivateModal();
            },
            onFinish: () => {
                isActivating.value = false;
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
        title="Branch Management"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Branches' },
        ]"
    >
        <Head title="Branches - Organization Admin" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Branches
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Manage physical locations, regional hubs, and operating offices for your organization.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200/90 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" />
                        Total Branches: {{ branches.total }}
                    </span>

                    <Button
                        href="/admin/branches/create"
                        variant="primary"
                        size="sm"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </template>
                        Add Branch
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
                            placeholder="Search by branch name or code..."
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 pl-10 pr-9 py-2 text-xs text-zinc-900 placeholder-zinc-400 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100 dark:focus:border-indigo-500"
                            @input="onSearchInput"
                        />
                        <button
                            v-if="search"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            title="Clear search"
                            @click="clearSearch"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div v-else-if="isLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="h-3.5 w-3.5 animate-spin text-zinc-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Right: Filters & Per-Page -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Status Filter -->
                        <div class="relative min-w-[140px]">
                            <select
                                v-model="selectedStatus"
                                class="w-full appearance-none rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2 pr-8 text-xs font-medium text-zinc-700 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-300"
                                @change="onStatusChange"
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active Status</option>
                                <option value="inactive">Inactive Status</option>
                            </select>
                            <div class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Per-Page Selector Component -->
                        <PerPageSelector
                            :model-value="perPage"
                            :options="[10, 15, 25, 50, 100]"
                            @change="onPerPageChange"
                        />

                        <!-- Clear Filters Button -->
                        <Button
                            v-if="search || selectedStatus || perPage !== 15"
                            variant="ghost"
                            size="sm"
                            @click="resetFilters"
                        >
                            Reset
                        </Button>
                    </div>
                </div>

                <!-- Table Content Body -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200/70 text-left text-xs dark:divide-zinc-800/80">
                        <thead class="bg-zinc-50/75 dark:bg-zinc-950/60 text-zinc-600 dark:text-zinc-400 font-semibold">
                            <tr>
                                <th scope="col" class="px-6 py-3.5">Branch Name</th>
                                <th scope="col" class="px-6 py-3.5">Code</th>
                                <th scope="col" class="px-6 py-3.5">City</th>
                                <th scope="col" class="px-6 py-3.5">Status</th>
                                <th scope="col" class="px-6 py-3.5">Created Date</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                            <tr
                                v-for="branch in branches.data"
                                :key="branch.public_id"
                                class="group transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                            >
                                <!-- Branch Name -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-500/10 dark:bg-indigo-950/70 dark:text-indigo-300 dark:ring-indigo-500/20">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <Link
                                                :href="`/admin/branches/${branch.public_id}`"
                                                class="font-semibold text-zinc-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400 transition-colors"
                                            >
                                                {{ branch.name }}
                                            </Link>
                                            <p v-if="branch.address_line_1" class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                                {{ branch.address_line_1 }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Code -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-md border border-zinc-200/80 bg-zinc-100/80 px-2.5 py-1 font-mono text-xs font-semibold text-zinc-800 shadow-2xs dark:border-zinc-700/80 dark:bg-zinc-800 dark:text-zinc-200">
                                        {{ branch.code }}
                                    </span>
                                </td>

                                <!-- City -->
                                <td class="whitespace-nowrap px-6 py-4 text-zinc-600 dark:text-zinc-300 font-medium">
                                    {{ branch.city || '—' }}
                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-6 py-4">
                                    <Badge
                                        :variant="branch.status === 'active' ? 'active' : 'neutral'"
                                        size="sm"
                                    >
                                        {{ branch.status === 'active' ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>

                                <!-- Created Date -->
                                <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                    {{ formatDate(branch.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link
                                            :href="`/admin/branches/${branch.public_id}`"
                                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                                            title="View Branch Details"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>

                                        <Link
                                            :href="`/admin/branches/${branch.public_id}/edit`"
                                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                                            title="Edit Branch"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>

                                        <!-- Deactivate Button -->
                                        <button
                                            v-if="branch.status === 'active'"
                                            type="button"
                                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:text-zinc-500 dark:hover:bg-rose-950/50 dark:hover:text-rose-400 transition-colors cursor-pointer"
                                            title="Deactivate Branch"
                                            @click="openDeactivateModal(branch)"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>

                                        <!-- Reactivate Button -->
                                        <button
                                            v-else
                                            type="button"
                                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-emerald-50 hover:text-emerald-600 dark:text-zinc-500 dark:hover:bg-emerald-950/50 dark:hover:text-emerald-400 transition-colors cursor-pointer"
                                            title="Activate Branch"
                                            @click="openActivateModal(branch)"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="branches.data.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">
                                            No branches found
                                        </h3>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 max-w-sm">
                                            {{ search || selectedStatus ? 'No branches match your current search or filter criteria. Try clearing filters.' : 'Get started by registering your organization’s first operating branch.' }}
                                        </p>
                                        <div class="mt-4">
                                            <Button
                                                v-if="search || selectedStatus"
                                                variant="outline"
                                                size="sm"
                                                @click="resetFilters"
                                            >
                                                Reset Filters
                                            </Button>
                                            <Button
                                                v-else
                                                href="/admin/branches/create"
                                                variant="primary"
                                                size="sm"
                                            >
                                                Add First Branch
                                            </Button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Always-Visible Pagination & Results Footer -->
                <div class="flex flex-col items-center justify-between gap-3 border-t border-zinc-200/70 bg-zinc-50/50 px-6 py-3.5 dark:border-zinc-800/80 dark:bg-zinc-950/40 sm:flex-row text-xs text-zinc-600 dark:text-zinc-400">
                    <div>
                        Showing
                        <span class="font-medium text-zinc-900 dark:text-white">{{ branches.from ?? 0 }}</span>
                        to
                        <span class="font-medium text-zinc-900 dark:text-white">{{ branches.to ?? 0 }}</span>
                        of
                        <span class="font-medium text-zinc-900 dark:text-white">{{ branches.total }}</span>
                        branches
                    </div>

                    <!-- Pagination Links Component -->
                    <div class="flex items-center gap-1 sm:space-x-1">
                        <template v-for="(link, index) in branches.links" :key="index">
                            <!-- Previous -->
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

                            <!-- Next -->
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

        <!-- Deactivate Confirmation Modal -->
        <Modal
            :show="!!deactivatingBranch"
            title="Deactivate Branch"
            description="Are you sure you want to deactivate this branch?"
            @close="closeDeactivateModal"
        >
            <div class="space-y-3">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    You are about to deactivate
                    <strong class="font-semibold text-zinc-900 dark:text-white">{{ deactivatingBranch?.name }}</strong>
                    <span class="font-mono text-zinc-500">({{ deactivatingBranch?.code }})</span>.
                </p>
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-300">
                    <p class="font-medium">Please note:</p>
                    <p class="mt-1">
                        An inactive branch remains in the database. Users or activities associated with this branch may be restricted until it is reactivated.
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
                    Deactivate Branch
                </Button>
            </template>
        </Modal>

        <!-- Activate Confirmation Modal -->
        <Modal
            :show="!!activatingBranch"
            title="Activate Branch"
            description="Reactivate this branch for normal operations."
            @close="closeActivateModal"
        >
            <p class="text-xs text-zinc-600 dark:text-zinc-300">
                Are you sure you want to reactivate
                <strong class="font-semibold text-zinc-900 dark:text-white">{{ activatingBranch?.name }}</strong>
                <span class="font-mono text-zinc-500">({{ activatingBranch?.code }})</span>?
                It will immediately become available for normal organization usage.
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
                    Activate Branch
                </Button>
            </template>
        </Modal>
    </OrganizationLayout>
</template>
