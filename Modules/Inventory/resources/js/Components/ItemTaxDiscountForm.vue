<script setup lang="ts">
import { computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { useTaxTerminology } from '@/utils/taxTerminology';
import { Card, Select, Badge } from '@/components';

interface TaxRate {
    id: number | string;
    name: string;
    rate: number;
    code?: string;
}

const props = defineProps<{
    form: {
        tax_type: 'taxable' | 'zero_rated' | 'exempt' | 'out_of_scope';
        tax_rate_id: number | string | '';
        tax_inclusive: boolean;
        hsn_sac_code: string;
        allow_discount: boolean;
        max_discount_percentage: number | string;
        selling_price?: number | string;
    };
    taxRates?: TaxRate[];
}>();

const { currency } = useCurrency();
const { terms } = useTaxTerminology();

// Default tax rates fallback if not provided
const availableTaxRates = computed<TaxRate[]>(() => {
    if (props.taxRates && props.taxRates.length > 0) {
        return props.taxRates;
    }
    return [
        { id: 'standard_6', name: 'Standard Rate (6%)', rate: 6, code: 'SR-6' },
        { id: 'standard_8', name: 'Standard Rate (8%)', rate: 8, code: 'SR-8' },
        { id: 'standard_10', name: 'Sales Tax (10%)', rate: 10, code: 'ST-10' },
        { id: 'zero_0', name: 'Zero Rate (0%)', rate: 0, code: 'ZR-0' },
        { id: 'exempt_0', name: 'Exempt Rate (0%)', rate: 0, code: 'EX-0' },
    ];
});

const currentTaxRate = computed(() => {
    if (props.form.tax_type === 'exempt' || props.form.tax_type === 'zero_rated' || props.form.tax_type === 'out_of_scope') {
        return 0;
    }
    const found = availableTaxRates.value.find(t => String(t.id) === String(props.form.tax_rate_id));
    return found ? found.rate : 0;
});

// Calculate tax breakdown simulator
const sellPrice = computed(() => parseFloat(String(props.form.selling_price || 0)) || 0);

const taxBreakdown = computed(() => {
    const rate = currentTaxRate.value;
    const price = sellPrice.value;

    if (rate <= 0 || price <= 0) {
        return {
            netPrice: price,
            taxAmount: 0,
            grossPrice: price,
        };
    }

    if (props.form.tax_inclusive) {
        // Price includes tax: Net = Price / (1 + rate/100)
        const net = price / (1 + (rate / 100));
        const tax = price - net;
        return {
            netPrice: net,
            taxAmount: tax,
            grossPrice: price,
        };
    } else {
        // Price excludes tax: Gross = Price * (1 + rate/100)
        const tax = price * (rate / 100);
        const gross = price + tax;
        return {
            netPrice: price,
            taxAmount: tax,
            grossPrice: gross,
        };
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header -->
        <div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                Taxation, HS Codes & Discount Policy
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                Configure statutory tax rates, inclusive/exclusive pricing display, customs codes, and cashier discount limits.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Tax Configuration (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <Card class="p-6">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        Tax Categorization
                    </h4>

                    <!-- Tax Type Pills -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Tax Treatment
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <button
                                    type="button"
                                    @click="form.tax_type = 'taxable'"
                                    :class="[
                                        'p-3 rounded-lg text-left border transition-all text-xs font-medium',
                                        form.tax_type === 'taxable'
                                            ? 'border-primary-600 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-950/60 dark:text-primary-200 shadow-xs ring-2 ring-primary-500/20'
                                            : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-700'
                                    ]"
                                >
                                    <div class="font-semibold text-sm">Taxable</div>
                                    <div class="text-[11px] opacity-80 mt-0.5">Standard tax applied</div>
                                </button>

                                <button
                                    type="button"
                                    @click="form.tax_type = 'zero_rated'"
                                    :class="[
                                        'p-3 rounded-lg text-left border transition-all text-xs font-medium',
                                        form.tax_type === 'zero_rated'
                                            ? 'border-primary-600 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-950/60 dark:text-primary-200 shadow-xs ring-2 ring-primary-500/20'
                                            : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-700'
                                    ]"
                                >
                                    <div class="font-semibold text-sm">Zero-Rated</div>
                                    <div class="text-[11px] opacity-80 mt-0.5">0% VAT/GST/SST</div>
                                </button>

                                <button
                                    type="button"
                                    @click="form.tax_type = 'exempt'"
                                    :class="[
                                        'p-3 rounded-lg text-left border transition-all text-xs font-medium',
                                        form.tax_type === 'exempt'
                                            ? 'border-primary-600 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-950/60 dark:text-primary-200 shadow-xs ring-2 ring-primary-500/20'
                                            : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-700'
                                    ]"
                                >
                                    <div class="font-semibold text-sm">Exempt</div>
                                    <div class="text-[11px] opacity-80 mt-0.5">Statutorily exempt</div>
                                </button>

                                <button
                                    type="button"
                                    @click="form.tax_type = 'out_of_scope'"
                                    :class="[
                                        'p-3 rounded-lg text-left border transition-all text-xs font-medium',
                                        form.tax_type === 'out_of_scope'
                                            ? 'border-primary-600 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-950/60 dark:text-primary-200 shadow-xs ring-2 ring-primary-500/20'
                                            : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-700'
                                    ]"
                                >
                                    <div class="font-semibold text-sm">Out of Scope</div>
                                    <div class="text-[11px] opacity-80 mt-0.5">Non-taxable supply</div>
                                </button>
                            </div>
                        </div>

                        <!-- Tax Rate Selector (Shown when Taxable) -->
                        <div v-if="form.tax_type === 'taxable'" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Applicable Tax Rate <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.tax_rate_id"
                                    class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white"
                                >
                                    <option value="">Select tax rate...</option>
                                    <option
                                        v-for="rate in availableTaxRates"
                                        :key="rate.id"
                                        :value="rate.id"
                                    >
                                        {{ rate.name }} ({{ rate.rate }}%)
                                    </option>
                                </select>
                            </div>

                            <!-- Inclusive vs Exclusive Switch -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Price Quotation Method
                                </label>
                                <div class="flex items-center gap-3 mt-2">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.tax_inclusive"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                                    </label>
                                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ form.tax_inclusive ? 'Tax-Inclusive (Prices in catalog include tax)' : 'Tax-Exclusive (Tax added at checkout)' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- HS / SAC Code -->
                        <div class="pt-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                HSN / SAC / Customs Tariff Code
                            </label>
                            <input
                                v-model="form.hsn_sac_code"
                                type="text"
                                placeholder="e.g. 8471.30.20 or 998311"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white font-mono"
                            />
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Required for electronic invoicing, tax reporting, and cross-border customs declarations.
                            </p>
                        </div>
                    </div>
                </Card>

                <!-- Discount Policy -->
                <Card class="p-6">
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                        Discount Controls & Limits
                    </h4>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Allow Line Discounts</span>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Allow cashier or sales rep to apply manual discount on this item.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.allow_discount"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                            </label>
                        </div>

                        <div v-if="form.allow_discount" class="pt-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Maximum Allowable Discount (%)
                            </label>
                            <div class="relative max-w-xs">
                                <input
                                    v-model.number="form.max_discount_percentage"
                                    type="number"
                                    min="0"
                                    max="100"
                                    placeholder="e.g. 15"
                                    class="w-full pr-8 pl-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white font-mono"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-zinc-400 text-xs font-semibold">
                                    %
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Prevents cashiers from giving discounts exceeding this threshold without manager approval.
                            </p>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Tax Calculation Live Simulator Card (1 col) -->
            <div class="space-y-6">
                <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-800 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Live Tax Breakdown</span>
                        <span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-mono">
                            {{ currentTaxRate }}% {{ form.tax_type }}
                        </span>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between items-center text-zinc-500 dark:text-zinc-400">
                            <span>Catalog Entered Price:</span>
                            <span class="font-mono text-zinc-900 dark:text-zinc-200">{{ currency }} {{ sellPrice.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-zinc-500 dark:text-zinc-400">
                            <span>Pricing Mode:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-200">{{ form.tax_inclusive ? 'Tax-Inclusive' : 'Tax-Exclusive' }}</span>
                        </div>
                        <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>
                        <div class="flex justify-between items-center text-zinc-600 dark:text-zinc-300">
                            <span>Net Price (Excl. Tax):</span>
                            <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ currency }} {{ taxBreakdown.netPrice.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-amber-600 dark:text-amber-400">
                            <span>Tax Amount ({{ currentTaxRate }}%):</span>
                            <span class="font-mono text-sm font-semibold">+ {{ currency }} {{ taxBreakdown.taxAmount.toFixed(2) }}</span>
                        </div>
                        <div class="h-px bg-zinc-100 dark:bg-zinc-800 my-2"></div>
                        <div class="flex justify-between items-center text-zinc-900 dark:text-white text-sm font-bold pt-1">
                            <span>Total Customer Price:</span>
                            <span class="font-mono text-base text-emerald-600 dark:text-emerald-400">{{ currency }} {{ taxBreakdown.grossPrice.toFixed(2) }}</span>
                        </div>
                    </div>

                    <div class="pt-2 text-[11px] text-zinc-400 dark:text-zinc-500 italic">
                        * Exact rounding and invoices are processed and validated by the backend accounting engine.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
