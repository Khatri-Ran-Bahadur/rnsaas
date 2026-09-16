<script setup lang="ts">
import { computed } from 'vue';
import { Select } from '@/components';

interface Unit {
    id: number;
    code: string;
    name: string;
}

const props = defineProps<{
    form: {
        base_unit: string;
        purchase_unit: string;
        sales_unit: string;
        stock_unit: string;
        purchase_conversion_factor: number;
        sales_conversion_factor: number;
    };
    units: Unit[];
}>();

const unitOptions = computed(() => {
    return props.units.map((u) => ({
        label: `${u.name} (${u.code})`,
        value: u.code,
    }));
});

const isPurchaseUnitDifferent = computed(() => {
    return props.form.purchase_unit && props.form.purchase_unit !== props.form.base_unit;
});

const isSalesUnitDifferent = computed(() => {
    return props.form.sales_unit && props.form.sales_unit !== props.form.base_unit;
});
</script>

<template>
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                Units of Measure & Conversion Rules
            </h3>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                Define the primary stock keeping unit and configure packaging conversions for purchases and retail/POS sales.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Base / Stock Unit -->
            <div class="p-4 bg-indigo-50/40 dark:bg-indigo-950/20 rounded-xl border border-indigo-200/70 dark:border-indigo-900/50">
                <label class="block text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider mb-1.5">
                    Base / Stock Unit <span class="text-red-500">*</span>
                </label>
                <Select
                    v-model="form.base_unit"
                    :options="unitOptions"
                    placeholder="Select base unit..."
                />
                <p class="text-[11px] text-indigo-700/80 dark:text-indigo-400 mt-2">
                    Primary unit used for inventory valuation, stock counts, and recipe calculations.
                </p>
            </div>

            <!-- Purchase Unit -->
            <div class="p-4 bg-slate-50/70 dark:bg-zinc-800/40 rounded-xl border border-slate-200 dark:border-zinc-800">
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                    Default Purchase Unit
                </label>
                <Select
                    v-model="form.purchase_unit"
                    :options="unitOptions"
                    placeholder="Select purchase unit..."
                />
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-2">
                    Default unit when issuing Purchase Bills and receiving supplier stock.
                </p>
            </div>

            <!-- Sales / POS Unit -->
            <div class="p-4 bg-slate-50/70 dark:bg-zinc-800/40 rounded-xl border border-slate-200 dark:border-zinc-800">
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                    Default Sales / POS Unit
                </label>
                <Select
                    v-model="form.sales_unit"
                    :options="unitOptions"
                    placeholder="Select sales unit..."
                />
                <p class="text-[11px] text-slate-500 dark:text-zinc-400 mt-2">
                    Default unit applied on POS counters, quotes, and customer sales invoices.
                </p>
            </div>
        </div>

        <!-- Conversion Factors Preview & Configuration -->
        <div class="space-y-4 pt-2 border-t border-slate-200 dark:border-zinc-800">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                Conversion Formulas
            </h4>

            <!-- Purchase Conversion -->
            <div
                v-if="isPurchaseUnitDifferent"
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-slate-50 dark:bg-zinc-800/60 rounded-xl border border-slate-200 dark:border-zinc-700"
            >
                <div class="space-y-0.5">
                    <span class="text-xs font-bold text-slate-900 dark:text-white">
                        Purchase Unit Conversion
                    </span>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        1 {{ form.purchase_unit }} = {{ form.purchase_conversion_factor || 1 }} {{ form.base_unit }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-slate-600 dark:text-zinc-400">Multiplier:</span>
                    <input
                        v-model.number="form.purchase_conversion_factor"
                        type="number"
                        min="0.0001"
                        step="any"
                        placeholder="e.g. 24"
                        class="w-28 font-mono rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    />
                    <span class="text-xs font-bold text-slate-700 dark:text-zinc-300">{{ form.base_unit }}</span>
                </div>
            </div>

            <!-- Sales Conversion -->
            <div
                v-if="isSalesUnitDifferent"
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-slate-50 dark:bg-zinc-800/60 rounded-xl border border-slate-200 dark:border-zinc-700"
            >
                <div class="space-y-0.5">
                    <span class="text-xs font-bold text-slate-900 dark:text-white">
                        Sales Unit Conversion
                    </span>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        1 {{ form.sales_unit }} = {{ form.sales_conversion_factor || 1 }} {{ form.base_unit }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-slate-600 dark:text-zinc-400">Multiplier:</span>
                    <input
                        v-model.number="form.sales_conversion_factor"
                        type="number"
                        min="0.0001"
                        step="any"
                        placeholder="e.g. 1"
                        class="w-28 font-mono rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-900 focus:border-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    />
                    <span class="text-xs font-bold text-slate-700 dark:text-zinc-300">{{ form.base_unit }}</span>
                </div>
            </div>

            <div v-if="!isPurchaseUnitDifferent && !isSalesUnitDifferent" class="p-4 bg-slate-50/50 dark:bg-zinc-800/20 rounded-xl text-xs text-slate-500 dark:text-zinc-400">
                Purchase, sales, and stock units are currently identical (1:1 standard base ratio).
            </div>
        </div>
    </div>
</template>
