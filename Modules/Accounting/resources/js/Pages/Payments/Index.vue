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

interface CustomerBrief {
    id: number;
    name: string;
    customer_code: string;
}

interface BankAccountBrief {
    id: number;
    code: string;
    name: string;
}

interface PaymentItem {
    id: number;
    public_id: string;
    payment_number: string;
    payment_date: string;
    amount: string;
    currency: string;
    payment_method: string;
    reference: string | null;
    status: string;
    customer?: CustomerBrief | null;
    bank_account?: BankAccountBrief | null;
}

interface Props {
    payments: PaginatedData<PaymentItem>;
    stats: {
        total_collected: string;
        posted_count: number;
        draft_count: number;
    };
    filters: {
        search?: string;
        status?: string;
        customer_id?: string;
    };
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('accounting.manage') || can('accounting.payments.manage'));

const columns: TableColumn[] = [
    { key: 'receipt', label: 'Receipt #', sortable: true },
    { key: 'customer', label: 'Customer' },
    { key: 'payment_date', label: 'Date', sortable: true },
    { key: 'account', label: 'Deposited Account' },
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
        '/admin/accounting/payments',
        {
            search: query.trim() || undefined,
            status: selectedStatus.value || undefined,
            customer_id: props.filters.customer_id || undefined,
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
        '/admin/accounting/payments',
        {
            search: props.filters.search || undefined,
            status: selectedStatus.value || undefined,
            customer_id: props.filters.customer_id || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const handlePageChange = (page: number) => {
    router.get(
        '/admin/accounting/payments',
        {
            page,
            search: props.filters.search || undefined,
            status: selectedStatus.value || undefined,
            customer_id: props.filters.customer_id || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const handlePerPageChange = (perPage: number) => {
    router.get(
        '/admin/accounting/payments',
        {
            per_page: perPage,
            search: props.filters.search || undefined,
            status: selectedStatus.value || undefined,
            customer_id: props.filters.customer_id || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const formatMoney = (val: string | number | undefined) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getStatusVariant = (status: string): 'active' | 'pending' | 'suspended' | 'cancelled' | 'neutral' | 'default' => {
    switch (status) {
        case 'posted':
            return 'active';
        case 'draft':
            return 'pending';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <Head title="Customer Receipts - Accounting" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Customer Receipts
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-zinc-400">
                        Record and allocate customer payments against outstanding sales invoices.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="canManage"
                        href="/admin/accounting/payments/create"
                    >
                        <Button>
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Record Receipt
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatsCard
                    title="Total Collected"
                    :value="`$${formatMoney(stats.total_collected)}`"
                    badge-color="emerald"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Posted Receipts"
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
                    title="Pending Allocation (Draft)"
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
                search-placeholder="Search receipt #, customer, ref..."
                filterable
                empty-title="No customer receipts found"
                empty-description="Record receipts collected from clients and allocate them to invoices."
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
                    <Link
                        v-if="canManage"
                        href="/admin/accounting/payments/create"
                    >
                        <Button size="sm">
                            <svg class="-ml-1 mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Receipt
                        </Button>
                    </Link>
                </template>

                <!-- Empty Actions Slot -->
                <template #empty-actions>
                    <Link
                        v-if="canManage"
                        href="/admin/accounting/payments/create"
                    >
                        <Button size="sm">
                            Record First Receipt
                        </Button>
                    </Link>
                </template>

                <!-- Custom Cell: Receipt # -->
                <template #cell(receipt)="{ item }">
                    <Link
                        :href="`/admin/accounting/payments/${item.public_id || item.id}`"
                        class="font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                    >
                        {{ item.payment_number }}
                    </Link>
                    <div v-if="item.reference" class="text-xs text-slate-400 dark:text-zinc-500">
                        Ref: {{ item.reference }}
                    </div>
                </template>

                <!-- Custom Cell: Customer -->
                <template #cell(customer)="{ item }">
                    <div v-if="item.customer" class="font-medium text-slate-900 dark:text-white">
                        <Link
                            :href="`/admin/accounting/customers/${item.customer.id}`"
                            class="hover:underline"
                        >
                            {{ item.customer.name }}
                        </Link>
                        <span class="block text-xs text-slate-400 dark:text-zinc-500">
                            {{ item.customer.customer_code }}
                        </span>
                    </div>
                    <span v-else class="text-slate-400 dark:text-zinc-500">—</span>
                </template>

                <!-- Custom Cell: Date -->
                <template #cell(payment_date)="{ item }">
                    <span class="text-xs font-medium text-slate-700 dark:text-zinc-300">
                        {{ item.payment_date }}
                    </span>
                </template>

                <!-- Custom Cell: Account -->
                <template #cell(account)="{ item }">
                    <span v-if="item.bank_account" class="inline-flex items-center gap-1 font-mono text-xs text-indigo-600 dark:text-indigo-400">
                        <span class="font-semibold">{{ item.bank_account.code }}</span>
                        <span class="text-slate-500 dark:text-zinc-400">- {{ item.bank_account.name }}</span>
                    </span>
                    <span v-else class="text-slate-400 dark:text-zinc-500">—</span>
                </template>

                <!-- Custom Cell: Method -->
                <template #cell(method)="{ item }">
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium capitalize text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ item.payment_method?.replace('_', ' ') }}
                    </span>
                </template>

                <!-- Custom Cell: Amount -->
                <template #cell(amount)="{ item }">
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                        ${{ formatMoney(item.amount) }}
                    </span>
                </template>

                <!-- Custom Cell: Status -->
                <template #cell(status)="{ item }">
                    <Badge :variant="getStatusVariant(item.status)">
                        {{ item.status }}
                    </Badge>
                </template>

                <!-- Custom Cell: Actions -->
                <template #cell(actions)="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <Link
                            :href="`/admin/accounting/payments/${item.public_id || item.id}`"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
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
                                :href="`/admin/accounting/payments/${item.public_id || item.id}`"
                                class="font-bold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                            >
                                {{ item.payment_number }}
                            </Link>
                            <div v-if="item.customer" class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400">
                                {{ item.customer.name }} ({{ item.customer.customer_code }})
                            </div>
                        </div>
                        <Badge :variant="getStatusVariant(item.status)">
                            {{ item.status }}
                        </Badge>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs border-t border-slate-100 pt-3 dark:border-zinc-800">
                        <div>
                            <span class="text-slate-400 dark:text-zinc-500">Date:</span>
                            <span class="ml-1 font-medium text-slate-700 dark:text-zinc-300">{{ item.payment_date }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 dark:text-zinc-500">Method:</span>
                            <span class="ml-1 font-medium capitalize text-slate-700 dark:text-zinc-300">{{ item.payment_method?.replace('_', ' ') }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-zinc-800">
                        <span class="text-base font-bold text-emerald-600 dark:text-emerald-400">
                            ${{ formatMoney(item.amount) }}
                        </span>
                        <Link
                            :href="`/admin/accounting/payments/${item.public_id || item.id}`"
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
