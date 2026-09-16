<script setup lang="ts">
import { computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { TextInput, Card, Badge, Button } from '@/components';

interface PriceTier {
    id?: string | number;
    tier_name: string;
    min_quantity: number;
    price: number;
    discount_percentage?: number;
}

const props = defineProps<{
    form: {
        cost_price: number | string;
        selling_price: number | string;
        min_selling_price: number | string;
        wholesale_price: number | string;
        retail_price: number | string;
        pos_price: number | string;
        ecommerce_price: number | string;
        price_tiers: PriceTier[];
    };
}>();

const { currency } = useCurrency();

// Helper calculations
const costVal = computed(() => parseFloat(String(props.form.cost_price || 0)) || 0);
const sellVal = computed(() => parseFloat(String(props.form.selling_price || 0)) || 0);
const minSellVal = computed(() => parseFloat(String(props.form.min_selling_price || 0)) || 0);

const profitAmount = computed(() => {
    return sellVal.value - costVal.value;
});

const markupPercentage = computed(() => {
    if (costVal.value <= 0) return 0;
    return ((sellVal.value - costVal.value) / costVal.value) * 100;
});

const marginPercentage = computed(() => {
    if (sellVal.value <= 0) return 0;
    return ((sellVal.value - costVal.value) / sellVal.value) * 100;
});

const isBelowMin = computed(() => {
    return minSellVal.value > 0 && sellVal.value < minSellVal.value;
});

const addPriceTier = () => {
    if (!props.form.price_tiers) {
        props.form.price_tiers = [];
    }
    props.form.price_tiers.push({
        id: Date.now(),
        tier_name: `Tier ${props.form.price_tiers.length + 1}`,
        min_quantity: 10,
        price: sellVal.value > 0 ? Number((sellVal.value * 0.95).toFixed(2)) : 0,
        discount_percentage: 5,
    });
};

const removePriceTier = (index: number) => {
    props.form.price_tiers.splice(index, 1);
};

const syncFromSelling = (target: 'retail' | 'pos' | 'wholesale' | 'ecommerce') => {
    const val = props.form.selling_price;
    if (target === 'retail') props.form.retail_price = val;
    if (target === 'pos') props.form.pos_price = val;
    if (target === 'wholesale') props.form.wholesale_price = val;
    if (target === 'ecommerce') props.form.ecommerce_price = val;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header -->
        <div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                Pricing & Multi-Channel Rates
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                Define cost baseline, standard selling price, margin metrics, channel rates, and quantity-tiered pricing.
            </p>
        </div>

        <!-- Margin & Profit Analytics Preview Card -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded-xl bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900/60 dark:to-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800">
            <div class="space-y-1">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Gross Profit per Unit</span>
                <div class="text-xl font-bold font-mono" :class="profitAmount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                    {{ currency }} {{ profitAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <p class="text-[11px] text-zinc-400">Selling Price − Purchase Cost</p>
            </div>

            <div class="space-y-1">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Gross Margin</span>
                <div class="text-xl font-bold font-mono" :class="marginPercentage >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                    {{ marginPercentage.toFixed(1) }}%
                </div>
                <p class="text-[11px] text-zinc-400">Profit ÷ Selling Price</p>
            </div>

            <div class="space-y-1">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Markup on Cost</span>
                <div class="text-xl font-bold font-mono" :class="markupPercentage >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-rose-600 dark:text-rose-400'">
                    {{ markupPercentage.toFixed(1) }}%
                </div>
                <p class="text-[11px] text-zinc-400">Profit ÷ Cost Price</p>
            </div>

            <div class="space-y-1">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Floor Guard</span>
                <div class="pt-0.5">
                    <span
                        v-if="isBelowMin"
                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-900"
                    >
                        ⚠️ Below Min Floor Price
                    </span>
                    <span
                        v-else-if="minSellVal > 0"
                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900"
                    >
                        ✓ Above Floor ({{ currency }} {{ minSellVal.toFixed(2) }})
                    </span>
                    <span v-else class="text-xs text-zinc-400">No floor price enforced</span>
                </div>
                <p class="text-[11px] text-zinc-400">Prevents selling below cost</p>
            </div>
        </div>

        <!-- Primary Core Prices Grid -->
        <Card class="p-6">
            <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                Core Price Structure
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Cost / Purchase Price -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Cost / Purchase Price ({{ currency }})
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400 text-sm font-mono">
                            {{ currency }}
                        </div>
                        <input
                            v-model.number="form.cost_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full pl-14 pr-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-white dark:text-white font-mono"
                        />
                    </div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Default procurement cost per base unit.
                    </p>
                </div>

                <!-- Standard Selling Price -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Standard Selling Price ({{ currency }}) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400 text-sm font-mono">
                            {{ currency }}
                        </div>
                        <input
                            v-model.number="form.selling_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full pl-14 pr-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-white dark:text-white font-mono font-semibold"
                        />
                    </div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Baseline list price before customer or channel discounts.
                    </p>
                </div>

                <!-- Minimum Floor Selling Price -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Minimum Floor Price ({{ currency }})
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400 text-sm font-mono">
                            {{ currency }}
                        </div>
                        <input
                            v-model.number="form.min_selling_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full pl-14 pr-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-white dark:text-white font-mono"
                        />
                    </div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Cashier & sales rep minimum allowable price barrier.
                    </p>
                </div>
            </div>
        </Card>

        <!-- Channel-Specific Pricing Grid -->
        <Card class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Multi-Channel & Segment Rates
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Override selling price per sales channel or customer group. Leave blank or copy from standard price.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Retail Price -->
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">Retail / Walk-in</span>
                        <button
                            type="button"
                            @click="syncFromSelling('retail')"
                            class="text-[11px] text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium"
                        >
                            Sync std
                        </button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400 text-xs font-mono">
                            {{ currency }}
                        </div>
                        <input
                            v-model.number="form.retail_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Same as std"
                            class="w-full pl-12 pr-2.5 py-1.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-white dark:text-white font-mono"
                        />
                    </div>
                </div>

                <!-- POS Register Price -->
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">POS Quick Price</span>
                        <button
                            type="button"
                            @click="syncFromSelling('pos')"
                            class="text-[11px] text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium"
                        >
                            Sync std
                        </button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400 text-xs font-mono">
                            {{ currency }}
                        </div>
                        <input
                            v-model.number="form.pos_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Same as std"
                            class="w-full pl-12 pr-2.5 py-1.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-white dark:text-white font-mono"
                        />
                    </div>
                </div>

                <!-- Wholesale Price -->
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">Wholesale / B2B</span>
                        <button
                            type="button"
                            @click="syncFromSelling('wholesale')"
                            class="text-[11px] text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium"
                        >
                            Sync std
                        </button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400 text-xs font-mono">
                            {{ currency }}
                        </div>
                        <input
                            v-model.number="form.wholesale_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Bulk rate"
                            class="w-full pl-12 pr-2.5 py-1.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-white dark:text-white font-mono"
                        />
                    </div>
                </div>

                <!-- Online / eCommerce Price -->
                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">eCommerce / Web</span>
                        <button
                            type="button"
                            @click="syncFromSelling('ecommerce')"
                            class="text-[11px] text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium"
                        >
                            Sync std
                        </button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400 text-xs font-mono">
                            {{ currency }}
                        </div>
                        <input
                            v-model.number="form.ecommerce_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Online rate"
                            class="w-full pl-12 pr-2.5 py-1.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-white dark:text-white font-mono"
                        />
                    </div>
                </div>
            </div>
        </Card>

        <!-- Volume / Quantity Tier Pricing Table -->
        <Card class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Quantity-Tiered Pricing Matrix
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Automatic wholesale volume breaks when ordering larger quantities.
                    </p>
                </div>
                <Button type="button" variant="outline" size="sm" @click="addPriceTier">
                    + Add Price Break
                </Button>
            </div>

            <div v-if="form.price_tiers && form.price_tiers.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-500 uppercase">
                            <th class="pb-3 px-2">Tier Label</th>
                            <th class="pb-3 px-2">Min Quantity</th>
                            <th class="pb-3 px-2">Unit Price ({{ currency }})</th>
                            <th class="pb-3 px-2">Discount %</th>
                            <th class="pb-3 px-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr v-for="(tier, idx) in form.price_tiers" :key="tier.id || idx">
                            <td class="py-2.5 px-2">
                                <input
                                    v-model="tier.tier_name"
                                    type="text"
                                    placeholder="e.g. 10+ Bulk"
                                    class="w-full px-2.5 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white"
                                />
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model.number="tier.min_quantity"
                                    type="number"
                                    min="1"
                                    class="w-24 px-2.5 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2">
                                <input
                                    v-model.number="tier.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-32 px-2.5 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-1 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono"
                                />
                            </td>
                            <td class="py-2.5 px-2">
                                <span class="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                    {{ sellVal > 0 && tier.price ? (((sellVal - tier.price) / sellVal) * 100).toFixed(1) + '%' : '-' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2 text-right">
                                <button
                                    type="button"
                                    @click="removePriceTier(idx)"
                                    class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 hover:underline"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-6 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    No quantity discount tiers defined. Standard selling price will apply to all quantities.
                </p>
            </div>
        </Card>
    </div>
</template>
