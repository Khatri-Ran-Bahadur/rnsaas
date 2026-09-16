<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import {
    Button,
    Badge,
    DataTable,
    StatsCard,
    Card,
    Select,
    type SelectOption,
    type TableColumn,
} from '@/components';
import type { PaginatedData } from '@/types/tenancy';
import { usePermissions } from '@/composables/usePermissions';

interface ItemListItem {
    id: number;
    name: string;
    sku: string;
    barcode?: string;
    type: 'stock' | 'service' | 'non_stock';
    category?: { id: number; name: string } | null;
    category_name?: string;
    brand?: { id: number; name: string } | null;
    brand_name?: string;
    cost_price: number;
    selling_price: number;
    on_hand_stock?: number;
    current_stock?: number;
    reorder_point?: number;
    reorder_level?: number;
    is_pos_available?: boolean;
    pos_enabled?: boolean;
    status: 'active' | 'draft' | 'archived';
    image_url?: string | null;
    unit_name?: string;
    base_unit?: string;
    tax_rate?: number;
    tax_type?: 'taxable' | 'zero_rated' | 'exempt' | 'out_of_scope';
    tax_category_name?: string;
    allow_discount?: boolean;
    max_discount_percentage?: number;
    promo_price?: number | null;
    wholesale_price?: number | null;
}

interface Props {
    items: PaginatedData<ItemListItem>;
    stats?: {
        total_items: number;
        stock_items_count: number;
        low_stock_count: number;
        total_inventory_value: number | string;
    };
    categories?: Array<{ id: number; name: string }>;
    brands?: Array<{ id: number; name: string }>;
    branches?: Array<{ id: number; name: string; code?: string }>;
    filters?: {
        search?: string;
        type?: string;
        category_id?: string;
        branch_id?: string;
        brand_id?: string;
        stock_status?: string;
        pos_available?: string;
    };
}

const props = defineProps<Props>();

import { useCurrency } from '@/composables/useCurrency';

const { currencyCode } = useCurrency();
const currency = computed(() => currencyCode.value);

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('inventory.manage') || can('inventory.items.manage'));

// Safe Stats
const safeStats = computed(() => {
    const dataList = props.items?.data || [];
    const stockItems = dataList.filter(i => i.type === 'stock');
    const lowStock = stockItems.filter(i => (i.on_hand_stock ?? i.current_stock ?? 0) <= (i.reorder_point ?? i.reorder_level ?? 0) && (i.reorder_point ?? i.reorder_level ?? 0) > 0);
    const totalVal = stockItems.reduce((sum, i) => sum + ((i.on_hand_stock ?? i.current_stock ?? 0) * (i.cost_price || 0)), 0);

    return {
        total_items: props.stats?.total_items ?? dataList.length,
        stock_items_count: props.stats?.stock_items_count ?? stockItems.length,
        low_stock_count: props.stats?.low_stock_count ?? lowStock.length,
        total_inventory_value: props.stats?.total_inventory_value ?? totalVal,
    };
});

// View mode: 'table' or 'grid'
const viewMode = ref<'table' | 'grid'>('table');

// Filter state
const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const selectedCategory = ref(props.filters?.category_id || '');
const selectedBrand = ref(props.filters?.brand_id || '');
const selectedStockStatus = ref(props.filters?.stock_status || '');
const selectedPos = ref(props.filters?.pos_available || '');

const typeOptions: SelectOption[] = [
    { label: 'All Item Types', value: '' },
    { label: 'Stock Item', value: 'stock' },
    { label: 'Service', value: 'service' },
    { label: 'Non-Stock', value: 'non_stock' },
];

const categoryOptions = computed<SelectOption[]>(() => [
    { label: 'All Categories', value: '' },
    ...(props.categories || []).map((c) => ({ label: c.name, value: String(c.id) })),
]);

const stockStatusOptions: SelectOption[] = [
    { label: 'All Stock Status', value: '' },
    { label: 'In Stock', value: 'in_stock' },
    { label: 'Low Stock Alert', value: 'low_stock' },
    { label: 'Out of Stock', value: 'out_of_stock' },
];

const applyFilters = () => {
    router.get(
        '/admin/inventory/items',
        {
            search: search.value || undefined,
            type: selectedType.value || undefined,
            category_id: selectedCategory.value || undefined,
            brand_id: selectedBrand.value || undefined,
            stock_status: selectedStockStatus.value || undefined,
            pos_available: selectedPos.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedType.value = '';
    selectedCategory.value = '';
    selectedBrand.value = '';
    selectedStockStatus.value = '';
    selectedPos.value = '';
    applyFilters();
};

const columns: TableColumn[] = [
    { key: 'item', label: 'Item & SKU', sortable: true },
    { key: 'type', label: 'Type' },
    { key: 'category', label: 'Category / Brand' },
    { key: 'pricing', label: 'Price, Tax & Discounts', align: 'right' },
    { key: 'stock', label: 'Stock Level', align: 'right' },
    { key: 'pos', label: 'POS / Channel', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const getTypeBadge = (type: string) => {
    switch (type) {
        case 'stock':
            return { label: 'Stock Item', variant: 'emerald' };
        case 'service':
            return { label: 'Service', variant: 'blue' };
        case 'non_stock':
            return { label: 'Non-Stock', variant: 'amber' };
        default:
            return { label: type, variant: 'zinc' };
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return { label: 'Active', variant: 'emerald' };
        case 'draft':
            return { label: 'Draft', variant: 'zinc' };
        case 'archived':
            return { label: 'Archived', variant: 'rose' };
        default:
            return { label: status, variant: 'zinc' };
    }
};

const deleteItem = (item: ItemListItem) => {
    if (confirm(`Are you sure you want to delete "${item.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/inventory/items/${item.id}`);
    }
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Products & Inventory Items" />

        <div class="space-y-6">
            <!-- Header with Breadcrumb and Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Items & Products</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Products & Inventory Items
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Universal catalog for retail, restaurant dishes, wholesale stock, service billables, and BOM assemblies with integrated taxation & discounts.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2.5">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="viewMode = viewMode === 'table' ? 'grid' : 'table'"
                    >
                        <span v-if="viewMode === 'table'">📱 Grid View</span>
                        <span v-else>📋 Table View</span>
                    </Button>

                    <Link
                        v-if="canManage"
                        href="/admin/inventory/items/create"
                    >
                        <Button variant="primary" size="sm">
                            + Create Item
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Top Stats KPI Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatsCard
                    title="Total Catalog Items"
                    :value="String(safeStats.total_items)"
                    description="All active & draft records"
                />
                <StatsCard
                    title="Physical Stock Items"
                    :value="String(safeStats.stock_items_count)"
                    description="Inventory tracked units"
                />
                <StatsCard
                    title="Low Stock Alerts"
                    :value="String(safeStats.low_stock_count)"
                    description="Below reorder threshold"
                    :variant="safeStats.low_stock_count > 0 ? 'warning' : 'default'"
                />
                <StatsCard
                    title="Total Stock Valuation"
                    :value="`${currency} ${Number(safeStats.total_inventory_value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"
                    description="On-hand balance at cost"
                />
            </div>

            <!-- Filter Toolbar -->
            <Card class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <!-- Search Input -->
                    <div class="lg:col-span-2">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by name, SKU, or barcode..."
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500 dark:text-white"
                        />
                    </div>

                    <div>
                        <Select
                            v-model="selectedType"
                            :options="typeOptions"
                            placeholder="All Item Types"
                            size="sm"
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <Select
                            v-model="selectedCategory"
                            :options="categoryOptions"
                            placeholder="All Categories"
                            size="sm"
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <Select
                            v-model="selectedStockStatus"
                            :options="stockStatusOptions"
                            placeholder="All Stock Status"
                            size="sm"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Reset / Filter Button -->
                    <div class="flex items-center gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            class="w-full text-xs"
                            @click="applyFilters"
                        >
                            Filter
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-xs text-zinc-500"
                            @click="resetFilters"
                        >
                            Reset
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Table View -->
            <div v-if="viewMode === 'table'">
                <DataTable
                    :columns="columns"
                    :data="items?.data || []"
                    :pagination="items"
                >
                    <!-- Item & SKU Column -->
                    <template #cell-item="{ row }">
                        <div class="flex items-center gap-3 py-1">
                            <div class="w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center overflow-hidden flex-shrink-0">
                                <img
                                    v-if="row.image_url"
                                    :src="row.image_url"
                                    :alt="row.name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-xs font-bold text-zinc-400">
                                    {{ (row.name || 'P').slice(0, 2).toUpperCase() }}
                                </span>
                            </div>
                            <div>
                                <Link
                                    :href="`/admin/inventory/items/${row.id}`"
                                    class="font-semibold text-zinc-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 text-sm line-clamp-1"
                                >
                                    {{ row.name }}
                                </Link>
                                <div class="flex items-center gap-2 text-xs text-zinc-500 font-mono mt-0.5">
                                    <span>SKU: {{ row.sku || '—' }}</span>
                                    <span v-if="row.barcode">• Barcode: {{ row.barcode }}</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Type Column -->
                    <template #cell-type="{ row }">
                        <Badge
                            :variant="getTypeBadge(row.type).variant as any"
                            size="sm"
                        >
                            {{ getTypeBadge(row.type).label }}
                        </Badge>
                    </template>

                    <!-- Category / Brand / Branch Column -->
                    <template #cell-category="{ row }">
                        <div class="text-xs">
                            <div class="font-medium text-zinc-800 dark:text-zinc-200">
                                {{ row.category?.name || row.category_name || 'Uncategorized' }}
                            </div>
                            <div v-if="row.brand?.name || row.brand_name" class="text-[11px] text-zinc-400">
                                Brand: {{ row.brand?.name || row.brand_name }}
                            </div>
                            <div v-if="row.branch?.name" class="text-[10px] text-indigo-500 font-medium mt-0.5">
                                🏢 {{ row.branch.name }}
                            </div>
                        </div>
                    </template>

                    <!-- Price, Tax & Discount Column -->
                    <template #cell-pricing="{ row }">
                        <div class="text-right text-xs space-y-0.5">
                            <div class="font-mono font-bold text-zinc-900 dark:text-white">
                                {{ currency }} {{ Number(row.selling_price || 0).toFixed(2) }}
                            </div>
                            <div class="font-mono text-[10px] text-zinc-400">
                                Cost: {{ currency }} {{ Number(row.cost_price || 0).toFixed(2) }}
                            </div>
                            <!-- Tax & Discount Badges -->
                            <div class="flex items-center justify-end gap-1 pt-0.5">
                                <span
                                    v-if="row.tax_category_name || row.tax_rate !== undefined"
                                    class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-mono font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-900"
                                    :title="`Tax: ${row.tax_type || 'taxable'} (${row.tax_rate || 0}%)`"
                                >
                                    {{ row.tax_category_name || `Tax ${row.tax_rate}%` }}
                                </span>
                                <span
                                    v-if="row.promo_price"
                                    class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900"
                                    :title="`Promo: ${currency} ${row.promo_price}`"
                                >
                                    Promo {{ currency }} {{ Number(row.promo_price).toFixed(2) }}
                                </span>
                                <span
                                    v-else-if="row.wholesale_price"
                                    class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-mono font-medium bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-900"
                                    :title="`Wholesale: ${currency} ${row.wholesale_price}`"
                                >
                                    WS {{ currency }} {{ Number(row.wholesale_price).toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Stock Level Column -->
                    <template #cell-stock="{ row }">
                        <div v-if="row.type === 'stock'" class="text-right text-xs">
                            <div
                                class="font-mono font-bold"
                                :class="[
                                    (row.on_hand_stock ?? row.current_stock ?? 0) <= 0 ? 'text-rose-600 dark:text-rose-400' :
                                    ((row.reorder_point ?? row.reorder_level ?? 0) > 0 && (row.on_hand_stock ?? row.current_stock ?? 0) <= (row.reorder_point ?? row.reorder_level ?? 0)) ? 'text-amber-600 dark:text-amber-400' :
                                    'text-emerald-600 dark:text-emerald-400'
                                ]"
                            >
                                {{ (row.on_hand_stock ?? row.current_stock ?? 0).toLocaleString() }} {{ row.unit_name || row.base_unit || 'Units' }}
                            </div>
                            <div v-if="(row.reorder_point ?? row.reorder_level ?? 0) > 0" class="text-[10px] text-zinc-400 font-mono">
                                ROP: {{ row.reorder_point ?? row.reorder_level }}
                            </div>
                        </div>
                        <div v-else class="text-right text-xs text-zinc-400 italic">
                            Non-tracked
                        </div>
                    </template>

                    <!-- POS Channel Column -->
                    <template #cell-pos="{ row }">
                        <span
                            v-if="row.is_pos_available || row.pos_enabled"
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900"
                        >
                            POS Ready
                        </span>
                        <span v-else class="text-[11px] text-zinc-400">
                            —
                        </span>
                    </template>

                    <!-- Status Column -->
                    <template #cell-status="{ row }">
                        <Badge
                            :variant="getStatusBadge(row.status).variant as any"
                            size="sm"
                        >
                            {{ getStatusBadge(row.status).label }}
                        </Badge>
                    </template>

                    <!-- Actions Column -->
                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-1.5">
                            <Link
                                :href="`/admin/inventory/items/${row.id}`"
                                class="p-1.5 text-zinc-500 hover:text-zinc-900 dark:hover:text-white rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                                title="View Details"
                            >
                                👁️
                            </Link>
                            <Link
                                v-if="canManage"
                                :href="`/admin/inventory/items/${row.id}/edit`"
                                class="p-1.5 text-zinc-500 hover:text-zinc-900 dark:hover:text-white rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                                title="Edit Item"
                            >
                                ✏️
                            </Link>
                            <button
                                v-if="canManage"
                                type="button"
                                @click="deleteItem(row)"
                                class="p-1.5 text-zinc-400 hover:text-rose-600 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                title="Delete Item"
                            >
                                🗑️
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>

            <!-- Grid Card View (POS Tile Mode) -->
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div
                    v-for="item in items?.data || []"
                    :key="item.id"
                    class="p-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:shadow-md transition-all flex flex-col justify-between"
                >
                    <div class="space-y-2">
                        <div class="aspect-square rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center overflow-hidden relative">
                            <img
                                v-if="item.image_url"
                                :src="item.image_url"
                                :alt="item.name"
                                class="w-full h-full object-cover"
                            />
                            <span v-else class="text-2xl font-bold text-zinc-300 dark:text-zinc-600">
                                {{ (item.name || 'P').slice(0, 2).toUpperCase() }}
                            </span>
                            <span
                                v-if="item.is_pos_available || item.pos_enabled"
                                class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500 text-white shadow-sm"
                            >
                                POS
                            </span>
                        </div>

                        <div>
                            <div class="text-xs font-semibold text-zinc-900 dark:text-white line-clamp-2">
                                {{ item.name }}
                            </div>
                            <div class="text-[11px] font-mono text-zinc-400 mt-0.5">
                                {{ item.sku || 'No SKU' }}
                            </div>
                            <!-- Tax & Discount Tags in Card -->
                            <div class="flex flex-wrap gap-1 mt-1">
                                <span v-if="item.tax_rate !== undefined" class="text-[9px] px-1 py-0.2 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-900">
                                    {{ item.tax_category_name || `Tax ${item.tax_rate}%` }}
                                </span>
                                <span v-if="item.promo_price" class="text-[9px] px-1 py-0.2 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900 font-bold">
                                    Promo {{ currency }} {{ item.promo_price }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        <span class="text-xs font-mono font-bold text-zinc-900 dark:text-white">
                            {{ currency }} {{ Number(item.selling_price || 0).toFixed(2) }}
                        </span>
                        <Link
                            :href="`/admin/inventory/items/${item.id}`"
                            class="text-xs text-emerald-600 hover:underline"
                        >
                            View
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
