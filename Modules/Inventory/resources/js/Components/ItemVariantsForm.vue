<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { Card, Button, Badge } from '@/components';

interface VariantAttribute {
    id: string | number;
    name: string; // e.g. "Color", "Size"
    values: string[]; // e.g. ["Red", "Blue"]
    inputValue?: string;
}

interface ItemVariant {
    id?: string | number;
    name: string;
    sku: string;
    barcode: string;
    attributes: Record<string, string>;
    cost_price: number | string;
    selling_price: number | string;
    is_active: boolean;
}

const props = defineProps<{
    form: {
        has_variants: boolean;
        sku?: string;
        cost_price?: number | string;
        selling_price?: number | string;
        variant_attributes: VariantAttribute[];
        variants: ItemVariant[];
    };
}>();

const { currency } = useCurrency();

// Add new attribute group (e.g. Color, Size, Material)
const addAttribute = () => {
    if (!props.form.variant_attributes) {
        props.form.variant_attributes = [];
    }
    props.form.variant_attributes.push({
        id: Date.now(),
        name: '',
        values: [],
        inputValue: '',
    });
};

const removeAttribute = (index: number) => {
    props.form.variant_attributes.splice(index, 1);
};

// Add tag value on Enter or comma
const addValue = (attr: VariantAttribute) => {
    if (!attr.inputValue || !attr.inputValue.trim()) return;
    const parts = attr.inputValue.split(',').map(s => s.trim()).filter(Boolean);
    parts.forEach(p => {
        if (!attr.values.includes(p)) {
            attr.values.push(p);
        }
    });
    attr.inputValue = '';
};

const removeValue = (attr: VariantAttribute, valIndex: number) => {
    attr.values.splice(valIndex, 1);
};

// Generate Cartesian Product Matrix
const generateVariantsMatrix = () => {
    const activeAttrs = (props.form.variant_attributes || []).filter(
        a => a.name.trim() && a.values.length > 0
    );

    if (activeAttrs.length === 0) {
        props.form.variants = [];
        return;
    }

    // Helper for cartesian product
    const cartesian = (arrays: string[][]): string[][] => {
        return arrays.reduce<string[][]>(
            (acc, curr) => acc.flatMap(c => curr.map(n => [...c, n])),
            [[]]
        );
    };

    const valueArrays = activeAttrs.map(a => a.values);
    const combinations = cartesian(valueArrays);

    const baseSku = props.form.sku || 'SKU';
    const baseCost = props.form.cost_price || 0;
    const baseSell = props.form.selling_price || 0;

    props.form.variants = combinations.map((comb, index) => {
        const attrObj: Record<string, string> = {};
        activeAttrs.forEach((attr, idx) => {
            attrObj[attr.name] = comb[idx];
        });

        const name = comb.join(' / ');
        const skuSuffix = comb.map(v => v.toUpperCase().replace(/\s+/g, '-').slice(0, 4)).join('-');
        const variantSku = `${baseSku}-${skuSuffix}`;

        return {
            id: Date.now() + index,
            name: name,
            sku: variantSku,
            barcode: '',
            attributes: attrObj,
            cost_price: baseCost,
            selling_price: baseSell,
            is_active: true,
        };
    });
};

const removeVariant = (index: number) => {
    props.form.variants.splice(index, 1);
};

const applyBasePricesToAll = () => {
    const baseCost = props.form.cost_price || 0;
    const baseSell = props.form.selling_price || 0;
    props.form.variants.forEach(v => {
        v.cost_price = baseCost;
        v.selling_price = baseSell;
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header -->
        <div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                Product Variations & Attribute Matrix
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                Manage size, color, material, or flavor options with individual SKU codes, barcodes, and price overrides.
            </p>
        </div>

        <!-- Has Variants Toggle Card -->
        <Card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        This item has multiple variations
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Enable if this product comes in different sizes, colors, capacities, or packaging variants.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.has_variants"
                        class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                </label>
            </div>

            <!-- Attribute Builder (When Enabled) -->
            <div v-if="form.has_variants" class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800 space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Variation Options & Attributes</h5>
                        <p class="text-xs text-zinc-500">Define option names (e.g. Size, Color) and their corresponding values.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addAttribute">
                        + Add Attribute
                    </Button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(attr, idx) in form.variant_attributes"
                        :key="attr.id || idx"
                        class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 space-y-3"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="w-1/3">
                                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase mb-1">
                                    Option Name
                                </label>
                                <input
                                    v-model="attr.name"
                                    type="text"
                                    placeholder="e.g. Size or Color"
                                    class="w-full px-3 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white"
                                />
                            </div>
                            <div class="w-2/3 flex items-end justify-between gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase mb-1">
                                        Option Values (Press Enter or Comma)
                                    </label>
                                    <div class="flex gap-2">
                                        <input
                                            v-model="attr.inputValue"
                                            @keydown.enter.prevent="addValue(attr)"
                                            @keydown.comma.prevent="addValue(attr)"
                                            type="text"
                                            placeholder="Type value & press Enter..."
                                            class="w-full px-3 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white"
                                        />
                                        <Button type="button" variant="outline" size="sm" @click="addValue(attr)">
                                            Add
                                        </Button>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeAttribute(idx)"
                                    class="p-2 text-zinc-400 hover:text-rose-600 transition-colors"
                                    title="Remove Option Group"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Pill Tags -->
                        <div v-if="attr.values.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                            <span
                                v-for="(val, valIdx) in attr.values"
                                :key="valIdx"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-200 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200"
                            >
                                {{ val }}
                                <button
                                    type="button"
                                    @click="removeValue(attr, valIdx)"
                                    class="text-zinc-500 hover:text-rose-600"
                                >
                                    &times;
                                </button>
                            </span>
                        </div>
                    </div>

                    <div v-if="!form.variant_attributes || form.variant_attributes.length === 0" class="text-center py-6 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            No variation attributes added yet. Click "+ Add Attribute" to create sizes, colors, etc.
                        </p>
                    </div>

                    <!-- Generate Variants Button -->
                    <div v-if="form.variant_attributes && form.variant_attributes.length > 0" class="flex items-center justify-between pt-2">
                        <span class="text-xs text-zinc-500">
                            Ready to generate matrix combinations based on the attributes above.
                        </span>
                        <Button type="button" variant="primary" size="sm" @click="generateVariantsMatrix">
                            ⚡ Generate Variant Combinations
                        </Button>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Generated Variants Table -->
        <Card v-if="form.has_variants && form.variants && form.variants.length > 0" class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Generated Variations ({{ form.variants.length }})
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Adjust individual SKUs, barcodes, cost, and selling prices for each variation.
                    </p>
                </div>
                <Button type="button" variant="outline" size="sm" @click="applyBasePricesToAll">
                    Apply Base Price to All
                </Button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 font-semibold text-zinc-500 uppercase tracking-wider">
                            <th class="pb-3 px-2">Variant</th>
                            <th class="pb-3 px-2">SKU Code</th>
                            <th class="pb-3 px-2">Barcode</th>
                            <th class="pb-3 px-2">Cost ({{ currency }})</th>
                            <th class="pb-3 px-2">Selling ({{ currency }})</th>
                            <th class="pb-3 px-2 text-center">Status</th>
                            <th class="pb-3 px-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr v-for="(v, idx) in form.variants" :key="v.id || idx" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                            <td class="py-2.5 px-2 font-medium text-zinc-900 dark:text-white">
                                {{ v.name }}
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model="v.sku"
                                    type="text"
                                    class="w-32 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model="v.barcode"
                                    type="text"
                                    placeholder="Optional"
                                    class="w-32 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model.number="v.cost_price"
                                    type="number"
                                    step="0.01"
                                    class="w-24 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model.number="v.selling_price"
                                    type="number"
                                    step="0.01"
                                    class="w-24 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono font-semibold"
                                />
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <input
                                    type="checkbox"
                                    v-model="v.is_active"
                                    class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                                />
                            </td>
                            <td class="py-2.5 px-2 text-right">
                                <button
                                    type="button"
                                    @click="removeVariant(idx)"
                                    class="text-rose-600 hover:text-rose-700 dark:text-rose-400 hover:underline"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </div>
</template>
