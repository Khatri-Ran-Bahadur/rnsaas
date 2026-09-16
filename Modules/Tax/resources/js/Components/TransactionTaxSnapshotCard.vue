<script setup lang="ts">
import { computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { Card, Badge } from '@/components';

interface TaxSnapshotLine {
    item_name: string;
    item_sku?: string;
    quantity: number;
    unit_price: number;
    taxable_base: number;
    snapshot_tax_rate: number;
    snapshot_tax_code: string;
    snapshot_tax_amount: number;
    current_system_rate?: number;
    is_inclusive: boolean;
}

const props = withDefaults(
    defineProps<{
        transactionNumber?: string;
        transactionDate?: string;
        snapshotLines?: TaxSnapshotLine[];
    }>(),
    {
        transactionNumber: 'INV-2024-0012',
        transactionDate: '2024-01-15',
        snapshotLines: () => [
            {
                item_name: 'Commercial Enterprise Server Hosting (Annual)',
                item_sku: 'SRV-HOST-01',
                quantity: 1,
                unit_price: 1000.00,
                taxable_base: 1000.00,
                snapshot_tax_rate: 6.00,
                snapshot_tax_code: 'SR-6-LEG',
                snapshot_tax_amount: 60.00,
                current_system_rate: 8.00,
                is_inclusive: false,
            },
        ],
    }
);

const { currency } = useCurrency();

const totalTax = computed(() => {
    return props.snapshotLines.reduce((sum, l) => sum + (l.snapshot_tax_amount || 0), 0);
});

const totalBase = computed(() => {
    return props.snapshotLines.reduce((sum, l) => sum + (l.taxable_base || 0), 0);
});
</script>

<template>
    <Card class="p-6 border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/10 dark:bg-indigo-950/10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4 border-b border-zinc-200 dark:border-zinc-800 pb-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-sm">🔒</span>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Point-in-Time Tax Audit Snapshot
                    </h4>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        IMMUTABLE
                    </span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                    Recorded for <strong>{{ transactionNumber }}</strong> on <strong>{{ transactionDate }}</strong>. Preserves statutory rates even if master rates change.
                </p>
            </div>
            <div class="text-right font-mono text-xs text-zinc-500">
                Total Tax Locked: <strong class="text-zinc-900 dark:text-white font-bold">{{ currency }} {{ totalTax.toFixed(2) }}</strong>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                        <th class="pb-2">Line Item</th>
                        <th class="pb-2 text-right">Taxable Base</th>
                        <th class="pb-2 text-center">Snapshot Rate at Transaction Date</th>
                        <th class="pb-2 text-center">Current Live Master Rate</th>
                        <th class="pb-2 text-right">Locked Tax Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <tr v-for="(line, idx) in snapshotLines" :key="idx">
                        <td class="py-2.5">
                            <span class="font-medium text-zinc-900 dark:text-white">{{ line.item_name }}</span>
                            <span v-if="line.item_sku" class="font-mono text-[10px] text-zinc-400 ml-2">({{ line.item_sku }})</span>
                        </td>
                        <td class="py-2.5 text-right font-mono">
                            {{ currency }} {{ line.taxable_base.toFixed(2) }}
                        </td>
                        <td class="py-2.5 text-center">
                            <span class="font-mono font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                {{ line.snapshot_tax_rate.toFixed(2) }}% ({{ line.snapshot_tax_code }})
                            </span>
                        </td>
                        <td class="py-2.5 text-center">
                            <span v-if="line.current_system_rate && line.current_system_rate !== line.snapshot_tax_rate" class="font-mono text-xs text-amber-600 dark:text-amber-400">
                                {{ line.current_system_rate.toFixed(2) }}% <span class="text-[10px] text-zinc-400">(Superseded)</span>
                            </span>
                            <span v-else class="font-mono text-xs text-zinc-400">
                                {{ (line.current_system_rate || line.snapshot_tax_rate).toFixed(2) }}% (Same)
                            </span>
                        </td>
                        <td class="py-2.5 text-right font-mono font-bold text-zinc-900 dark:text-white">
                            {{ currency }} {{ line.snapshot_tax_amount.toFixed(2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>
</template>
