<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge } from '@/components';
import ItemBasicInfoForm from '../../Components/ItemBasicInfoForm.vue';
import ItemIdentificationForm from '../../Components/ItemIdentificationForm.vue';
import ItemUnitsForm from '../../Components/ItemUnitsForm.vue';
import ItemPricingForm from '../../Components/ItemPricingForm.vue';
import ItemTaxDiscountForm from '../../Components/ItemTaxDiscountForm.vue';
import ItemInventoryTrackingForm from '../../Components/ItemInventoryTrackingForm.vue';
import ItemVariantsForm from '../../Components/ItemVariantsForm.vue';
import ItemPosConfigForm from '../../Components/ItemPosConfigForm.vue';
import ItemRecipeBomForm from '../../Components/ItemRecipeBomForm.vue';

interface Category {
    id: number;
    name: string;
    parent_id?: number | null;
}

interface Brand {
    id: number | string;
    name: string;
}

interface Branch {
    id: number | string;
    name: string;
    code?: string;
}

interface Unit {
    id: number | string;
    name: string;
    code: string;
}

interface TaxRate {
    id: number | string;
    name: string;
    rate: number;
    code?: string;
}

interface ItemData {
    id: number;
    name: string;
    sku: string;
    item_code: string;
    type: 'stock' | 'service' | 'non_stock';
    category_id: number | '';
    subcategory_id: number | '';
    branch_id?: number | '';
    brand_id?: number | string | '';
    brand_name?: string;
    manufacturer: string;
    status: 'active' | 'draft' | 'archived';
    description: string;
    internal_notes: string;
    image_url: string | null;

    barcode_symbology?: string;
    barcode?: string;
    secondary_barcodes?: string[];
    mpn?: string;
    supplier_item_code?: string;

    base_unit_id?: number | string | '';
    purchase_unit_id?: number | string | '';
    sales_unit_id?: number | string | '';
    unit_conversions?: any[];

    cost_price: number | string;
    selling_price: number | string;
    min_selling_price: number | string;
    wholesale_price: number | string;
    retail_price: number | string;
    pos_price: number | string;
    ecommerce_price: number | string;
    price_tiers?: any[];

    tax_type: 'taxable' | 'zero_rated' | 'exempt' | 'out_of_scope';
    tax_rate_id: number | string | '';
    tax_inclusive: boolean;
    hsn_sac_code: string;
    allow_discount: boolean;
    max_discount_percentage: number | string;

    track_inventory: boolean;
    tracking_type: 'standard' | 'batch' | 'serial';
    valuation_method: 'fifo' | 'average' | 'standard' | 'specific';
    min_stock: number | string;
    reorder_point: number | string;
    reorder_quantity: number | string;
    max_stock: number | string;
    lead_time_days: number | string;
    allow_negative_stock: boolean;
    has_expiry: boolean;

    has_variants: boolean;
    variant_attributes?: any[];
    variants?: any[];

    is_pos_available: boolean;
    is_pos_favorite: boolean;
    pos_display_name: string;
    pos_color: string;
    pos_kitchen_station: string;
    prompt_for_quantity: boolean;
    is_weighable: boolean;
    modifier_groups?: any[];

    has_recipe_bom: boolean;
    yield_quantity: number | string;
    yield_unit: string;
    scrap_percentage: number | string;
    preparation_notes: string;
    bom_items?: any[];
}

const props = defineProps<{
    item: ItemData;
    categories: Category[];
    brands?: Brand[];
    brandSuggestions?: string[];
    branches?: Branch[];
    units?: Unit[];
    taxRates?: TaxRate[];
}>();

const currentTab = ref<
    'basic' | 'identification' | 'units' | 'pricing' | 'tax' | 'inventory' | 'variants' | 'pos' | 'recipe'
>('basic');

const tabs = [
    { id: 'basic', label: '1. Basic Info', icon: '📝', desc: 'Type, SKU, Category, Brand' },
    { id: 'identification', label: '2. Barcodes & Codes', icon: '🏷️', desc: 'Barcodes, EAN-13, MPN' },
    { id: 'units', label: '3. Units & Multi-UOM', icon: '⚖️', desc: 'Base, Purchase, Sales UOM' },
    { id: 'pricing', label: '4. Pricing & Tiers', icon: '💰', desc: 'Cost, Selling, Wholesale, POS' },
    { id: 'tax', label: '5. Tax & Discounts', icon: '🧾', desc: 'Tax rates, Inclusive, HSN Code' },
    { id: 'inventory', label: '6. Inventory & Tracking', icon: '📦', desc: 'ROP, Valuation, Batch/Serial' },
    { id: 'variants', label: '7. Variants & Matrix', icon: '🎨', desc: 'Size, Color, Option SKUs' },
    { id: 'pos', label: '8. POS & Kitchen', icon: '📱', desc: 'Touch tile, Modifiers, KDS' },
    { id: 'recipe', label: '9. Recipe / BOM', icon: '🍳', desc: 'Bill of Materials & Yield' },
];

const form = useForm({
    name: props.item.name || '',
    sku: props.item.sku || '',
    item_code: props.item.item_code || '',
    type: props.item.type || 'stock',
    category_id: props.item.category_id || '',
    subcategory_id: props.item.subcategory_id || '',
    branch_id: props.item.branch_id || '',
    brand_id: props.item.brand_id || '',
    brand_name: props.item.brand_name || '',
    manufacturer: props.item.manufacturer || '',
    status: props.item.status || 'active',
    description: props.item.description || '',
    internal_notes: props.item.internal_notes || '',
    image_url: props.item.image_url || null,

    barcode_symbology: props.item.barcode_symbology || 'CODE128',
    barcode: props.item.barcode || '',
    secondary_barcodes: props.item.secondary_barcodes || [],
    mpn: props.item.mpn || '',
    supplier_item_code: props.item.supplier_item_code || '',

    base_unit_id: props.item.base_unit_id || '',
    purchase_unit_id: props.item.purchase_unit_id || '',
    sales_unit_id: props.item.sales_unit_id || '',
    unit_conversions: props.item.unit_conversions || [],

    cost_price: props.item.cost_price || '',
    selling_price: props.item.selling_price || '',
    min_selling_price: props.item.min_selling_price || '',
    wholesale_price: props.item.wholesale_price || '',
    retail_price: props.item.retail_price || '',
    pos_price: props.item.pos_price || '',
    ecommerce_price: props.item.ecommerce_price || '',
    price_tiers: props.item.price_tiers || [],

    tax_type: props.item.tax_type || 'taxable',
    tax_rate_id: props.item.tax_rate_id || '',
    tax_inclusive: Boolean(props.item.tax_inclusive),
    hsn_sac_code: props.item.hsn_sac_code || '',
    allow_discount: props.item.allow_discount !== false,
    max_discount_percentage: props.item.max_discount_percentage || 20,

    track_inventory: props.item.track_inventory !== false,
    tracking_type: props.item.tracking_type || 'standard',
    valuation_method: props.item.valuation_method || 'fifo',
    min_stock: props.item.min_stock || 0,
    reorder_point: props.item.reorder_point || 0,
    reorder_quantity: props.item.reorder_quantity || 0,
    max_stock: props.item.max_stock || 0,
    lead_time_days: props.item.lead_time_days || 0,
    allow_negative_stock: Boolean(props.item.allow_negative_stock),
    has_expiry: Boolean(props.item.has_expiry),

    has_variants: Boolean(props.item.has_variants),
    variant_attributes: props.item.variant_attributes || [],
    variants: props.item.variants || [],

    is_pos_available: props.item.is_pos_available !== false,
    is_pos_favorite: Boolean(props.item.is_pos_favorite),
    pos_display_name: props.item.pos_display_name || '',
    pos_color: props.item.pos_color || 'zinc',
    pos_kitchen_station: props.item.pos_kitchen_station || '',
    prompt_for_quantity: Boolean(props.item.prompt_for_quantity),
    is_weighable: Boolean(props.item.is_weighable),
    modifier_groups: props.item.modifier_groups || [],

    has_recipe_bom: Boolean(props.item.has_recipe_bom),
    yield_quantity: props.item.yield_quantity || 1,
    yield_unit: props.item.yield_unit || 'Portion',
    scrap_percentage: props.item.scrap_percentage || 0,
    preparation_notes: props.item.preparation_notes || '',
    bom_items: props.item.bom_items || [],
});

const submit = () => {
    form.put(`/admin/inventory/items/${props.item.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout>
        <Head :title="`Edit ${item.name}`" />

        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- Header with Breadcrumbs & Action Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory Items</Link>
                        <span>/</span>
                        <Link :href="`/admin/inventory/items/${item.id}`" class="hover:text-zinc-700 dark:hover:text-zinc-300">{{ item.name }}</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Edit</span>
                    </nav>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            Edit Item: {{ item.name }}
                        </h1>
                        <span class="font-mono text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                            {{ item.sku }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link :href="`/admin/inventory/items/${item.id}`">
                        <Button variant="ghost" size="sm">Cancel</Button>
                    </Link>
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{ form.processing ? 'Saving...' : 'Update Changes' }}
                    </Button>
                </div>
            </div>

            <!-- Main Layout: Navigation Sidebar + Form Panes -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Navigation Tabs Sidebar -->
                <div class="lg:col-span-1 space-y-1">
                    <button
                        v-for="t in tabs"
                        :key="t.id"
                        type="button"
                        @click="currentTab = t.id as any"
                        :class="[
                            'w-full text-left p-3 rounded-xl transition-all flex items-start gap-3 border',
                            currentTab === t.id
                                ? 'bg-primary-600 text-white dark:bg-primary-600 dark:text-white border-primary-600 shadow-sm'
                                : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60'
                        ]"
                    >
                        <span class="text-lg leading-none mt-0.5">{{ t.icon }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold leading-tight truncate">
                                {{ t.label }}
                            </div>
                            <div
                                class="text-[10px] mt-0.5 leading-tight truncate"
                                :class="currentTab === t.id ? 'opacity-80' : 'text-zinc-400'"
                            >
                                {{ t.desc }}
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Form Panes Content (3 cols) -->
                <div class="lg:col-span-3">
                    <form @submit.prevent="submit">
                        <!-- Tab 1: Basic Info -->
                        <div v-show="currentTab === 'basic'">
                            <ItemBasicInfoForm
                                :form="form"
                                :categories="categories"
                                :brands="brands"
                                :branches="branches"
                                :brand-suggestions="brandSuggestions"
                            />
                        </div>

                        <!-- Tab 2: Barcodes & Identification -->
                        <div v-show="currentTab === 'identification'">
                            <ItemIdentificationForm :form="form" />
                        </div>

                        <!-- Tab 3: Units & UOM -->
                        <div v-show="currentTab === 'units'">
                            <ItemUnitsForm :form="form" :units="units" />
                        </div>

                        <!-- Tab 4: Pricing & Multi-Channel -->
                        <div v-show="currentTab === 'pricing'">
                            <ItemPricingForm :form="form" />
                        </div>

                        <!-- Tab 5: Tax & Discounts -->
                        <div v-show="currentTab === 'tax'">
                            <ItemTaxDiscountForm :form="form" :tax-rates="taxRates" />
                        </div>

                        <!-- Tab 6: Inventory Tracking -->
                        <div v-show="currentTab === 'inventory'">
                            <ItemInventoryTrackingForm
                                :form="form"
                                :item-type="form.type"
                            />
                        </div>

                        <!-- Tab 7: Variants -->
                        <div v-show="currentTab === 'variants'">
                            <ItemVariantsForm :form="form" />
                        </div>

                        <!-- Tab 8: POS & Kitchen -->
                        <div v-show="currentTab === 'pos'">
                            <ItemPosConfigForm :form="form" />
                        </div>

                        <!-- Tab 9: Recipe & BOM -->
                        <div v-show="currentTab === 'recipe'">
                            <ItemRecipeBomForm :form="form" />
                        </div>

                        <!-- Bottom Navigation Step Buttons -->
                        <div class="mt-8 pt-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <Button
                                v-if="currentTab !== 'basic'"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="() => {
                                    const idx = tabs.findIndex(t => t.id === currentTab);
                                    if (idx > 0) currentTab = tabs[idx - 1].id as any;
                                }"
                            >
                                ← Previous Section
                            </Button>
                            <div v-else></div>

                            <Button
                                type="submit"
                                variant="primary"
                                size="sm"
                                :disabled="form.processing"
                            >
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
