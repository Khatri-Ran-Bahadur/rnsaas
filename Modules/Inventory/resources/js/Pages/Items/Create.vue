<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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

const props = defineProps<{
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
    // Basic Info
    name: '',
    sku: '',
    item_code: '',
    type: 'stock' as 'stock' | 'service' | 'non_stock',
    category_id: '' as number | '',
    subcategory_id: '' as number | '',
    branch_id: '' as number | '',
    brand_id: '' as number | string | '',
    brand_name: '',
    manufacturer: '',
    status: 'active' as 'active' | 'draft' | 'archived',
    description: '',
    internal_notes: '',
    image_url: null as string | null,

    // Identification
    barcode_symbology: 'CODE128',
    barcode: '',
    secondary_barcodes: [] as string[],
    mpn: '',
    supplier_item_code: '',

    // Units
    base_unit_id: '' as number | string | '',
    purchase_unit_id: '' as number | string | '',
    sales_unit_id: '' as number | string | '',
    unit_conversions: [] as any[],

    // Pricing
    cost_price: '' as number | string,
    selling_price: '' as number | string,
    min_selling_price: '' as number | string,
    wholesale_price: '' as number | string,
    retail_price: '' as number | string,
    pos_price: '' as number | string,
    ecommerce_price: '' as number | string,
    price_tiers: [] as any[],

    // Tax & Discounts
    tax_type: 'taxable' as 'taxable' | 'zero_rated' | 'exempt' | 'out_of_scope',
    tax_rate_id: '' as number | string | '',
    tax_inclusive: false,
    hsn_sac_code: '',
    allow_discount: true,
    max_discount_percentage: 20 as number | string,

    // Inventory & Tracking
    track_inventory: true,
    tracking_type: 'standard' as 'standard' | 'batch' | 'serial',
    valuation_method: 'fifo' as 'fifo' | 'average' | 'standard' | 'specific',
    min_stock: 5 as number | string,
    reorder_point: 10 as number | string,
    reorder_quantity: 20 as number | string,
    max_stock: 100 as number | string,
    lead_time_days: 7 as number | string,
    allow_negative_stock: false,
    has_expiry: false,

    // Variants
    has_variants: false,
    variant_attributes: [] as any[],
    variants: [] as any[],

    // POS & Kitchen
    is_pos_available: true,
    is_pos_favorite: false,
    pos_display_name: '',
    pos_color: 'zinc',
    pos_kitchen_station: '',
    prompt_for_quantity: false,
    is_weighable: false,
    modifier_groups: [] as any[],

    // Recipe / BOM
    has_recipe_bom: false,
    yield_quantity: 1 as number | string,
    yield_unit: 'Portion',
    scrap_percentage: 0 as number | string,
    preparation_notes: '',
    bom_items: [] as any[],
});

const submit = (status?: 'active' | 'draft') => {
    if (status) {
        form.status = status;
    }
    form.post('/admin/inventory/items', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Create New Product / Item" />

        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- Header with Breadcrumbs & Action Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory Items</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Create</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Create New Product / Item
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure unified product information, pricing, multi-UOM, inventory tracking, POS touch tiles, and BOM recipes.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/inventory/items">
                        <Button variant="ghost" size="sm">Cancel</Button>
                    </Link>
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        :disabled="form.processing"
                        @click="submit('draft')"
                    >
                        Save as Draft
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        :disabled="form.processing"
                        @click="submit('active')"
                    >
                        {{ form.processing ? 'Saving...' : 'Save & Publish' }}
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
                    <form @submit.prevent="submit('active')">
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

                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="currentTab !== 'recipe'"
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    @click="() => {
                                        const idx = tabs.findIndex(t => t.id === currentTab);
                                        if (idx < tabs.length - 1) currentTab = tabs[idx + 1].id as any;
                                    }"
                                >
                                    Next Section →
                                </Button>
                                <Button
                                    type="submit"
                                    variant="primary"
                                    size="sm"
                                    :disabled="form.processing"
                                >
                                    Save Product
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
