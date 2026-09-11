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

interface VendorItem {
    id: number;
    vendor_code: string;
    name: string;
}

interface BillItem {
    id: number;
    bill_number: string;
    bill_date: string;
    due_date: string;
    currency: string;
    total_amount: string;
    reference: string | null;
    status: {
        value: string;
        label: string;
    } | string;
    vendor?: VendorItem | null;
}

interface Props {
    bills: PaginatedData<BillItem>;
    filters: {
        search?: string;
        status?: string;
    };
    stats: {
        total_amount: string;
        draft_count: number;
        issued_count: number;
        posted_count: number;
    };
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('accounting.manage') || can('accounting.bills.manage'));

const columns: TableColumn[] = [
    { key: 'bill_number', label: 'Bill #', sortable: true },
    { key: 'vendor', label: 'Vendor' },
    { key: 'dates', label: 'Bill / Due Date' },
    { key: 'total_amount', label: 'Total Amount', align: 'right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const selectedStatus = ref(props.filters.status ?? '');

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Draft', value: 'draft' },
    { label: 'Issued', value: 'issued' },
    { label: 'Posted', value: 'posted' },
    { label: 'Void', value: 'void' },
];

const handleSearch = (query: string) => {
    router.get(
        '/admin/accounting/purchase-bills',
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
        '/admin/accounting/purchase-bills',
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
        '/admin/accounting/purchase-bills',
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
        '/admin/accounting/purchase-bills',
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

const getStatusValue = (status: BillItem['status']) => {
    if (typeof status === 'object' && status !== null) {
        return status.value;
    }
    return status;
};

const getStatusVariant = (status: BillItem['status']): 'active' | 'pending' | 'suspended' | 'cancelled' | 'neutral' | 'default' => {
    const val = getStatusValue(status);
    switch (val) {
        case 'posted':
            return 'active';
        case 'issued':
            return 'default';
        case 'draft':
            return 'pending';
        case 'void':
            return 'cancelled';
        default:
            return 'neutral';
    }
};

const formatCurrency = (val: string | number) => {
    const num = parseFloat(String(val) || '0');
    return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};
</script>

<template>
    <OrganizationLayout
        title="Purchase Bills"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Purchase Bills' },
        ]"
    >
        <Head title="Purchase Bills - Accounting" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Purchase Bills
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Track vendor invoices, manage multi-line expenses, taxes, and post to General Ledger.
                    </p>
                </div>

                <div v-if="canManage" class="flex items-center gap-3">
                    <Link href="/admin/accounting/purchase-bills/create">
                        <Button variant="primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Purchase Bill
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <StatsCard
                    title="Total Invoiced"
                    :value="`$${formatCurrency(stats.total_amount)}`"
                    badge-color="indigo"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Draft Bills"
                    :value="stats.draft_count"
                    badge-color="zinc"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Issued (Unposted)"
                    :value="stats.issued_count"
                    badge-color="blue"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Posted to Ledger"
                    :value="stats.posted_count"
                    badge-color="emerald"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>
            </div>

            <!-- Unified DataTable with Pagination -->
            <DataTable
                :columns="columns"
                :data="bills"
                :search-value="filters.search ?? ''"
                search-placeholder="Search by bill #, reference, or vendor..."
                filterable
                empty-title="No purchase bills found"
                empty-description="Record supplier invoices to track payables and post to the General Ledger."
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
                    <Link v-if="canManage" href="/admin/accounting/purchase-bills/create">
                        <Button size="sm">
                            <svg class="-ml-1 mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Bill
                        </Button>
                    </Link>
                </template>

                <!-- Empty Actions Slot -->
                <template #empty-actions>
                    <Link v-if="canManage" href="/admin/accounting/purchase-bills/create">
                        <Button size="sm">
                            Create First Purchase Bill
                        </Button>
                    </Link>
                </template>

                <!-- Custom Cell: Bill # -->
                <template #cell(bill_number)="{ item }">
                    <Link
                        :href="`/admin/accounting/purchase-bills/${item.id}`"
                        class="font-mono font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        {{ item.bill_number }}
                    </Link>
                    <div v-if="item.reference" class="text-xs text-zinc-400">
                        Ref: {{ item.reference }}
                    </div>
                </template>

                <!-- Custom Cell: Vendor -->
                <template #cell(vendor)="{ item }">
                    <div v-if="item.vendor">
                        <Link
                            :href="`/admin/accounting/vendors/${item.vendor.id}`"
                            class="font-medium text-zinc-900 hover:text-indigo-600 dark:text-zinc-100 dark:hover:text-indigo-400"
                        >
                            {{ item.vendor.name }}
                        </Link>
                        <div class="font-mono text-xs text-zinc-400">{{ item.vendor.vendor_code }}</div>
                    </div>
                    <span v-else class="text-zinc-400">—</span>
                </template>

                <!-- Custom Cell: Dates -->
                <template #cell(dates)="{ item }">
                    <div class="text-xs text-zinc-900 dark:text-zinc-100">Bill: {{ item.bill_date }}</div>
                    <div class="text-xs text-zinc-400">Due: {{ item.due_date }}</div>
                </template>

                <!-- Custom Cell: Total Amount -->
                <template #cell(total_amount)="{ item }">
                    <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">
                        ${{ formatCurrency(item.total_amount) }}
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
                            :href="`/admin/accounting/purchase-bills/${item.id}`"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            View &rarr;
                        </Link>
                        <Link
                            v-if="canManage && getStatusValue(item.status) === 'draft'"
                            :href="`/admin/accounting/purchase-bills/${item.id}/edit`"
                            class="text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            Edit
                        </Link>
                    </div>
                </template>

                <!-- Grid Card View -->
                <template #grid-card="{ item }">
                    <div class="flex items-start justify-between">
                        <div>
                            <Link
                                :href="`/admin/accounting/purchase-bills/${item.id}`"
                                class="font-mono font-bold text-zinc-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                            >
                                {{ item.bill_number }}
                            </Link>
                            <div v-if="item.vendor" class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ item.vendor.name }} ({{ item.vendor.vendor_code }})
                            </div>
                        </div>
                        <Badge :variant="getStatusVariant(item.status)">
                            {{ getStatusValue(item.status) }}
                        </Badge>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <div>
                            <span class="text-zinc-400">Bill Date:</span>
                            <span class="ml-1 font-medium text-zinc-700 dark:text-zinc-300">{{ item.bill_date }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400">Due Date:</span>
                            <span class="ml-1 font-medium text-zinc-700 dark:text-zinc-300">{{ item.due_date }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">
                            ${{ formatCurrency(item.total_amount) }}
                        </span>
                        <Link
                            :href="`/admin/accounting/purchase-bills/${item.id}`"
                            class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            Details &rarr;
                        </Link>
                    </div>
                </template>
            </DataTable>
        </div>
    </OrganizationLayout>
</template>
