<script setup lang="ts">
import { computed } from 'vue';
import { Card, Badge } from '@/components';

const props = defineProps<{
    form: {
        track_inventory: boolean;
        tracking_type: 'standard' | 'batch' | 'serial';
        valuation_method: 'fifo' | 'average' | 'standard' | 'specific';
        min_stock: number | string;
        reorder_point: number | string;
        reorder_quantity: number | string;
        max_stock: number | string;
        lead_time_days: number | string;
        allow_negative_stock: boolean;
        has_expiry: boolean;
    };
    itemType?: 'stock' | 'service' | 'non_stock';
}>();

const isStockItem = computed(() => props.itemType === 'stock' || props.form.track_inventory);
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header -->
        <div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                Inventory Tracking, Reorder Thresholds & Traceability
            </h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                Configure valuation method, batch & lot expiry, serial numbers, reorder points, and lead time buffers.
            </p>
        </div>

        <div v-if="!isStockItem" class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 text-amber-800 dark:text-amber-300 text-sm">
            <div class="flex items-center gap-2 font-medium">
                <span>ℹ️ Non-Stock / Service Item Selected</span>
            </div>
            <p class="text-xs mt-1 opacity-90">
                This item is marked as a service or non-stock item. Inventory tracking is disabled by default. If you need to track stock levels, switch the item type to "Stock Item" in Basic Info or toggle tracking below.
            </p>
        </div>

        <!-- Inventory Tracking Toggle Card -->
        <Card class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Enable Inventory Tracking
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Track on-hand balances, warehouse locations, and stock movements across all branches.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.track_inventory"
                        class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                </label>
            </div>

            <!-- Tracking Mode Selector (When Enabled) -->
            <div v-if="form.track_inventory" class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Traceability & Tracking Mode
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Standard Qty -->
                    <button
                        type="button"
                        @click="form.tracking_type = 'standard'"
                        :class="[
                            'p-4 rounded-xl text-left border transition-all text-xs',
                            form.tracking_type === 'standard'
                                ? 'border-primary-600 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-950/60 dark:text-primary-200 shadow-xs ring-2 ring-primary-500/20'
                                : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-700'
                        ]"
                    >
                        <div class="font-bold text-sm">Standard Quantity</div>
                        <div class="text-[11px] opacity-80 mt-1">
                            Tracks aggregate count per location. Ideal for general retail, grocery, raw ingredients, and consumables.
                        </div>
                    </button>

                    <!-- Batch / Lot Tracking -->
                    <button
                        type="button"
                        @click="form.tracking_type = 'batch'"
                        :class="[
                            'p-4 rounded-xl text-left border transition-all text-xs',
                            form.tracking_type === 'batch'
                                ? 'border-primary-600 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-950/60 dark:text-primary-200 shadow-xs ring-2 ring-primary-500/20'
                                : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-700'
                        ]"
                    >
                        <div class="font-bold text-sm flex items-center justify-between">
                            <span>Batch / Lot Tracking</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-500">Perishables</span>
                        </div>
                        <div class="text-[11px] opacity-80 mt-1">
                            Tracks batch/lot numbers, manufacturing dates, and expiration dates. Required for pharmacy and food products.
                        </div>
                    </button>

                    <!-- Serial Number Tracking -->
                    <button
                        type="button"
                        @click="form.tracking_type = 'serial'"
                        :class="[
                            'p-4 rounded-xl text-left border transition-all text-xs',
                            form.tracking_type === 'serial'
                                ? 'border-primary-600 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-950/60 dark:text-primary-200 shadow-xs ring-2 ring-primary-500/20'
                                : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-700'
                        ]"
                    >
                        <div class="font-bold text-sm flex items-center justify-between">
                            <span>Serial Tracking</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400">Electronics</span>
                        </div>
                        <div class="text-[11px] opacity-80 mt-1">
                            Tracks unique serial number / IMEI per individual unit. Ideal for electronics, appliances, and warranty management.
                        </div>
                    </button>
                </div>

                <!-- Expiry Date Tracking Extra Options for Batch -->
                <div v-if="form.tracking_type === 'batch'" class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 mt-4 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Enforce Expiry Date on Inward Goods</span>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Prompt for expiration date when receiving Purchase Orders or Goods Receipt Notes.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="form.has_expiry"
                            class="sr-only peer"
                        />
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-primary-600 dark:peer-checked:bg-primary-600"></div>
                    </label>
                </div>
            </div>
        </Card>

        <!-- Reorder Levels & Safety Buffers (When Enabled) -->
        <Card v-if="form.track_inventory" class="p-6">
            <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                Reorder Points & Stock Thresholds
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Reorder Point -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Reorder Point (ROP)
                    </label>
                    <input
                        v-model.number="form.reorder_point"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white font-mono"
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Triggers low-stock replenishment alert.
                    </p>
                </div>

                <!-- Reorder Quantity -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Reorder Quantity (EOQ)
                    </label>
                    <input
                        v-model.number="form.reorder_quantity"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white font-mono"
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Default quantity populated on PO.
                    </p>
                </div>

                <!-- Min Safety Stock -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Safety Stock (Min Level)
                    </label>
                    <input
                        v-model.number="form.min_stock"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white font-mono"
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Minimum emergency buffer level.
                    </p>
                </div>

                <!-- Max Stock Limit -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Max Stock Capacity
                    </label>
                    <input
                        v-model.number="form.max_stock"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white font-mono"
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Prevents over-purchasing and dead stock.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800">
                <!-- Supplier Lead Time -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Supplier Lead Time (Days)
                    </label>
                    <input
                        v-model.number="form.lead_time_days"
                        type="number"
                        min="0"
                        placeholder="e.g. 7"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white font-mono"
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Estimated transit days from PO confirmation to warehouse receipt.
                    </p>
                </div>

                <!-- Inventory Valuation Method -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Valuation Costing Method
                    </label>
                    <select
                        v-model="form.valuation_method"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 dark:focus:ring-primary-500/20 dark:focus:border-primary-500 dark:text-white"
                    >
                        <option value="fifo">FIFO (First In, First Out)</option>
                        <option value="average">Weighted Moving Average Cost</option>
                        <option value="standard">Standard / Fixed Cost</option>
                        <option value="specific">Specific Identification (Serial/Batch)</option>
                    </select>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Controls how COGS is calculated upon sales dispatch.
                    </p>
                </div>
            </div>
        </Card>

        <!-- Negative Stock Policy Card -->
        <Card v-if="form.track_inventory" class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Allow Negative Stock Dispatch
                    </h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Permits sales/invoicing even when physical on-hand quantity is zero or insufficient.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="form.allow_negative_stock"
                        class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-amber-600 dark:peer-checked:bg-amber-500"></div>
                </label>
            </div>

            <div v-if="form.allow_negative_stock" class="mt-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 text-xs text-amber-800 dark:text-amber-300">
                ⚠️ <strong>Audit Warning:</strong> Negative stock creates temporary accounting variance until matching purchase goods receipts are reconciled. Recommended only for high-speed retail environments with delayed goods receipt entry.
            </div>
        </Card>
    </div>
</template>
