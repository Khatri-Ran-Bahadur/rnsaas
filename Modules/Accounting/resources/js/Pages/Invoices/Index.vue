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

interface InvoiceItem {
    id: number;
    public_id: string;
    invoice_number: string;
    invoice_date: string;
    due_date: string;
    currency: string;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    grand_total: string;
    status: string;
    reference: string | null;
    customer?: CustomerBrief | null;
}

interface Props {
    invoices: PaginatedData<InvoiceItem>;
    stats: {
        total_invoices: number;
        draft_count: number;
        issued_count: number;
        total_amount: string;
    };
    filters: {
        search?: string;
        status?: string;
        customer_id?: string;
    };
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('accounting.manage') || can('accounting.invoices.manage'));

const columns: TableColumn[] = [
    { key: 'invoice', label: 'Invoice #', sortable: true },
    { key: 'customer', label: 'Customer' },
    { key: 'dates', label: 'Invoice / Due Date' },
    { key: 'amount', label: 'Total Amount', align: 'right' },
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
        '/admin/accounting/invoices',
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
        '/admin/accounting/invoices',
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
        '/admin/accounting/invoices',
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
        '/admin/accounting/invoices',
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

const formatDate = (dateStr: string) => {
    if (!dateStr) return '—';
    return dateStr.split('T')[0];
};

const statusVariant = (status: string) => {
    switch (status) {
        case 'posted':
            return 'active';
        case 'issued':
            return 'pending';
        case 'void':
            return 'cancelled';
        case 'draft':
        default:
            return 'neutral';
    }
};
</script>

<template>
    <Head title="Sales Invoices - Accounting" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Sales Invoices
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Create client bills, track receivables, issue credit, and post to General Ledger.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="canManage"
                        href="/admin/accounting/invoices/create"
                    >
                        <Button>
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Sales Invoice
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total Invoices"
                    :value="stats.total_invoices"
                    badge-color="blue"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Drafts Pending"
                    :value="stats.draft_count"
                    badge-color="zinc"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Awaiting Settlement"
                    :value="stats.issued_count"
                    badge-color="amber"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>

                <StatsCard
                    title="Invoiced Total"
                    :value="`$${formatMoney(stats.total_amount)}`"
                    badge-color="emerald"
                >
                    <template #icon>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </StatsCard>
            </div>

            <!-- Unified DataTable with Pagination -->
            <DataTable
                :columns="columns"
                :data="invoices"
                :search-value="filters.search || ''"
                search-placeholder="Search by invoice number or customer..."
                filterable
                empty-title="No sales invoices found"
                empty-description="Create an invoice to bill a customer and track accounts receivable."
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

                <!-- Custom Cell: Invoice Number -->
                <template #cell(invoice)="{ item }">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 font-mono text-xs font-bold">
                            INV
                        </div>
                        <div>
                            <Link
                                :href="`/admin/accounting/invoices/${item.id}`"
                                class="font-semibold text-zinc-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400 transition-colors"
                            >
                                {{ item.invoice_number }}
                            </Link>
                            <div v-if="item.reference" class="text-xs text-zinc-400">
                                Ref: {{ item.reference }}
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Custom Cell: Customer -->
                <template #cell(customer)="{ item }">
                    <div v-if="item.customer">
                        <Link
                            :href="`/admin/accounting/customers/${item.customer.id}`"
                            class="font-medium text-xs text-zinc-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                        >
                            {{ item.customer.name }}
                        </Link>
                        <div class="font-mono text-[11px] text-zinc-400">
                            {{ item.customer.customer_code }}
                        </div>
                    </div>
                    <span v-else class="text-xs text-zinc-400">—</span>
                </template>

                <!-- Custom Cell: Dates -->
                <template #cell(dates)="{ item }">
                    <div class="text-xs space-y-0.5">
                        <div class="text-zinc-800 dark:text-zinc-200">
                            Date: {{ formatDate(item.invoice_date) }}
                        </div>
                        <div class="text-zinc-400 dark:text-zinc-500">
                            Due: {{ formatDate(item.due_date) }}
                        </div>
                    </div>
                </template>

                <!-- Custom Cell: Total Amount -->
                <template #cell(amount)="{ item }">
                    <div class="text-right">
                        <div class="font-bold text-sm text-zinc-900 dark:text-white">
                            {{ item.currency }} {{ formatMoney(item.grand_total) }}
                        </div>
                        <div v-if="Number(item.tax_total) > 0" class="text-[11px] text-zinc-400">
                            Tax: {{ item.currency }} {{ formatMoney(item.tax_total) }}
                        </div>
                    </div>
                </template>

                <!-- Custom Cell: Status -->
                <template #cell(status)="{ item }">
                    <Badge :variant="statusVariant(item.status)">
                        {{ item.status.toUpperCase() }}
                    </Badge>
                </template>

                <!-- Custom Cell: Actions -->
                <template #cell(actions)="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <Link
                            :href="`/admin/accounting/invoices/${item.id}`"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                            title="View Invoice"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </Link>

                        <Link
                            v-if="canManage && item.status === 'draft'"
                            :href="`/admin/accounting/invoices/${item.id}/edit`"
                            class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                            title="Edit Invoice"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>

                        <Link
                            v-if="canManage && item.status === 'issued'"
                            :href="`/admin/accounting/payments/create?customer_id=${item.customer?.id}`"
                            class="rounded-lg p-1.5 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/50 transition-colors"
                            title="Record Receipt"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </Link>
                    </div>
                </template>

                <template #empty-actions>
                    <Link
                        v-if="canManage"
                        href="/admin/accounting/invoices/create"
                    >
                        <Button size="sm">Create Sales Invoice</Button>
                    </Link>
                </template>
            </DataTable>
        </div>
    </OrganizationLayout>
</template>
