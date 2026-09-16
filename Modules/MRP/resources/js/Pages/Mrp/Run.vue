<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button } from '@/components';

interface PlannedOrder {
    id: number;
    type: 'manufacture' | 'purchase';
    item_code: string;
    item_name: string;
    required_qty: number;
    unit: string;
    suggested_date: string;
    source_demand: string;
    lead_time_days: number;
    supplier_or_workcenter: string;
    status: 'suggested' | 'approved' | 'rejected';
}

interface StockShortage {
    item_code: string;
    item_name: string;
    current_stock: number;
    allocated_stock: number;
    incoming_po: number;
    projected_shortage: number;
    unit: string;
    safety_stock: number;
}

const props = defineProps<{
    shortages: StockShortage[];
    plannedOrders: PlannedOrder[];
    lastRunDate: string;
}>();

const planningHorizon = ref('30');
const isRunningMrp = ref(false);
const runCompleted = ref(false);

const triggerMrpRun = () => {
    isRunningMrp.value = true;
    setTimeout(() => {
        isRunningMrp.value = false;
        runCompleted.value = true;
    }, 1200);
};
</script>

<template>
    <Head title="MRP Material Planning Engine" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Automated Demand Calculator</span>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <span class="text-xs text-slate-500">Last Run: {{ lastRunDate }}</span>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            Material Requirements Planning (MRP)
                        </h1>
                        <p class="text-xs text-slate-500 max-w-2xl">
                            Analyze active Sales Orders, reorder points, and BOM explosions across your manufacturing hierarchy to generate planned production runs and purchasing requisitions.
                        </p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-2 bg-slate-50 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs">
                            <span class="text-slate-500">Horizon:</span>
                            <select v-model="planningHorizon" class="bg-transparent font-bold text-slate-800 dark:text-slate-200 focus:outline-none">
                                <option value="14">14 Days</option>
                                <option value="30">30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90">90 Days (Quarter)</option>
                            </select>
                        </div>

                        <button
                            @click="triggerMrpRun"
                            :disabled="isRunningMrp"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-lg shadow-indigo-900/20 transition-all flex items-center space-x-2 disabled:opacity-50"
                        >
                            <svg v-if="isRunningMrp" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isRunningMrp ? 'Calculating Net Demands...' : 'Run MRP Engine' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Material Shortage Warnings -->
            <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h3 class="text-sm font-bold text-amber-900 dark:text-amber-300">Projected Material Deficits & Stockouts ({{ shortages.length }})</h3>
                    </div>
                    <span class="text-xs text-amber-700 dark:text-amber-400 font-semibold">Calculated from unfulfilled Sales Orders</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="sh in shortages" :key="sh.item_code" class="p-3.5 bg-white dark:bg-slate-900 rounded-xl border border-amber-200 dark:border-amber-900/50 space-y-1">
                        <div class="flex items-start justify-between">
                            <div class="font-bold text-xs text-slate-900 dark:text-white">{{ sh.item_name }}</div>
                            <Badge variant="danger" size="sm">-{{ sh.projected_shortage }} {{ sh.unit }}</Badge>
                        </div>
                        <div class="text-[11px] text-slate-500 font-mono">{{ sh.item_code }}</div>
                        <div class="flex justify-between text-[11px] text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span>On Hand: <strong class="text-slate-700 dark:text-slate-300">{{ sh.current_stock }}</strong></span>
                            <span>Allocated: <strong class="text-slate-700 dark:text-slate-300">{{ sh.allocated_stock }}</strong></span>
                            <span>Safety Min: <strong class="text-slate-700 dark:text-slate-300">{{ sh.safety_stock }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Planned Order Suggestions Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Generated Actionable Orders & Requisitions</h3>
                        <p class="text-xs text-slate-500">Approve recommendations to automatically instantiate Production Orders and Purchase Orders.</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm">
                            Approve All Recommendations
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-3">Order Type</th>
                                <th class="px-4 py-3">Item / Assembly</th>
                                <th class="px-4 py-3">Suggested Qty</th>
                                <th class="px-4 py-3">Suggested Start</th>
                                <th class="px-4 py-3">Source Demand</th>
                                <th class="px-4 py-3">Lead Time</th>
                                <th class="px-4 py-3">Assigned Source</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="order in plannedOrders" :key="order.id" class="hover:bg-slate-50/50">
                                <td class="px-4 py-3.5">
                                    <Badge :variant="order.type === 'manufacture' ? 'primary' : 'secondary'" size="sm">
                                        {{ order.type === 'manufacture' ? 'PROD ORDER' : 'PURCHASE REQ' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ order.item_name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ order.item_code }}</div>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-900 dark:text-slate-100">
                                    {{ order.required_qty }} {{ order.unit }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                                    {{ order.suggested_date }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500 font-mono">
                                    {{ order.source_demand }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                                    {{ order.lead_time_days }} days
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-700 dark:text-slate-300 font-semibold">
                                    {{ order.supplier_or_workcenter }}
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button class="px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 font-bold text-xs hover:bg-emerald-200">
                                            Approve
                                        </button>
                                        <button class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-xs hover:bg-slate-200">
                                            Ignore
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
