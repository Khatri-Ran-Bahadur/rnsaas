<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import DataTable, { type TableColumn } from '@/components/DataTable.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Dropdown from '@/components/Dropdown.vue';
import type { PaginatedData, Tenant } from '@/types/tenancy';

const props = defineProps<{
    tenants: PaginatedData<Tenant>;
    filters?: {
        search?: string;
        status?: string;
        industry?: string;
        per_page?: number;
        sort_by?: string;
        sort_order?: 'asc' | 'desc';
    };
}>();

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const industry = ref(props.filters?.industry ?? '');
const perPage = ref(props.filters?.per_page ?? 10);
const sortBy = ref(props.filters?.sort_by ?? 'created_at');
const sortOrder = ref<'asc' | 'desc'>(props.filters?.sort_order ?? 'desc');

const columns: TableColumn[] = [
    { key: 'organization', label: 'Organization', sortable: true },
    { key: 'industry', label: 'Industry', sortable: true },
    { key: 'country_code', label: 'Country' },
    { key: 'currency', label: 'Currency' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'created_at', label: 'Created', sortable: true },
    { key: 'actions', label: 'Actions', align: 'right' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/tenants',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            industry: industry.value || undefined,
            per_page: perPage.value !== 10 ? perPage.value : undefined,
            sort_by: sortBy.value !== 'created_at' ? sortBy.value : undefined,
            sort_order: sortOrder.value !== 'desc' ? sortOrder.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const handleSearch = (query: string) => {
    search.value = query;
    applyFilters();
};

const handleSort = (columnKey: string, order: 'asc' | 'desc') => {
    sortBy.value = columnKey;
    sortOrder.value = order;
    applyFilters();
};

const handlePerPageChange = (newPerPage: number) => {
    perPage.value = newPerPage;
    applyFilters();
};

const handlePageChange = (page: number) => {
    router.get(
        '/tenants',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            industry: industry.value || undefined,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    industry.value = '';
    applyFilters();
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AdminLayout
        title="Tenants"
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/dashboard' }, { label: 'Tenants' }]"
    >
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Tenants
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Manage organizations using the SathiSaaS platform.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    href="/tenants/create"
                    variant="primary"
                >
                    <template #prefix>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </template>
                    <span>Create Tenant</span>
                </Button>
            </div>
        </div>

        <!-- DataTable Container -->
        <div class="mt-6">
            <DataTable
                :columns="columns"
                :data="tenants"
                :search-value="search"
                :current-per-page="perPage"
                :sort-by="sortBy"
                :sort-order="sortOrder"
                search-placeholder="Search by organization name, slug, country..."
                @search="handleSearch"
                @sort="handleSort"
                @per-page-change="handlePerPageChange"
                @page-change="handlePageChange"
            >
                <!-- Filters Dropdown inside DataTable Header -->
                <template #filters>
                    <Dropdown width="w-56">
                        <template #trigger="{ isOpen }">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                :class="{ 'border-zinc-900 dark:border-white font-semibold': status || industry }"
                            >
                                <svg class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span>Filters</span>
                                <span v-if="status || industry" class="h-1.5 w-1.5 rounded-full bg-zinc-900 dark:bg-white" />
                            </button>
                        </template>

                        <template #default="{ close }">
                            <div class="p-3 space-y-3">
                                <p class="text-xs font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                                    Filter by Status
                                </p>
                                <select
                                    v-model="status"
                                    class="w-full rounded-md border border-zinc-200 bg-white p-1.5 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                    @change="applyFilters(); close()"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>

                                <button
                                    v-if="status || industry"
                                    type="button"
                                    class="w-full text-center text-xs font-medium text-rose-600 hover:underline pt-1"
                                    @click="resetFilters(); close()"
                                >
                                    Reset Filters
                                </button>
                            </div>
                        </template>
                    </Dropdown>
                </template>

                <!-- Custom Cell: Organization (Avatar + Name + Slug) -->
                <template #cell(organization)="{ item }">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-semibold text-xs text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200">
                            {{ item.name.substring(0, 2) }}
                        </div>
                        <div>
                            <Link
                                :href="`/tenants/${item.id}`"
                                class="font-semibold text-zinc-900 hover:underline dark:text-zinc-100"
                            >
                                {{ item.name }}
                            </Link>
                            <div class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                                {{ item.slug }}
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Custom Cell: Status Badge -->
                <template #cell(status)="{ item }">
                    <Badge :variant="item.status" />
                </template>

                <!-- Custom Cell: Created Date -->
                <template #cell(created_at)="{ item }">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                        {{ formatDate(item.created_at) }}
                    </span>
                </template>

                <!-- Custom Cell: Actions (View / Edit Buttons) -->
                <template #cell(actions)="{ item }">
                    <div class="flex items-center justify-end gap-1.5">
                        <Link
                            :href="`/tenants/${item.id}`"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors"
                            title="View Organization"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </Link>
                        <Link
                            :href="`/tenants/${item.id}/edit`"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors"
                            title="Edit Organization"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>
                    </div>
                </template>

                <!-- Custom Empty State Actions -->
                <template #empty-actions>
                    <Button
                        v-if="search || status"
                        variant="outline"
                        size="sm"
                        @click="resetFilters"
                    >
                        Reset Filters
                    </Button>
                    <Button
                        v-else
                        href="/tenants/create"
                        variant="primary"
                        size="sm"
                    >
                        Create First Tenant
                    </Button>
                </template>
            </DataTable>
        </div>
    </AdminLayout>
</template>
