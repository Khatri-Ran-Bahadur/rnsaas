<script setup lang="ts">
import { computed } from 'vue';
import { Card, Badge } from '@/components';
import { useCurrency } from '@/composables/useCurrency';

const { currency, currencySymbol, formatMoney } = useCurrency();

const props = withDefaults(
    defineProps<{
        amount?: number | string;
        taxRate?: number | string;
        isInclusive?: boolean;
        title?: string;
    }>(),
    {
        amount: 100,
        taxRate: 8,
        isInclusive: false,
        title: 'Tax Calculation & Price Quotation Preview',
    }
);

const numAmount = computed(() => parseFloat(String(props.amount || 0)) || 0);
const numRate = computed(() => parseFloat(String(props.taxRate || 0)) || 0);

const calculation = computed(() => {
    const rate = numRate.value;
    const val = numAmount.value;

    if (rate <= 0 || val <= 0) {
        return {
            netBase: val,
            taxAmount: 0,
            grossTotal: val,
            effectiveRate: 0,
        };
    }

    if (props.isInclusive) {
        // Price includes tax: Base = Total / (1 + rate/100)
        const net = val / (1 + (rate / 100));
        const tax = val - net;
        return {
            netBase: net,
            taxAmount: tax,
            grossTotal: val,
            effectiveRate: rate,
        };
    } else {
        // Price excludes tax: Total = Base + (Base * rate/100)
        const tax = val * (rate / 100);
        const gross = val + tax;
        return {
            netBase: val,
            taxAmount: tax,
            grossTotal: gross,
            effectiveRate: rate,
        };
    }
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">
                {{ title }}
            </span>
            <span
                :class="[
                    'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                    isInclusive
                        ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800'
                        : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800'
                ]"
            >
                {{ isInclusive ? 'Tax-Inclusive Mode' : 'Tax-Exclusive Mode' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <!-- 1. Net Base -->
            <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 space-y-1">
                <span class="text-[11px] font-medium text-zinc-500 uppercase">Taxable Net Base</span>
                <div class="text-lg font-bold font-mono text-zinc-900 dark:text-white">
                    {{ currency }} {{ calculation.netBase.toFixed(2) }}
                </div>
                <p class="text-[10px] text-zinc-400">
                    {{ isInclusive ? 'Derived before tax' : 'Entered catalog unit price' }}
                </p>
            </div>

            <!-- 2. Tax Component -->
            <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 space-y-1">
                <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400 uppercase">
                    Tax Amount ({{ numRate }}%)
                </span>
                <div class="text-lg font-bold font-mono text-amber-600 dark:text-amber-400">
                    + {{ currency }} {{ calculation.taxAmount.toFixed(2) }}
                </div>
                <p class="text-[10px] text-zinc-400">
                    {{ isInclusive ? 'Included in gross price' : 'Added to customer bill' }}
                </p>
            </div>

            <!-- 3. Gross Total -->
            <div class="p-3.5 rounded-xl bg-zinc-900 text-white dark:bg-zinc-950 border border-zinc-800 space-y-1">
                <span class="text-[11px] font-medium text-zinc-400 uppercase">Final Total Amount</span>
                <div class="text-lg font-bold font-mono text-emerald-400">
                    {{ currency }} {{ calculation.grossTotal.toFixed(2) }}
                </div>
                <p class="text-[10px] text-zinc-400">
                    Total payable by customer
                </p>
            </div>
        </div>

        <p class="text-[11px] text-zinc-400 italic">
            * Note: Official tax calculation and precision rounding are processed securely on the backend accounting server.
        </p>
    </div>
</template>
