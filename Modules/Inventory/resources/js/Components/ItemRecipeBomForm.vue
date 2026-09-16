<script setup lang="ts">
import { computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { Card, Button, Badge } from '@/components';

interface BomItem {
    id?: string | number;
    ingredient_item_id: number | string;
    ingredient_name?: string;
    quantity: number;
    unit_name: string;
    unit_cost: number;
    wastage_percentage?: number;
}

interface RawMaterial {
    id: number | string;
    name: string;
    sku?: string;
    unit?: string;
    cost_price?: number;
}

const props = defineProps<{
    form: {
        has_recipe_bom: boolean;
        yield_quantity: number | string;
        yield_unit: string;
        scrap_percentage: number | string;
        preparation_notes: string;
        bom_items: BomItem[];
        selling_price?: number | string;
    };
    rawMaterials?: RawMaterial[];
}>();

const { currency } = useCurrency();

// Demo raw materials list if not provided
const availableIngredients = computed<RawMaterial[]>(() => {
    if (props.rawMaterials && props.rawMaterials.length > 0) {
        return props.rawMaterials;
    }
    return [
        { id: 101, name: 'Fresh Milk (Full Cream)', sku: 'ING-MILK', unit: 'Litre', cost_price: 6.50 },
        { id: 102, name: 'Espresso Coffee Beans (Arabica)', sku: 'ING-COFFEE', unit: 'Kg', cost_price: 75.00 },
        { id: 103, name: 'Vanilla Syrup Monin', sku: 'ING-SYRUP', unit: 'Litre', cost_price: 42.00 },
        { id: 104, name: 'Takeaway Paper Cup 12oz', sku: 'PKG-CUP12', unit: 'Pcs', cost_price: 0.35 },
        { id: 105, name: 'Cup Lid & Paper Straw', sku: 'PKG-LID', unit: 'Pcs', cost_price: 0.15 },
        { id: 106, name: 'Burger Bun (Brioche)', sku: 'ING-BUN', unit: 'Pcs', cost_price: 1.20 },
        { id: 107, name: 'Beef Patty 150g', sku: 'ING-BEEF', unit: 'Pcs', cost_price: 4.80 },
        { id: 108, name: 'Cheddar Cheese Slice', sku: 'ING-CHEESE', unit: 'Pcs', cost_price: 0.60 },
    ];
});

const addIngredient = () => {
    if (!props.form.bom_items) {
        props.form.bom_items = [];
    }
    props.form.bom_items.push({
        id: Date.now(),
        ingredient_item_id: '',
        quantity: 1,
        unit_name: 'Pcs',
        unit_cost: 0,
        wastage_percentage: 0,
    });
};

const removeIngredient = (index: number) => {
    props.form.bom_items.splice(index, 1);
};

const onIngredientSelect = (item: BomItem) => {
    const raw = availableIngredients.value.find(i => String(i.id) === String(item.ingredient_item_id));
    if (raw) {
        item.ingredient_name = raw.name;
        item.unit_cost = raw.cost_price || 0;
        item.unit_name = raw.unit || 'Pcs';
    }
};

// Calculate total recipe cost
const totalRecipeCost = computed(() => {
    if (!props.form.bom_items) return 0;
    return props.form.bom_items.reduce((sum, item) => {
        const qty = parseFloat(String(item.quantity || 0)) || 0;
        const cost = parseFloat(String(item.unit_cost || 0)) || 0;
        const waste = parseFloat(String(item.wastage_percentage || 0)) || 0;
        const itemTotal = qty * cost * (1 + waste / 100);
        return sum + itemTotal;
    }, 0);
});

const sellPrice = computed(() => parseFloat(String(props.form.selling_price || 0)) || 0);

const foodCostPercentage = computed(() => {
    if (sellPrice.value <= 0) return 0;
    return (totalRecipeCost.value / sellPrice.value) * 100;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header -->
        <div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                Recipe & Bill of Materials (BOM)
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                Assemble raw ingredients, packaging, or sub-components. Stock of ingredients will be auto-deducted upon sales or manufacturing batch run.
            </p>
        </div>

        <!-- Master BOM Switch Card -->
        <Card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Composite / Manufactured / Recipe Item
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Enable if this finished dish, beverage, assembly, or kit is prepared from raw material ingredients.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.has_recipe_bom"
                        class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                </label>
            </div>

            <!-- Recipe Details when Enabled -->
            <div v-if="form.has_recipe_bom" class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800 space-y-6">
                <!-- Yield Settings -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Output Yield Quantity
                        </label>
                        <input
                            v-model.number="form.yield_quantity"
                            type="number"
                            min="1"
                            placeholder="1"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                        />
                        <p class="mt-1 text-xs text-zinc-500">Output produced per recipe batch run (e.g. 1 portion or 12 units).</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Yield Unit
                        </label>
                        <input
                            v-model="form.yield_unit"
                            type="text"
                            placeholder="e.g. Portion, Plate, Cup, Box"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Expected Scrap / Waste Buffer (%)
                        </label>
                        <input
                            v-model.number="form.scrap_percentage"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                        />
                        <p class="mt-1 text-xs text-zinc-500">Allowance for trimming, spill, or evaporation loss.</p>
                    </div>
                </div>

                <!-- Cost / Food Cost Breakdown Indicator -->
                <div class="p-4 rounded-xl bg-gradient-to-r from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800/60 border border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Calculated Recipe Cost</span>
                        <div class="text-xl font-bold font-mono text-zinc-900 dark:text-white">
                            {{ currency }} {{ totalRecipeCost.toFixed(2) }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Food / Material Cost Ratio</span>
                        <div
                            class="text-xl font-bold font-mono"
                            :class="foodCostPercentage <= 35 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                        >
                            {{ foodCostPercentage.toFixed(1) }}%
                            <span class="text-xs font-normal text-zinc-400">of selling price</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Gross Profit per Unit</span>
                        <div class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                            {{ currency }} {{ (sellPrice - totalRecipeCost).toFixed(2) }}
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Ingredients Table Card -->
        <Card v-if="form.has_recipe_bom" class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Recipe Ingredients & Components ({{ (form.bom_items || []).length }})
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Specify exact raw materials consumed for each batch.
                    </p>
                </div>
                <Button type="button" variant="outline" size="sm" @click="addIngredient">
                    + Add Raw Ingredient
                </Button>
            </div>

            <div v-if="form.bom_items && form.bom_items.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 font-semibold text-zinc-500 uppercase tracking-wider">
                            <th class="pb-3 px-2">Raw Ingredient / Material</th>
                            <th class="pb-3 px-2">Quantity Needed</th>
                            <th class="pb-3 px-2">Unit</th>
                            <th class="pb-3 px-2">Unit Cost ({{ currency }})</th>
                            <th class="pb-3 px-2">Waste %</th>
                            <th class="pb-3 px-2">Subtotal ({{ currency }})</th>
                            <th class="pb-3 px-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr v-for="(item, idx) in form.bom_items" :key="item.id || idx" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                            <td class="py-2.5 px-2 min-w-[200px]">
                                <select
                                    v-model="item.ingredient_item_id"
                                    @change="onIngredientSelect(item)"
                                    class="w-full px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white"
                                >
                                    <option value="">Select ingredient...</option>
                                    <option
                                        v-for="raw in availableIngredients"
                                        :key="raw.id"
                                        :value="raw.id"
                                    >
                                        {{ raw.name }} ({{ raw.unit || 'Unit' }})
                                    </option>
                                </select>
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    class="w-20 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2 text-zinc-600 dark:text-zinc-400">
                                {{ item.unit_name || '-' }}
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model.number="item.unit_cost"
                                    type="number"
                                    step="0.01"
                                    class="w-20 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model.number="item.wastage_percentage"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    class="w-16 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2 font-mono font-semibold text-zinc-900 dark:text-white">
                                {{ ((item.quantity || 0) * (item.unit_cost || 0) * (1 + (item.wastage_percentage || 0) / 100)).toFixed(2) }}
                            </td>
                            <td class="py-2.5 px-2 text-right">
                                <button
                                    type="button"
                                    @click="removeIngredient(idx)"
                                    class="text-rose-600 hover:underline"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-6 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    No ingredients added yet. Click "+ Add Raw Ingredient" to assemble this product recipe.
                </p>
            </div>

            <!-- Preparation / Kitchen SOP Notes -->
            <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Preparation SOP & Cooking Instructions
                </label>
                <textarea
                    v-model="form.preparation_notes"
                    rows="3"
                    placeholder="Step-by-step preparation notes, cooking times, or assembly sequence for kitchen staff..."
                    class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white"
                ></textarea>
            </div>
        </Card>
    </div>
</template>
