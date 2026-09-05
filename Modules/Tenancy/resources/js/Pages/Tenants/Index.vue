<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Select from '@/components/Select.vue';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import ListGridToggle from '@/components/ListGridToggle.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import SearchInput from '@/components/SearchInput.vue';
import FilterButton from '@/components/FilterButton.vue';
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

const viewMode = ref<'list' | 'grid'>('list');
const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const industry = ref(props.filters?.industry ?? '');
const perPage = ref(props.filters?.per_page ?? 10);
const sortBy = ref(props.filters?.sort_by ?? 'created_at');
const sortOrder = ref<'asc' | 'desc'>(props.filters?.sort_order ?? 'desc');
const showFilters = ref(false);

const activeFiltersCount = computed(() => {
    return [status.value, industry.value].filter(Boolean).length;
});

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Pending', value: 'pending' },
    { label: 'Suspended', value: 'suspended' },
    { label: 'Cancelled', value: 'cancelled' },
];

const industryOptions = [
    { label: 'All Industries', value: '' },
    { label: 'Food & Beverage / Restaurant', value: 'restaurant' },
    { label: 'Retail & E-commerce', value: 'retail' },
    { label: 'Software & Technology', value: 'technology' },
    { label: 'Healthcare & Medical', value: 'healthcare' },
    { label: 'Financial Services', value: 'finance' },
    { label: 'Education & Training', value: 'education' },
    { label: 'Hospitality & Tourism', value: 'hospitality' },
    { label: 'Other', value: 'other' },
];

const applyFilters = () => {
    router.get(
        '/superadmin/tenants',
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

const handleSort = (columnKey: string) => {
    const nextOrder = sortBy.value === columnKey && sortOrder.value === 'asc' ? 'desc' : 'asc';
    sortBy.value = columnKey;
    sortOrder.value = nextOrder;
    applyFilters();
};

const handlePerPageChange = (newPerPage: number) => {
    perPage.value = newPerPage;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    industry.value = '';
    applyFilters();
};

const impersonateTenant = (tenantId: number) => {
    router.post(`/superadmin/tenants/${tenantId}/impersonate`);
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
    <SuperAdminLayout
        title="Organizations"
        :breadcrumbs="[{ label: 'Dashboard', href: '/superadmin/dashboard' }, { label: 'Organizations' }]"
    >
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Organizations
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Manage and monitor all client organizations on the multi-tenant platform.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    href="/superadmin/tenants/create"
                    variant="primary"
                >
                    <template #prefix>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </template>
                    <span>New Organization</span>
                </Button>
            </div>
        </div>

        <!-- Main Content Card (Accounting style) -->
        <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200/90 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <!-- Search & Controls Header -->
            <div class="p-4 sm:p-5 border-b border-zinc-200/90 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-900/60">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="flex-1 max-w-md">
                        <SearchInput
                            v-model="search"
                            placeholder="Search by organization name, slug, country..."
                            @search="applyFilters"
                            @clear="resetFilters"
                        />
                    </div>

                    <!-- Right Controls -->
                    <div class="flex items-center gap-2.5 self-end sm:self-auto">
                        <!-- List / Grid Toggle -->
                        <ListGridToggle v-model="viewMode" />

                        <!-- Per Page Selector -->
                        <PerPageSelector
                            v-model="perPage"
                            @change="handlePerPageChange"
                        />

                        <!-- Advanced Filter Toggle Button -->
                        <FilterButton
                            :show-filters="showFilters"
                            :count="activeFiltersCount"
                            @toggle="showFilters = !showFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Advanced Filters Row (Expandable) -->
            <div
                v-if="showFilters"
                class="p-4 sm:p-5 bg-zinc-50/90 border-b border-zinc-200/90 dark:bg-zinc-900/90 dark:border-zinc-800"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 items-end">
                    <!-- Status Filter -->
                    <div>
                        <Select
                            v-model="status"
                            label="Status"
                            :options="statusOptions"
                            placeholder="All Statuses"
                        />
                    </div>

                    <!-- Industry Filter -->
                    <div>
                        <Select
                            v-model="industry"
                            label="Industry"
                            :options="industryOptions"
                            placeholder="All Industries"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="primary"
                            size="sm"
                            @click="applyFilters"
                        >
                            Apply Filters
                        </Button>
                        <Button
                            v-if="search || status || industry"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="resetFilters"
                        >
                            Clear
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Content Area: Empty State -->
            <div v-if="tenants.data.length === 0" class="p-12 text-center">
                <EmptyState
                    title="No Organizations Found"
                    description="There are no organizations matching your selected search or filter criteria."
                >
                    <template #actions>
                        <Button
                            v-if="search || status || industry"
                            variant="outline"
                            size="sm"
                            @click="resetFilters"
                        >
                            Clear Filters
                        </Button>
                        <Button
                            v-else
                            href="/superadmin/tenants/create"
                            variant="primary"
                            size="sm"
                        >
                            Create First Organization
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <!-- Content Area: List View (Table) -->
            <div v-else-if="viewMode === 'list'" class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                    <thead class="border-b border-zinc-200 bg-zinc-100/75 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                        <tr>
                            <th
                                scope="col"
                                class="h-11 px-4 text-left align-middle cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors select-none"
                                @click="handleSort('name')"
                            >
                                <div class="inline-flex items-center gap-1.5">
                                    <span>Organization</span>
                                    <svg
                                        v-if="sortBy === 'name' && sortOrder === 'asc'"
                                        class="h-3.5 w-3.5 text-primary-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                    </svg>
                                    <svg
                                        v-else-if="sortBy === 'name' && sortOrder === 'desc'"
                                        class="h-3.5 w-3.5 text-primary-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Industry</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Country</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Currency</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Status</th>
                            <th
                                scope="col"
                                class="h-11 px-4 text-left align-middle cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors select-none"
                                @click="handleSort('created_at')"
                            >
                                <div class="inline-flex items-center gap-1.5">
                                    <span>Created</span>
                                    <svg
                                        v-if="sortBy === 'created_at' && sortOrder === 'asc'"
                                        class="h-3.5 w-3.5 text-primary-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                    </svg>
                                    <svg
                                        v-else-if="sortBy === 'created_at' && sortOrder === 'desc'"
                                        class="h-3.5 w-3.5 text-primary-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </th>
                            <th scope="col" class="h-11 px-4 text-right align-middle">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/80 dark:divide-zinc-800/80">
                        <tr
                            v-for="item in tenants.data"
                            :key="item.id"
                            class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40"
                        >
                            <!-- Organization -->
                            <td class="p-4 align-middle font-medium text-zinc-900 dark:text-zinc-100">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-semibold text-xs text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200/60 dark:border-zinc-700/60">
                                        {{ item.name.substring(0, 2) }}
                                    </div>
                                    <div>
                                        <Link
                                            :href="`/superadmin/tenants/${item.id}`"
                                            class="font-semibold text-zinc-900 hover:underline dark:text-white"
                                        >
                                            {{ item.name }}
                                        </Link>
                                        <div class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                                            {{ item.slug }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Industry -->
                            <td class="px-4 py-4 text-xs font-medium text-zinc-700 dark:text-zinc-300 capitalize">
                                {{ item.industry || '—' }}
                            </td>

                            <!-- Country -->
                            <td class="px-4 py-4 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ item.country_code || '—' }}
                            </td>

                            <!-- Currency -->
                            <td class="px-4 py-4 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                {{ item.currency || '—' }}
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <Badge :variant="item.status" />
                            </td>

                            <!-- Created -->
                            <td class="px-4 py-4 font-mono text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>

                            <!-- Actions (Teleported Dropdown with colored icons) -->
                            <td class="p-4 align-middle text-right whitespace-nowrap">
                                <Dropdown align="right" width="w-44">
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
                                        <div class="py-1 text-xs">
                                            <Link
                                                :href="`/superadmin/tenants/${item.id}`"
                                                class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                                @click="close"
                                            >
                                                <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>View Organization</span>
                                            </Link>

                                            <button
                                                v-if="item.status === 'active'"
                                                type="button"
                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-indigo-700 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40 transition-colors rounded-md font-medium text-left"
                                                @click="impersonateTenant(item.id); close()"
                                            >
                                                <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                                </svg>
                                                <span>Login as Admin</span>
                                            </button>

                                            <Link
                                                :href="`/superadmin/tenants/${item.id}/edit`"
                                                class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                                @click="close"
                                            >
                                                <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Edit Organization</span>
                                            </Link>
                                        </div>
                                    </template>
                                </Dropdown>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Content Area: Grid View (Accounting-style Cards) -->
            <div
                v-else-if="viewMode === 'grid'"
                class="p-5 bg-zinc-50/30 dark:bg-zinc-950/30"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <div
                        v-for="item in tenants.data"
                        :key="item.id"
                        class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 transition-all"
                    >
                        <!-- Top Info -->
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-bold text-sm text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700">
                                    {{ item.name.substring(0, 2) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <Link
                                        :href="`/superadmin/tenants/${item.id}`"
                                        class="font-semibold text-sm text-zinc-900 hover:underline dark:text-white truncate block"
                                        :title="item.name"
                                    >
                                        {{ item.name }}
                                    </Link>
                                    <span class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500 truncate block">
                                        {{ item.slug }}
                                    </span>
                                </div>
                            </div>

                            <!-- Details -->
                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Industry</span>
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200 capitalize truncate block">
                                        {{ item.industry || '—' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Country</span>
                                        <span class="text-zinc-800 dark:text-zinc-200 font-medium">
                                            {{ item.country_code || '—' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Currency</span>
                                        <span class="text-zinc-800 dark:text-zinc-200 font-mono font-medium">
                                            {{ item.currency || '—' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-1">
                                    <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Created</span>
                                    <span class="font-mono text-zinc-600 dark:text-zinc-400 text-[11px]">
                                        {{ formatDate(item.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-4 pt-3 border-t border-dashed border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <!-- Status Badge -->
                            <Badge :variant="item.status" />

                            <!-- Actions Dropdown -->
                            <Dropdown align="right" width="w-44">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white transition-colors cursor-pointer"
                                        title="Actions"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>

                                <template #default="{ close }">
                                    <div class="py-1 text-xs">
                                        <Link
                                            :href="`/superadmin/tenants/${item.id}`"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>View Organization</span>
                                        </Link>

                                        <Link
                                            :href="`/superadmin/tenants/${item.id}/edit`"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit Organization</span>
                                        </Link>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Footer Pagination (Inside the card container) -->
            <div
                v-if="tenants.total > 0"
                class="border-t border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
            >
                <Pagination :data="tenants" />
            </div>
        </div>
    </SuperAdminLayout>
</template>
