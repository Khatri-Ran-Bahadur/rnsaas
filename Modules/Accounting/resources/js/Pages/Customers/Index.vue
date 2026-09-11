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

interface ReceivableAccount {
    id: number;
    code: string;
    name: string;
}

interface CustomerItem {
    id: number;
    public_id: string;
    customer_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    status: string;
    is_active: boolean;
    receivable_account?: ReceivableAccount | null;
    credit_limit: string;
    payment_terms_days: number;
}

interface Props {
    customers: PaginatedData<CustomerItem>;
    filters: {
        search?: string;
        status?: string;
    };
    stats: {
        total_customers: number;
        active_customers: number;
        total_receivables: string;
        overdue_receivables: string;
    };
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('accounting.manage') || can('accounting.customers.manage'));

const columns: TableColumn[] = [
    { key: 'customer', label: 'Customer', sortable: true },
    { key: 'contact', label: 'Contact Information' },
    { key: 'account', label: 'Receivable Account' },
    { key: 'terms', label: 'Terms & Limit' },
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
        '/admin/accounting/customers',
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
        '/admin/accounting/customers',
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
        '/admin/accounting/customers',
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
        '/admin/accounting/customers',
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

const toggleStatus = (customer: CustomerItem) => {
    if (!canManage.value) return;
    router.post(
        `/admin/accounting/customers/${customer.public_id || customer.id}/status`,
        {},
        { preserveScroll: true }
    );
};

const formatMoney = (val: string | number | undefined) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Customers - Accounting" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Customers
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Manage your client directory, accounts receivable mappings, and credit limits.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="canManage"
                        href="/admin/accounting/customers/create"
                    >
                        <Button>
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Customer
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Modern Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total Customers"
                    :value="stats.total_customers"
                    badge-color="blue"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Active Directory"
                    :value="stats.active_customers"
                    badge-color="emerald"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Total Receivables"
                    :value="`$${formatMoney(stats.total_receivables)}`"
                    badge-color="indigo"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Overdue Amount"
                    :value="`$${formatMoney(stats.overdue_receivables)}`"
                    badge-color="rose"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>
            </div>

            <!-- Unified DataTable with Pagination -->
            <DataTable
                :columns="columns"
                :data="customers"
                :search-value="filters.search || ''"
                search-placeholder="Search by customer name, code, or email..."
                filterable
                empty-title="No customers found"
                empty-description="Create your first client account to start generating sales invoices and tracking receivables."
                @search="handleSearch"
                @page-change="handlePageChange"
                @per-page-change="handlePerPageChange"
            >
                <template #filters>
                    <div class="w-40">
                        <Select
                            :model-value="selectedStatus"
                            :options="statusOptions"
                            placeholder="Filter status..."
                            @change="handleStatusChange"
                        />
                    </div>
                </template>

                <!-- Custom Cell: Customer Name & Code -->
                <template #cell(customer)="{ item }">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-xs text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 ring-1 ring-indigo-500/20">
                            {{ item.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <div>
                            <Link
                                :href="`/admin/accounting/customers/${item.public_id || item.id}`"
                                class="font-semibold text-zinc-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400 transition-colors"
                            >
                                {{ item.name }}
                            </Link>
                            <div class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                                {{ item.customer_code }}
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Custom Cell: Contact Information -->
                <template #cell(contact)="{ item }">
                    <div class="text-xs space-y-0.5">
                        <div v-if="item.email" class="text-zinc-800 dark:text-zinc-200">
                            {{ item.email }}
                        </div>
                        <div v-if="item.phone" class="text-zinc-400 dark:text-zinc-500">
                            {{ item.phone }}
                        </div>
                        <span v-if="!item.email && !item.phone" class="text-zinc-400">—</span>
                    </div>
                </template>

                <!-- Custom Cell: Receivable Account -->
                <template #cell(account)="{ item }">
                    <div v-if="item.receivable_account" class="text-xs">
                        <span class="font-mono font-medium text-zinc-700 dark:text-zinc-300">{{ item.receivable_account.code }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400"> - {{ item.receivable_account.name }}</span>
                    </div>
                    <span v-else class="text-xs text-zinc-400 italic">Default AR</span>
                </template>

                <!-- Custom Cell: Terms & Credit Limit -->
                <template #cell(terms)="{ item }">
                    <div class="text-xs text-zinc-700 dark:text-zinc-300">
                        <div><span class="text-zinc-400">Net</span> {{ item.payment_terms_days || 0 }} days</div>
                        <div v-if="Number(item.credit_limit) > 0" class="text-zinc-400 font-mono">
                            Limit: ${{ formatMoney(item.credit_limit) }}
                        </div>
                    </div>
                </template>

                <!-- Custom Cell: Status -->
                <template #cell(status)="{ item }">
                    <Badge :variant="item.status === 'active' ? 'active' : 'neutral'">
                        {{ item.status === 'active' ? 'Active' : 'Inactive' }}
                    </Badge>
                </template>

                <!-- Custom Cell: Actions -->
                <template #cell(actions)="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <Link
                            :href="`/admin/accounting/customers/${item.public_id || item.id}`"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                            title="View Customer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </Link>

                        <Link
                            v-if="canManage"
                            :href="`/admin/accounting/customers/${item.public_id || item.id}/edit`"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                            title="Edit Customer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>

                        <button
                            v-if="canManage"
                            type="button"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-amber-600 dark:hover:bg-zinc-800 dark:hover:text-amber-400 transition-colors"
                            :title="item.status === 'active' ? 'Deactivate Customer' : 'Activate Customer'"
                            @click="toggleStatus(item)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Grid Card View -->
                <template #grid-card="{ item }">
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-2xs hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-xs text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 ring-1 ring-indigo-500/20">
                                    {{ item.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <Link
                                        :href="`/admin/accounting/customers/${item.public_id || item.id}`"
                                        class="font-semibold text-sm text-zinc-900 hover:text-indigo-600 dark:text-white truncate block"
                                    >
                                        {{ item.name }}
                                    </Link>
                                    <div class="font-mono text-xs text-zinc-400 truncate">{{ item.customer_code }}</div>
                                </div>
                            </div>
                            <Badge :variant="item.status === 'active' ? 'active' : 'neutral'">
                                {{ item.status === 'active' ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>

                        <div class="mt-4 space-y-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                            <div v-if="item.email" class="truncate">{{ item.email }}</div>
                            <div v-if="item.phone">{{ item.phone }}</div>
                            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 flex justify-between">
                                <span>Terms:</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">Net {{ item.payment_terms_days }} days</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex justify-end gap-2">
                            <Link
                                :href="`/admin/accounting/customers/${item.public_id || item.id}`"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                            >
                                View Details &rarr;
                            </Link>
                        </div>
                    </div>
                </template>

                <template #empty-actions>
                    <Link
                        v-if="canManage"
                        href="/admin/accounting/customers/create"
                    >
                        <Button size="sm">Add Customer</Button>
                    </Link>
                </template>
            </DataTable>
        </div>
    </OrganizationLayout>
</template>
