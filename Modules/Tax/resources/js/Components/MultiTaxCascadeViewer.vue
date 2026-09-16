<script setup lang="ts">
import { computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { Card, Badge } from '@/components';

interface TaxCascadeItem {
    id: string | number;
    name: string;
    code: string;
    rate: number;
    calculation_type: 'independent' | 'compound' | 'withholding' | 'fixed';
    applied_amount: number;
    description?: string;
}

const props = withDefaults(
    defineProps<{
        subtotal?: number;
        taxes?: TaxCascadeItem[];
    }>(),
    {
        subtotal: 1000,
        taxes: () => [
            {
                id: 1,
                name: 'Standard VAT / Sales Tax',
                code: 'SR-8',
                rate: 8,
                calculation_type: 'independent',
                applied_amount: 80.00,
                description: 'Calculated independently on taxable base (8% of $1,000)',
            },
            {
                id: 2,
                name: 'Local Tourism / City Surcharge',
                code: 'CITY-SUR',
                rate: 2,
                calculation_type: 'independent',
                applied_amount: 20.00,
                description: 'Regional municipality surcharge (2% of $1,000)',
            },
            {
                id: 3,
                name: 'Eco Waste Recovery Levy',
                code: 'ECO-FIXED',
                rate: 0,
                calculation_type: 'fixed',
                applied_amount: 5.00,
                description: 'Mandatory statutory fixed environmental fee',
            },
            {
                id: 4,
                name: 'Contractor Withholding Tax Deduction',
                code: 'WHT-10',
                rate: 10,
                calculation_type: 'withholding',
                applied_amount: -100.00,
                description: 'Deduction held at source for direct statutory remittance',
            },
        ],
    }
);

const { currency } = useCurrency();

const totalTaxImpact = computed(() => {
    return props.taxes.reduce((sum, t) => sum + (t.applied_amount || 0), 0);
});

const finalPayable = computed(() => {
    return props.subtotal + totalTaxImpact.value;
});

const getCalcTypeBadge = (type: string) => {
    switch (type) {
        case 'independent':
            return { label: 'Independent Base', class: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' };
        case 'compound':
            return { label: 'Compound (Tax on Tax)', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' };
        case 'withholding':
            return { label: 'Withholding Deduction', class: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' };
        case 'fixed':
            return { label: 'Fixed Statutory Fee', class: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' };
        default:
            return { label: type, class: 'bg-zinc-100 text-zinc-700' };
    }
};
</script>

<template>
    <Card class="p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                    Multi-Tax Cascade & Waterfall Simulator
                </h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                    Demonstrates how multiple statutory taxes, surcharges, compound cascading, and withholding deductions combine on a single transaction.
                </p>
            </div>
            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                Subtotal: {{ currency }} {{ subtotal.toFixed(2) }}
            </span>
        </div>

        <!-- Waterfall Steps Table -->
        <div class="space-y-3">
            <!-- 0. Subtotal Step -->
            <div class="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-zinc-200 dark:bg-zinc-800 flex items-center justify-center font-bold text-zinc-700 dark:text-zinc-300">0</span>
                    <span class="font-semibold text-zinc-900 dark:text-white">Transaction Line Subtotal (Pre-Tax)</span>
                </div>
                <span class="font-mono font-bold text-sm text-zinc-900 dark:text-white">
                    {{ currency }} {{ subtotal.toFixed(2) }}
                </span>
            </div>

            <!-- Tax Steps -->
            <div
                v-for="(t, idx) in taxes"
                :key="t.id"
                class="flex flex-col sm:flex-row sm:items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors gap-2"
            >
                <div class="flex items-start gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center font-bold shrink-0 mt-0.5">
                        {{ idx + 1 }}
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-zinc-900 dark:text-white">{{ t.name }}</span>
                            <span class="font-mono text-[10px] text-zinc-400">({{ t.code }})</span>
                            <span :class="['px-2 py-0.5 rounded text-[10px] font-semibold', getCalcTypeBadge(t.calculation_type).class]">
                                {{ getCalcTypeBadge(t.calculation_type).label }}
                            </span>
                        </div>
                        <p v-if="t.description" class="text-[11px] text-zinc-400 mt-0.5">
                            {{ t.description }}
                        </p>
                    </div>
                </div>

                <div class="text-right font-mono font-bold text-sm shrink-0" :class="t.applied_amount >= 0 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'">
                    {{ t.applied_amount >= 0 ? '+' : '' }}{{ currency }} {{ t.applied_amount.toFixed(2) }}
                </div>
            </div>

            <!-- Final Net Amount -->
            <div class="flex items-center justify-between p-4 rounded-xl bg-zinc-900 text-white dark:bg-zinc-950 border border-zinc-800 text-sm">
                <div>
                    <span class="font-bold">Net Transaction Total</span>
                    <p class="text-[11px] text-zinc-400">Subtotal + Output Taxes − Withholding Deductions</p>
                </div>
                <div class="text-right">
                    <span class="font-mono text-xl font-extrabold text-emerald-400">
                        {{ currency }} {{ finalPayable.toFixed(2) }}
                    </span>
                </div>
            </div>
        </div>
    </Card>
</template>
