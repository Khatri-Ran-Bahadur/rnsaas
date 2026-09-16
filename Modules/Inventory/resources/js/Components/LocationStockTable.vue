<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCurrency } from '@/composables/useCurrency';
import { Card, Badge, Button } from '@/components';

interface LocationStock {
    location_id: number | string;
    location_name: string;
    branch_name?: string;
    type: 'store' | 'warehouse' | 'kitchen' | 'transit';
    on_hand: number;
    reserved: number;
    incoming: number;
    reorder_level?: number;
    bin_rack?: string;
}

const props = defineProps<{
    stockByLocation: LocationStock[];
    unitName?: string;
    costPrice?: number;
    itemId?: number | string;
}>();

const { currency } = useCurrency();

const totalOnHand = computed(() => {
    return props.stockByLocation.reduce((sum, loc) => sum + (loc.on_hand || 0), 0);
});

const totalReserved = computed(() => {
    return props.stockByLocation.reduce((sum, loc) => sum + (loc.reserved || 0), 0);
});

const totalIncoming = computed(() => {
    return props.stockByLocation.reduce((sum, loc) => sum + (loc.incoming || 0), 0);
});

const totalAvailable = computed(() => {
    return totalOnHand.value - totalReserved.value;
});

const totalValuation = computed(() => {
    return totalOnHand.value * (props.costPrice || 0);
});

const getStockBadge = (loc: LocationStock) => {
    const available = (loc.on_hand || 0) - (loc.reserved || 0);
    const reorder = loc.reorder_level || 0;

    if (loc.on_hand <= 0) {
        return { label: 'Out of Stock', color: 'rose', bgClass: 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-900' };
    }
    if (reorder > 0 && available <= reorder) {
        return { label: 'Low Stock Alert', color: 'amber', bgClass: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-900' };
    }
    return { label: 'In Stock', color: 'emerald', bgClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900' };
};
</script>

<template>
    <div class="space-y-6">
        <!-- Stock Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total On-Hand</span>
                <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white">
                    {{ totalOnHand.toLocaleString() }} <span class="text-xs font-normal text-zinc-400">{{ unitName || 'Units' }}</span>
                </div>
                <p class="text-[11px] text-zinc-400">Physical stock counted</p>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Reserved</span>
                <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">
                    {{ totalReserved.toLocaleString() }} <span class="text-xs font-normal text-zinc-400">{{ unitName || 'Units' }}</span>
                </div>
                <p class="text-[11px] text-zinc-400">Committed to open orders</p>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Net Available</span>
                <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                    {{ totalAvailable.toLocaleString() }} <span class="text-xs font-normal text-zinc-400">{{ unitName || 'Units' }}</span>
                </div>
                <p class="text-[11px] text-zinc-400">Available to sell / dispatch</p>
            </div>

            <div class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Incoming POs</span>
                <div class="text-2xl font-bold font-mono text-sky-600 dark:text-sky-400">
                    {{ totalIncoming.toLocaleString() }} <span class="text-xs font-normal text-zinc-400">{{ unitName || 'Units' }}</span>
                </div>
                <p class="text-[11px] text-zinc-400">Confirmed purchase orders</p>
            </div>

            <div class="col-span-2 lg:col-span-1 p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm space-y-1">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Inventory Value</span>
                <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-white">
                    {{ currency }} {{ totalValuation.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <p class="text-[11px] text-zinc-400">At standard cost rate</p>
            </div>
        </div>

        <!-- Location Breakdown Table -->
        <Card class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Warehouse & Branch Breakdown
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Live stock balance across all storage locations, retail stores, and fulfillment hubs.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="itemId"
                        :href="`/admin/inventory/transfers/create?item_id=${itemId}`"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200 transition-colors"
                    >
                        🔄 Transfer Stock
                    </Link>
                    <Link
                        v-if="itemId"
                        :href="`/admin/inventory/adjustments/create?item_id=${itemId}`"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200 transition-colors"
                    >
                        📝 Stock Count / Adjust
                    </Link>
                </div>
            </div>

            <div v-if="stockByLocation && stockByLocation.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 font-semibold text-zinc-500 uppercase tracking-wider">
                            <th class="pb-3 px-3">Location / Warehouse</th>
                            <th class="pb-3 px-3">Type</th>
                            <th class="pb-3 px-3">Aisle / Bin</th>
                            <th class="pb-3 px-3 text-right">On-Hand</th>
                            <th class="pb-3 px-3 text-right">Reserved</th>
                            <th class="pb-3 px-3 text-right">Available</th>
                            <th class="pb-3 px-3 text-right">Incoming</th>
                            <th class="pb-3 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr
                            v-for="loc in stockByLocation"
                            :key="loc.location_id"
                            class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition-colors"
                        >
                            <td class="py-3 px-3">
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ loc.location_name }}
                                </div>
                                <div v-if="loc.branch_name" class="text-[11px] text-zinc-400">
                                    {{ loc.branch_name }}
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="capitalize px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                    {{ loc.type }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-mono text-zinc-500">
                                {{ loc.bin_rack || '—' }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-semibold text-zinc-900 dark:text-white">
                                {{ loc.on_hand.toLocaleString() }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-amber-600 dark:text-amber-400">
                                {{ loc.reserved.toLocaleString() }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ (loc.on_hand - loc.reserved).toLocaleString() }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-sky-600 dark:text-sky-400">
                                {{ loc.incoming.toLocaleString() }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border',
                                        getStockBadge(loc).bgClass
                                    ]"
                                >
                                    {{ getStockBadge(loc).label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-8 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-lg">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">No warehouse stock records found</p>
                <p class="text-xs text-zinc-400 mt-1">Stock balances will appear as goods are received or transferred.</p>
            </div>
        </Card>
    </div>
</template>
