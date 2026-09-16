<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, Select, type SelectOption, type TableColumn } from '@/components';
import type { PaginatedData } from '@/types/tenancy';

interface StockLedgerEntry {
    id: number;
    transaction_date: string;
    reference: string;
    item_id: number;
    item_name: string;
    item_sku: string;
    location_name: string;
    movement_type: 'purchase_receipt' | 'sales_dispatch' | 'transfer_in' | 'transfer_out' | 'adjustment_in' | 'adjustment_out' | 'production_in' | 'production_out';
    quantity: number;
    unit_cost: number;
    total_cost: number;
    balance_after: number;
    user_name?: string;
    notes?: string;
}

const props = defineProps<{
    movements: PaginatedData<StockLedgerEntry>;
    filters: {
        item_id?: string;
        location_id?: string;
        movement_type?: string;
        search?: string;
    };
    locations: Array<{ id: number; name: string }>;
}>();

import { useCurrency } from '@/composables/useCurrency';

const { currencyCode } = useCurrency();
const currency = computed(() => currencyCode.value);

const search = ref(props.filters.search || '');
const selectedLocation = ref(props.filters.location_id || '');
const selectedType = ref(props.filters.movement_type || '');

const locationOptions = computed<SelectOption[]>(() => [
    { label: 'All Locations', value: '' },
    ...props.locations.map((l) => ({ label: l.name, value: String(l.id) })),
]);

const movementTypeOptions: SelectOption[] = [
    { label: 'All Movement Types', value: '' },
    { label: 'Purchase Receipt', value: 'purchase_receipt' },
    { label: 'Sales Dispatch', value: 'sales_dispatch' },
    { label: 'Transfer In', value: 'transfer_in' },
    { label: 'Transfer Out', value: 'transfer_out' },
    { label: 'Adjustment In', value: 'adjustment_in' },
    { label: 'Adjustment Out', value: 'adjustment_out' },
    { label: 'Production Finished Goods', value: 'production_in' },
    { label: 'Production Ingredients', value: 'production_out' },
];

const applyFilters = () => {
    router.get(
        '/admin/inventory/ledger',
        {
            search: search.value || undefined,
            location_id: selectedLocation.value || undefined,
            movement_type: selectedType.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const getMovementBadge = (type: string) => {
    if (type.includes('_in') || type === 'purchase_receipt') {
        return { label: type.replace('_', ' '), bgClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900' };
    }
    return { label: type.replace('_', ' '), bgClass: 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-900' };
};

const columns: TableColumn[] = [
    { key: 'date', label: 'Date / Time', sortable: true },
    { key: 'reference', label: 'Reference' },
    { key: 'item', label: 'Item & SKU' },
    { key: 'location', label: 'Location' },
    { key: 'type', label: 'Transaction Type' },
    { key: 'qty', label: 'Qty Change', align: 'right' },
    { key: 'balance', label: 'Balance After', align: 'right' },
];
</script>

<template>
    <OrganizationLayout>
        <Head title="Stock Movements Ledger" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Stock Ledger</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Inventory Audit & Stock Ledger
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Chronological record of every inward, outward, transfer, and adjustment stock transaction.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link href="/admin/inventory/transfers/create">
                        <Button variant="outline" size="sm">
                            🔄 Transfer Stock
                        </Button>
                    </Link>
                    <Link href="/admin/inventory/adjustments/create">
                        <Button variant="primary" size="sm">
                            📝 Adjust Stock
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <Card class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by item name, SKU, or transaction reference..."
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                        />
                    </div>

                    <div>
                        <Select
                            v-model="selectedLocation"
                            :options="locationOptions"
                            placeholder="All Locations"
                            size="sm"
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <Select
                            v-model="selectedType"
                            :options="movementTypeOptions"
                            placeholder="All Movement Types"
                            size="sm"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </Card>

            <!-- Movements DataTable -->
            <Card class="p-0 overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="movements.data"
                    :pagination="movements"
                >
                    <template #cell-date="{ row }">
                        <div class="py-2 text-xs text-zinc-500 font-mono">
                            {{ row.transaction_date }}
                        </div>
                    </template>

                    <template #cell-reference="{ row }">
                        <span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ row.reference }}
                        </span>
                    </template>

                    <template #cell-item="{ row }">
                        <div>
                            <Link
                                :href="`/admin/inventory/items/${row.item_id}`"
                                class="text-xs font-semibold text-zinc-900 dark:text-white hover:text-indigo-600 line-clamp-1"
                            >
                                {{ row.item_name }}
                            </Link>
                            <span class="font-mono text-[11px] text-zinc-400">SKU: {{ row.item_sku }}</span>
                        </div>
                    </template>

                    <template #cell-location="{ row }">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">
                            {{ row.location_name }}
                        </span>
                    </template>

                    <template #cell-type="{ row }">
                        <span
                            :class="[
                                'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase border',
                                getMovementBadge(row.movement_type).bgClass
                            ]"
                        >
                            {{ getMovementBadge(row.movement_type).label }}
                        </span>
                    </template>

                    <template #cell-qty="{ row }">
                        <span
                            :class="[
                                'font-mono text-xs font-bold',
                                row.quantity >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                            ]"
                        >
                            {{ row.quantity >= 0 ? '+' : '' }}{{ row.quantity }}
                        </span>
                    </template>

                    <template #cell-balance="{ row }">
                        <span class="font-mono text-xs font-semibold text-zinc-900 dark:text-white">
                            {{ row.balance_after?.toLocaleString() || 0 }}
                        </span>
                    </template>
                </DataTable>
            </Card>
        </div>
    </OrganizationLayout>
</template>
