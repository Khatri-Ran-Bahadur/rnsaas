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

interface VendorRef {
    id: number;
    vendor_code: string;
    name: string;
}

interface AccountRef {
    id: number;
    code: string;
    name: string;
}

interface PaymentItem {
    id: number;
    payment_number: string;
    payment_date: string;
    amount: string;
    currency: string;
    payment_method: string;
    reference: string | null;
    status: {
        value: string;
        label: string;
    } | string;
    vendor?: VendorRef | null;
    bank_account?: AccountRef | null;
}

interface Props {
    payments: PaginatedData<PaymentItem>;
    filters: {
        search?: string;
        status?: string;
    };
    stats: {
        total_paid: string;
        posted_count: number;
        draft_count: number;
    };
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('accounting.manage') || can('accounting.payments.manage'));

const columns: TableColumn[] = [
    { key: 'payment_number', label: 'Payment #', sortable: true },
    { key: 'vendor', label: 'Vendor' },
    { key: 'payment_date', label: 'Date', sortable: true },
    { key: 'bank_account', label: 'Paid From' },
    { key: 'method', label: 'Method' },
    { key: 'amount', label: 'Amount', align: 'right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const selectedStatus = ref(props.filters.status ?? '');

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Draft', value: 'draft' },
    { label: 'Posted', value: 'posted' },
];

const handleSearch = (query: string) => {
    router.get(
        '/admin/accounting/vendor-payments',
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
        '/admin/accounting/vendor-payments',
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
        '/admin/accounting/vendor-payments',
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
        '/admin/accounting/vendor-payments',
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

const getStatusValue = (status: PaymentItem['status']) => {
    if (typeof status === 'object' && status !== null) {
        return status.value;
    }
    return status;
};

const getStatusVariant = (status: PaymentItem['status']): 'active' | 'pending' | 'suspended' | 'cancelled' | 'neutral' | 'default' => {
    const val = getStatusValue(status);
    return val === 'posted' ? 'active' : 'pending';
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
        title="Vendor Payments"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Vendor Payments' },
        ]"
    >
        <Head title="Vendor Payments - Accounting" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Vendor Payments
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Record disbursements to suppliers and allocate against outstanding purchase bills.
                    </p>
                </div>

                <div v-if="canManage" class="flex items-center gap-3">
                    <Link href="/admin/accounting/vendor-payments/create">
                        <Button variant="primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Record Payment
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatsCard
                    title="Total Disbursed"
                    :value="`$${formatCurrency(stats.total_paid)}`"
                    badge-color="emerald"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Posted Payments"
                    :value="stats.posted_count"
                    badge-color="blue"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Draft / Unposted"
                    :value="stats.draft_count"
                    badge-color="amber"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>
            </div>

            <!-- Unified DataTable with Pagination -->
            <DataTable
                :columns="columns"
                :data="payments"
                :search-value="filters.search ?? ''"
                search-placeholder="Search by payment #, reference, or vendor..."
                filterable
                empty-title="No vendor payments found"
                empty-description="Record disbursements and allocate them to supplier purchase bills."
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
                    <Link v-if="canManage" href="/admin/accounting/vendor-payments/create">
                        <Button size="sm">
                            <svg class="-ml-1 mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Record Payment
                        </Button>
                    </Link>
                </template>

                <!-- Empty Actions Slot -->
                <template #empty-actions>
                    <Link v-if="canManage" href="/admin/accounting/vendor-payments/create">
                        <Button size="sm">
                            Record First Payment
                        </Button>
                    </Link>
                </template>

                <!-- Custom Cell: Payment # -->
                <template #cell(payment_number)="{ item }">
                    <Link
                        :href="`/admin/accounting/vendor-payments/${item.id}`"
                        class="font-mono font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        {{ item.payment_number }}
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

                <!-- Custom Cell: Payment Date -->
                <template #cell(payment_date)="{ item }">
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        {{ item.payment_date }}
                    </span>
                </template>

                <!-- Custom Cell: Bank Account -->
                <template #cell(bank_account)="{ item }">
                    <span v-if="item.bank_account" class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                        {{ item.bank_account.code }} - {{ item.bank_account.name }}
                    </span>
                    <span v-else class="text-zinc-400">—</span>
                </template>

                <!-- Custom Cell: Method -->
                <template #cell(method)="{ item }">
                    <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium capitalize text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ item.payment_method?.replace('_', ' ') }}
                    </span>
                </template>

                <!-- Custom Cell: Amount -->
                <template #cell(amount)="{ item }">
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        ${{ formatCurrency(item.amount) }}
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
                            :href="`/admin/accounting/vendor-payments/${item.id}`"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            View & Allocate &rarr;
                        </Link>
                    </div>
                </template>

                <!-- Grid Card View -->
                <template #grid-card="{ item }">
                    <div class="flex items-start justify-between">
                        <div>
                            <Link
                                :href="`/admin/accounting/vendor-payments/${item.id}`"
                                class="font-mono font-bold text-zinc-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                            >
                                {{ item.payment_number }}
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
                            <span class="text-zinc-400">Date:</span>
                            <span class="ml-1 font-medium text-zinc-700 dark:text-zinc-300">{{ item.payment_date }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400">Method:</span>
                            <span class="ml-1 font-medium capitalize text-zinc-700 dark:text-zinc-300">{{ item.payment_method?.replace('_', ' ') }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            ${{ formatCurrency(item.amount) }}
                        </span>
                        <Link
                            :href="`/admin/accounting/vendor-payments/${item.id}`"
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
