<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, StatsCard } from '@/components';
import LocationStockTable from '../../Components/LocationStockTable.vue';
import { usePermissions } from '@/composables/usePermissions';

interface ItemDetail {
    id: number;
    name: string;
    sku: string;
    item_code?: string;
    type: 'stock' | 'service' | 'non_stock';
    status: 'active' | 'draft' | 'archived';
    description?: string;
    internal_notes?: string;
    image_url?: string | null;

    category?: { id: number; name: string } | null;
    subcategory?: { id: number; name: string } | null;
    brand?: { id: number; name: string } | null;
    manufacturer?: string;

    barcode_symbology?: string;
    barcode?: string;
    secondary_barcodes?: string[];
    mpn?: string;
    supplier_item_code?: string;

    base_unit?: { id: number; name: string; code: string } | null;
    purchase_unit?: { id: number; name: string; code: string } | null;
    sales_unit?: { id: number; name: string; code: string } | null;

    cost_price: number;
    selling_price: number;
    min_selling_price?: number;
    wholesale_price?: number;
    retail_price?: number;
    pos_price?: number;
    ecommerce_price?: number;
    price_tiers?: Array<{ id: number | string; tier_name: string; min_quantity: number; price: number }>;

    tax_type: 'taxable' | 'zero_rated' | 'exempt' | 'out_of_scope';
    tax_rate?: { id: number; name: string; rate: number } | null;
    tax_inclusive: boolean;
    hsn_sac_code?: string;
    allow_discount: boolean;
    max_discount_percentage?: number;

    track_inventory: boolean;
    tracking_type: 'standard' | 'batch' | 'serial';
    valuation_method: string;
    min_stock: number;
    reorder_point: number;
    reorder_quantity: number;
    max_stock: number;
    lead_time_days: number;
    allow_negative_stock: boolean;
    has_expiry: boolean;

    has_variants: boolean;
    variants?: Array<{
        id: number | string;
        name: string;
        sku: string;
        barcode?: string;
        cost_price: number;
        selling_price: number;
        is_active: boolean;
    }>;

    is_pos_available: boolean;
    is_pos_favorite: boolean;
    pos_display_name?: string;
    pos_color?: string;
    pos_kitchen_station?: string;
    modifier_groups?: Array<{
        id: number | string;
        name: string;
        is_required: boolean;
        options: Array<{ id: number | string; name: string; extra_price: number }>;
    }>;

    has_recipe_bom: boolean;
    yield_quantity?: number;
    yield_unit?: string;
    bom_items?: Array<{
        id: number | string;
        ingredient_name: string;
        quantity: number;
        unit_name: string;
        unit_cost: number;
        wastage_percentage?: number;
    }>;
}

interface StockMovement {
    id: number;
    reference: string;
    movement_type: string;
    location_name: string;
    quantity: number;
    unit_cost: number;
    created_at: string;
    user_name?: string;
}

const props = defineProps<{
    item: ItemDetail;
    stockByLocation: any[];
    recentMovements?: StockMovement[];
}>();

import { useCurrency } from '@/composables/useCurrency';

const { currencyCode } = useCurrency();
const currency = computed(() => currencyCode.value);

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('inventory.manage') || can('inventory.items.manage'));

const activeTab = ref<'locations' | 'pricing' | 'variants' | 'pos' | 'recipe' | 'ledger'>('locations');

const profitMargin = computed(() => {
    const cost = Number(props.item.cost_price || 0);
    const sell = Number(props.item.selling_price || 0);
    if (sell <= 0) return 0;
    return ((sell - cost) / sell) * 100;
});

const totalOnHand = computed(() => {
    return (props.stockByLocation || []).reduce((sum, l) => sum + (l.on_hand || 0), 0);
});

const deleteItem = () => {
    if (confirm(`Are you sure you want to delete ${props.item.name}?`)) {
        router.delete(`/admin/inventory/items/${props.item.id}`);
    }
};
</script>

<template>
    <OrganizationLayout>
        <Head :title="item.name" />

        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- Header with Breadcrumbs & Action Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory Items</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">{{ item.sku }}</span>
                    </nav>

                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ item.name }}
                        </h1>
                        <span
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase',
                                item.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900' :
                                item.status === 'draft' ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' :
                                'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                            ]"
                        >
                            {{ item.status }}
                        </span>
                        <span
                            v-if="item.is_pos_available"
                            class="px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900"
                        >
                            POS Active
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-500 font-mono mt-2">
                        <span>SKU: <strong class="text-zinc-800 dark:text-zinc-200">{{ item.sku }}</strong></span>
                        <span v-if="item.barcode">• Barcode: <strong class="text-zinc-800 dark:text-zinc-200">{{ item.barcode }}</strong></span>
                        <span v-if="item.category">• Category: <strong class="text-zinc-800 dark:text-zinc-200">{{ item.category.name }}</strong></span>
                        <span v-if="item.brand">• Brand: <strong class="text-zinc-800 dark:text-zinc-200">{{ item.brand.name }}</strong></span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        v-if="canManage"
                        :href="`/admin/inventory/items/${item.id}/edit`"
                    >
                        <Button variant="primary" size="sm">
                            ✏️ Edit Product
                        </Button>
                    </Link>
                    <Button
                        v-if="canManage"
                        variant="ghost"
                        size="sm"
                        class="text-rose-600 hover:text-rose-700 dark:text-rose-400"
                        @click="deleteItem"
                    >
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Top KPI Cards Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <StatsCard
                    title="Standard Selling Price"
                    :value="`${currency} ${Number(item.selling_price || 0).toFixed(2)}`"
                    :description="`Cost: ${currency} ${Number(item.cost_price || 0).toFixed(2)}`"
                />
                <StatsCard
                    title="Gross Margin"
                    :value="`${profitMargin.toFixed(1)}%`"
                    :description="`Profit: ${currency} ${(Number(item.selling_price || 0) - Number(item.cost_price || 0)).toFixed(2)} / unit`"
                    :variant="profitMargin >= 0 ? 'default' : 'danger'"
                />
                <StatsCard
                    title="Total On-Hand Stock"
                    :value="`${totalOnHand.toLocaleString()} ${item.base_unit?.name || 'Units'}`"
                    :description="item.reorder_point ? `Reorder Point: ${item.reorder_point}` : 'Across all locations'"
                    :variant="(item.reorder_point > 0 && totalOnHand <= item.reorder_point) ? 'warning' : 'default'"
                />
                <StatsCard
                    title="Total Valuation"
                    :value="`${currency} ${(totalOnHand * Number(item.cost_price || 0)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"
                    description="At standard cost rate"
                />
            </div>

            <!-- Tab Navigation Bar -->
            <div class="border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-2 overflow-x-auto">
                <button
                    type="button"
                    @click="activeTab = 'locations'"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'locations'
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    🏢 Multi-Location Stock
                </button>

                <button
                    type="button"
                    @click="activeTab = 'pricing'"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'pricing'
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    💰 Pricing, Taxes & Multi-Channel
                </button>

                <button
                    v-if="item.has_variants"
                    type="button"
                    @click="activeTab = 'variants'"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'variants'
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    🎨 Variations ({{ (item.variants || []).length }})
                </button>

                <button
                    v-if="item.is_pos_available"
                    type="button"
                    @click="activeTab = 'pos'"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'pos'
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    📱 POS & Touch Configuration
                </button>

                <button
                    v-if="item.has_recipe_bom"
                    type="button"
                    @click="activeTab = 'recipe'"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'recipe'
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    🍳 Recipe & Ingredients
                </button>

                <button
                    type="button"
                    @click="activeTab = 'ledger'"
                    :class="[
                        'px-4 py-2.5 text-xs font-semibold border-b-2 transition-all whitespace-nowrap',
                        activeTab === 'ledger'
                            ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                    ]"
                >
                    📜 Stock Ledger & Audit Log
                </button>
            </div>

            <!-- Tab 1: Multi-Location Stock -->
            <div v-if="activeTab === 'locations'">
                <LocationStockTable
                    :stock-by-location="stockByLocation"
                    :unit-name="item.base_unit?.name"
                    :cost-price="Number(item.cost_price)"
                    :item-id="item.id"
                />
            </div>

            <!-- Tab 2: Pricing & Taxes -->
            <div v-if="activeTab === 'pricing'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card class="p-4 space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase">Retail Price</span>
                        <div class="text-lg font-bold font-mono text-zinc-900 dark:text-white">
                            {{ currency }} {{ Number(item.retail_price || item.selling_price).toFixed(2) }}
                        </div>
                    </Card>

                    <Card class="p-4 space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase">Wholesale Price</span>
                        <div class="text-lg font-bold font-mono text-zinc-900 dark:text-white">
                            {{ currency }} {{ Number(item.wholesale_price || item.selling_price).toFixed(2) }}
                        </div>
                    </Card>

                    <Card class="p-4 space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase">POS Touch Price</span>
                        <div class="text-lg font-bold font-mono text-zinc-900 dark:text-white">
                            {{ currency }} {{ Number(item.pos_price || item.selling_price).toFixed(2) }}
                        </div>
                    </Card>

                    <Card class="p-4 space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase">eCommerce Price</span>
                        <div class="text-lg font-bold font-mono text-zinc-900 dark:text-white">
                            {{ currency }} {{ Number(item.ecommerce_price || item.selling_price).toFixed(2) }}
                        </div>
                    </Card>
                </div>

                <!-- Price Tiers Table -->
                <Card v-if="item.price_tiers && item.price_tiers.length > 0" class="p-6">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        Quantity Discount Breaks
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                                    <th class="pb-2">Tier Name</th>
                                    <th class="pb-2">Min Qty</th>
                                    <th class="pb-2 font-mono">Tier Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="tier in item.price_tiers" :key="tier.id">
                                    <td class="py-2.5 font-medium">{{ tier.tier_name }}</td>
                                    <td class="py-2.5 font-mono">{{ tier.min_quantity }}+</td>
                                    <td class="py-2.5 font-mono font-bold">{{ currency }} {{ Number(tier.price).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- Tab 3: Variants -->
            <div v-if="activeTab === 'variants' && item.has_variants">
                <Card class="p-6">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        Product Variations Matrix
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                                    <th class="pb-2">Variation</th>
                                    <th class="pb-2 font-mono">SKU</th>
                                    <th class="pb-2 font-mono">Barcode</th>
                                    <th class="pb-2 font-mono">Selling Price</th>
                                    <th class="pb-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="v in item.variants" :key="v.id">
                                    <td class="py-2.5 font-medium text-zinc-900 dark:text-white">{{ v.name }}</td>
                                    <td class="py-2.5 font-mono">{{ v.sku }}</td>
                                    <td class="py-2.5 font-mono text-zinc-400">{{ v.barcode || '—' }}</td>
                                    <td class="py-2.5 font-mono font-bold">{{ currency }} {{ Number(v.selling_price).toFixed(2) }}</td>
                                    <td class="py-2.5 text-center">
                                        <span :class="v.is_active ? 'text-emerald-600 font-semibold' : 'text-zinc-400'">
                                            {{ v.is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- Tab 4: POS & Kitchen -->
            <div v-if="activeTab === 'pos' && item.is_pos_available" class="space-y-6">
                <Card class="p-6">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        POS Touch Tile Preview
                    </h4>
                    <div class="flex items-center gap-6">
                        <!-- POS Touch Tile Demo -->
                        <div class="w-36 h-28 rounded-2xl bg-zinc-800 text-white p-3 flex flex-col justify-between shadow-lg">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="px-1.5 py-0.5 rounded bg-zinc-700 font-mono">{{ item.sku.slice(0, 6) }}</span>
                                <span v-if="item.is_pos_favorite">⭐</span>
                            </div>
                            <div>
                                <div class="text-xs font-bold leading-tight line-clamp-2">
                                    {{ item.pos_display_name || item.name }}
                                </div>
                                <div class="text-xs font-mono font-bold text-emerald-400 mt-1">
                                    {{ currency }} {{ Number(item.pos_price || item.selling_price).toFixed(2) }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <div><strong>Kitchen Routing:</strong> {{ item.pos_kitchen_station || 'None (Receipt Only)' }}</div>
                            <div><strong>Prompt for Quantity:</strong> {{ item.prompt_for_quantity ? 'Yes' : 'No' }}</div>
                            <div><strong>Weighable Scale:</strong> {{ item.is_weighable ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Tab 5: Recipe & BOM -->
            <div v-if="activeTab === 'recipe' && item.has_recipe_bom">
                <Card class="p-6">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        Ingredients & Raw Materials
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                                    <th class="pb-2">Ingredient</th>
                                    <th class="pb-2 font-mono">Qty</th>
                                    <th class="pb-2">Unit</th>
                                    <th class="pb-2 font-mono">Unit Cost</th>
                                    <th class="pb-2 font-mono">Waste %</th>
                                    <th class="pb-2 font-mono text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="ing in item.bom_items" :key="ing.id">
                                    <td class="py-2.5 font-medium text-zinc-900 dark:text-white">{{ ing.ingredient_name }}</td>
                                    <td class="py-2.5 font-mono">{{ ing.quantity }}</td>
                                    <td class="py-2.5">{{ ing.unit_name }}</td>
                                    <td class="py-2.5 font-mono">{{ currency }} {{ Number(ing.unit_cost).toFixed(2) }}</td>
                                    <td class="py-2.5 font-mono">{{ ing.wastage_percentage || 0 }}%</td>
                                    <td class="py-2.5 font-mono font-bold text-right">
                                        {{ currency }} {{ (ing.quantity * ing.unit_cost * (1 + (ing.wastage_percentage || 0) / 100)).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- Tab 6: Stock Ledger -->
            <div v-if="activeTab === 'ledger'">
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                            Stock Movements & Audit History
                        </h4>
                        <Link
                            :href="`/admin/inventory/ledger?item_id=${item.id}`"
                            class="text-xs text-indigo-600 hover:underline"
                        >
                            View Full Ledger →
                        </Link>
                    </div>

                    <div v-if="recentMovements && recentMovements.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                                    <th class="pb-2">Date / Time</th>
                                    <th class="pb-2">Reference</th>
                                    <th class="pb-2">Type</th>
                                    <th class="pb-2">Location</th>
                                    <th class="pb-2 font-mono text-right">Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="m in recentMovements" :key="m.id">
                                    <td class="py-2.5 text-zinc-500">{{ m.created_at }}</td>
                                    <td class="py-2.5 font-mono font-semibold">{{ m.reference }}</td>
                                    <td class="py-2.5 capitalize">{{ m.movement_type }}</td>
                                    <td class="py-2.5">{{ m.location_name }}</td>
                                    <td
                                        class="py-2.5 font-mono font-bold text-right"
                                        :class="m.quantity >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                    >
                                        {{ m.quantity >= 0 ? '+' : '' }}{{ m.quantity }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center py-8 text-zinc-400 text-xs">
                        No recent stock movements recorded.
                    </div>
                </Card>
            </div>
        </div>
    </OrganizationLayout>
</template>
