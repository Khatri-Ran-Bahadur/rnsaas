<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import {
    Button,
    Badge,
    DataTable,
    StatsCard,
    Select,
    type TableColumn,
} from '@/components';
import type { PaginatedData } from '@/types/tenancy';
import { usePermissions } from '@/composables/usePermissions';

interface PayableAccount {
    id: number;
    code: string;
    name: string;
}

interface VendorItem {
    id: number;
    vendor_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    currency: string;
    status: {
        value: string;
        label: string;
    } | string;
    payable_account?: PayableAccount | null;
    credit_limit?: string;
    payment_terms_days?: number;
}

interface Props {
    vendors: PaginatedData<VendorItem>;
    filters: {
        search?: string;
        status?: string;
    };
    stats: {
        total: number;
        active: number;
        inactive: number;
    };
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('accounting.manage') || can('accounting.vendors.manage'));

const columns: TableColumn[] = [
    { key: 'vendor_code', label: 'Vendor Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'contact', label: 'Contact' },
    { key: 'account', label: 'Payable Account' },
    { key: 'terms', label: 'Terms' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const selectedStatus = ref(props.filters.status ?? '');

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const handleSearch = (query: string) => {
    router.get(
        '/admin/accounting/vendors',
        {
            search: query.trim() || undefined,
            status: selectedStatus.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const handleStatusChange = (val: string | number) => {
    selectedStatus.value = String(val);
    router.get(
        '/admin/accounting/vendors',
        {
            search: props.filters.search || undefined,
            status: selectedStatus.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const handlePageChange = (page: number) => {
    router.get(
        '/admin/accounting/vendors',
        {
            page,
            search: props.filters.search || undefined,
            status: selectedStatus.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const handlePerPageChange = (perPage: number) => {
    router.get(
        '/admin/accounting/vendors',
        {
            per_page: perPage,
            search: props.filters.search || undefined,
            status: selectedStatus.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const toggleStatus = (vendor: VendorItem) => {
    if (!canManage.value) return;
    router.post(
        `/admin/accounting/vendors/${vendor.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
        }
    );
};

const getStatusValue = (status: VendorItem['status']) => {
    if (typeof status === 'object' && status !== null) {
        return status.value;
    }
    return status;
};

const getStatusVariant = (status: VendorItem['status']): 'active' | 'pending' | 'suspended' | 'cancelled' | 'neutral' | 'default' => {
    const val = getStatusValue(status);
    return val === 'active' ? 'active' : 'suspended';
};
</script>

<template>
    <OrganizationLayout
        title="Vendors"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Vendors' },
        ]"
    >
        <Head title="Vendors - Accounting" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Vendors / Suppliers
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Manage vendor accounts payable subledgers, default expense mapping, and credit terms.
                    </p>
                </div>

                <div v-if="canManage" class="flex items-center gap-3">
                    <Link href="/admin/accounting/vendors/create">
                        <Button variant="primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Vendor
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatsCard
                    title="Total Vendors"
                    :value="stats.total"
                    badge-color="indigo"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Active Vendors"
                    :value="stats.active"
                    badge-color="emerald"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Inactive Vendors"
                    :value="stats.inactive"
                    badge-color="zinc"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </template>
                </StatsCard>
            </div>

            <!-- Unified DataTable with Pagination -->
            <DataTable
                :columns="columns"
                :data="vendors"
                :search-value="filters.search ?? ''"
                search-placeholder="Search by name, vendor code, or email..."
                filterable
                empty-title="No vendors found"
                empty-description="Create vendor accounts to manage accounts payable and bills."
                @search="handleSearch"
                @page-change="handlePageChange"
                @per-page-change="handlePerPageChange"
            >
                <!-- Filters Slot: Modern Select Dropdown -->
                <template #filters>
                    <div class="w-48">
                        <Select
                            :model-value="selectedStatus"
                            :options="statusOptions"
                            placeholder="All Statuses"
                            @update:model-value="handleStatusChange"
                        />
                    </div>
                </template>

                <!-- Actions Slot -->
                <template #actions>
                    <Link v-if="canManage" href="/admin/accounting/vendors/create">
                        <Button size="sm">
                            <svg class="-ml-1 mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Vendor
                        </Button>
                    </Link>
                </template>

                <!-- Empty Actions Slot -->
                <template #empty-actions>
                    <Link v-if="canManage" href="/admin/accounting/vendors/create">
                        <Button size="sm">
                            Create First Vendor
                        </Button>
                    </Link>
                </template>

                <!-- Custom Cell: Vendor Code -->
                <template #cell(vendor_code)="{ item }">
                    <Link
                        :href="`/admin/accounting/vendors/${item.id}`"
                        class="font-mono font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        {{ item.vendor_code }}
                    </Link>
                </template>

                <!-- Custom Cell: Name -->
                <template #cell(name)="{ item }">
                    <Link
                        :href="`/admin/accounting/vendors/${item.id}`"
                        class="font-medium text-zinc-900 hover:text-indigo-600 dark:text-zinc-100 dark:hover:text-indigo-400"
                    >
                        {{ item.name }}
                    </Link>
                </template>

                <!-- Custom Cell: Contact -->
                <template #cell(contact)="{ item }">
                    <div v-if="item.email" class="text-zinc-600 dark:text-zinc-300">{{ item.email }}</div>
                    <div v-if="item.phone" class="text-xs text-zinc-400 dark:text-zinc-500">{{ item.phone }}</div>
                    <span v-if="!item.email && !item.phone" class="text-zinc-400 dark:text-zinc-500">—</span>
                </template>

                <!-- Custom Cell: Payable Account -->
                <template #cell(account)="{ item }">
                    <span v-if="item.payable_account" class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                        {{ item.payable_account.code }} - {{ item.payable_account.name }}
                    </span>
                    <span v-else class="text-zinc-400 dark:text-zinc-500">Default AP</span>
                </template>

                <!-- Custom Cell: Terms -->
                <template #cell(terms)="{ item }">
                    <span class="text-zinc-600 dark:text-zinc-300">
                        {{ item.payment_terms_days ?? 0 }} days
                    </span>
                </template>

                <!-- Custom Cell: Status -->
                <template #cell(status)="{ item }">
                    <Badge :variant="getStatusVariant(item.status)">
                        {{ getStatusValue(item.status) }}
                    </Badge>
                </template>

                <!-- Custom Cell: Actions -->
                <template #cell(actions)="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <Link
                            :href="`/admin/accounting/reports/vendors/${item.id}/statement`"
                            class="text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            Statement
                        </Link>
                        <Link
                            v-if="canManage"
                            :href="`/admin/accounting/vendors/${item.id}/edit`"
                            class="text-xs text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Edit
                        </Link>
                        <button
                            v-if="canManage"
                            type="button"
                            class="text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 cursor-pointer"
                            @click="toggleStatus(item)"
                        >
                            {{ getStatusValue(item.status) === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </template>

                <!-- Grid Card View -->
                <template #grid-card="{ item }">
                    <div class="flex items-start justify-between">
                        <div>
                            <Link
                                :href="`/admin/accounting/vendors/${item.id}`"
                                class="font-bold text-zinc-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                            >
                                {{ item.name }}
                            </Link>
                            <div class="font-mono text-xs text-indigo-600 dark:text-indigo-400">
                                {{ item.vendor_code }}
                            </div>
                        </div>
                        <Badge :variant="getStatusVariant(item.status)">
                            {{ getStatusValue(item.status) }}
                        </Badge>
                    </div>

                    <div class="mt-3 space-y-1 text-xs text-zinc-600 dark:text-zinc-300 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <div v-if="item.email">📧 {{ item.email }}</div>
                        <div v-if="item.phone">📞 {{ item.phone }}</div>
                        <div>Terms: {{ item.payment_terms_days ?? 0 }} days</div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <Link
                            :href="`/admin/accounting/reports/vendors/${item.id}/statement`"
                            class="text-xs text-zinc-500 hover:underline"
                        >
                            Statement
                        </Link>
                        <div class="flex items-center gap-2">
                            <Link
                                v-if="canManage"
                                :href="`/admin/accounting/vendors/${item.id}/edit`"
                                class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                Edit
                            </Link>
                            <button
                                v-if="canManage"
                                type="button"
                                class="text-xs text-zinc-400 hover:text-zinc-600"
                                @click="toggleStatus(item)"
                            >
                                {{ getStatusValue(item.status) === 'active' ? 'Deactivate' : 'Activate' }}
                            </button>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </OrganizationLayout>
</template>
