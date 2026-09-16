<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button } from '@/components';

interface MaterialItem {
    id: number;
    item_code: string;
    item_name: string;
    required_qty: number;
    issued_qty: number;
    unit: string;
    lot_number: string;
    warehouse: string;
    status: 'staged' | 'issued' | 'partial' | 'pending';
    unit_cost: number;
}

interface OperationStep {
    id: number;
    step_number: number;
    operation_name: string;
    work_center: string;
    standard_time_min: number;
    actual_time_min: number;
    operator_name: string;
    status: 'pending' | 'in_progress' | 'completed' | 'paused';
    completed_qty: number;
    scrapped_qty: number;
}

interface OrderDetail {
    id: number;
    order_number: string;
    product_name: string;
    sku: string;
    bom_number: string;
    planned_qty: number;
    completed_qty: number;
    scrapped_qty: number;
    unit: string;
    status: 'draft' | 'planned' | 'released' | 'in_progress' | 'completed' | 'closed' | 'cancelled';
    priority: 'low' | 'normal' | 'high' | 'urgent';
    start_date: string;
    due_date: string;
    work_center: string;
    source: string;
    sales_order_ref?: string;
    assigned_supervisor: string;
    progress_percent: number;
    batch_number: string;
    lot_expiry_date: string;
    materials: MaterialItem[];
    operations: OperationStep[];
    standard_cost: number;
    actual_material_cost: number;
    actual_labor_cost: number;
    actual_overhead_cost: number;
}

const props = withDefaults(
    defineProps<{
        order?: OrderDetail;
    }>(),
    {
        order: () => ({
            id: 0,
            order_number: '',
            product_name: '',
            sku: '',
            bom_number: '',
            planned_qty: 0,
            completed_qty: 0,
            scrapped_qty: 0,
            unit: 'Units',
            status: 'draft',
            priority: 'normal',
            start_date: '',
            due_date: '',
            work_center: '',
            source: '',
            assigned_supervisor: '',
            progress_percent: 0,
            batch_number: '',
            lot_expiry_date: '',
            materials: [],
            operations: [],
            standard_cost: 0,
            actual_material_cost: 0,
            actual_labor_cost: 0,
            actual_overhead_cost: 0,
        }),
    }
);

const activeTab = ref<'operations' | 'materials' | 'yield' | 'costing'>('operations');
const showProduceModal = ref(false);
const produceQty = ref(10);
const scrapQty = ref(0);
const scrapReason = ref('');

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'draft': return 'secondary';
        case 'planned': return 'info';
        case 'released': return 'warning';
        case 'in_progress': return 'primary';
        case 'completed': return 'success';
        case 'closed': return 'secondary';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};

const getOpStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'completed': return 'success';
        case 'in_progress': return 'primary';
        case 'paused': return 'warning';
        default: return 'secondary';
    }
};

const totalActualCost = props.order.actual_material_cost + props.order.actual_labor_cost + props.order.actual_overhead_cost;
</script>

<template>
    <Head :title="`MO: ${order.order_number}`" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header with Order Number & Action Controls -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-3">
                            <Link href="/admin/mrp/work-orders" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-semibold">
                                &larr; Back to Orders
                            </Link>
                            <span class="text-slate-300 dark:text-slate-700">|</span>
                            <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-0.5 rounded-lg border border-indigo-200 dark:border-indigo-900">
                                {{ order.order_number }}
                            </span>
                            <Badge :variant="getStatusBadgeVariant(order.status)" size="sm">
                                {{ order.status.replace('_', ' ').toUpperCase() }}
                            </Badge>
                            <Badge variant="secondary" size="sm" class="font-mono">
                                Lot: {{ order.batch_number }}
                            </Badge>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight pt-1">
                            {{ order.product_name }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <span>SKU: <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ order.sku }}</strong></span>
                            <span>•</span>
                            <span>BOM: <strong class="text-slate-700 dark:text-slate-300">{{ order.bom_number }}</strong></span>
                            <span>•</span>
                            <span>Supervisor: <strong class="text-slate-700 dark:text-slate-300">{{ order.assigned_supervisor }}</strong></span>
                            <span>•</span>
                            <span>Due: <strong class="text-slate-700 dark:text-slate-300">{{ order.due_date }}</strong></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <button
                            v-if="order.status === 'planned'"
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md transition-all"
                        >
                            Release to Floor
                        </button>
                        <button
                            v-if="order.status === 'released'"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition-all"
                        >
                            Start Production Run
                        </button>
                        <button
                            v-if="order.status === 'in_progress'"
                            @click="showProduceModal = true"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-900/20 transition-all flex items-center space-x-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Record Yield / FG Receipt</span>
                        </button>
                        <button
                            class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-colors"
                        >
                            Print Job Traveler
                        </button>
                    </div>
                </div>

                <!-- Live Progress KPI Ribbon -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400">Target Output</span>
                        <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5">
                            {{ order.planned_qty }} <span class="text-xs font-normal text-slate-500">{{ order.unit }}</span>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] uppercase tracking-wider font-bold text-emerald-600 dark:text-emerald-400">Good Produced</span>
                        <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                            {{ order.completed_qty }} <span class="text-xs font-normal text-slate-500">({{ order.progress_percent }}%)</span>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] uppercase tracking-wider font-bold text-red-500">Scrapped / Waste</span>
                        <div class="text-lg font-black text-red-500 mt-0.5">
                            {{ order.scrapped_qty }} <span class="text-xs font-normal text-slate-500">{{ order.unit }}</span>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] uppercase tracking-wider font-bold text-indigo-500">Actual Accumulated Cost</span>
                        <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 mt-0.5">
                            ${{ totalActualCost.toFixed(2) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                <button
                    @click="activeTab = 'operations'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2"
                    :class="activeTab === 'operations' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <span>Shop Floor Operations ({{ order.operations.length }})</span>
                </button>
                <button
                    @click="activeTab = 'materials'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2"
                    :class="activeTab === 'materials' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <span>Raw Material Consumption ({{ order.materials.length }})</span>
                </button>
                <button
                    @click="activeTab = 'costing'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2"
                    :class="activeTab === 'costing' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <span>Cost Ledger & Variance</span>
                </button>
            </div>

            <!-- Tab 1: Operations Steps -->
            <div v-if="activeTab === 'operations'" class="space-y-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Work Center Routing Steps</h3>
                    <div class="space-y-3">
                        <div
                            v-for="op in order.operations"
                            :key="op.id"
                            class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex flex-col md:flex-row md:items-center justify-between gap-4"
                        >
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 font-black text-sm flex items-center justify-center flex-shrink-0">
                                    {{ op.step_number }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-slate-100 flex items-center space-x-2">
                                        <span>{{ op.operation_name }}</span>
                                        <Badge :variant="getOpStatusBadgeVariant(op.status)" size="sm">
                                            {{ op.status.toUpperCase() }}
                                        </Badge>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        Work Center: <strong class="text-slate-700 dark:text-slate-300">{{ op.work_center }}</strong> • Operator: {{ op.operator_name || 'Unassigned' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-6 text-xs text-slate-600 dark:text-slate-400">
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase">Standard Time</span>
                                    <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ op.standard_time_min }} min</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase">Actual Logged</span>
                                    <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ op.actual_time_min }} min</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px] uppercase">Yield Complete</span>
                                    <strong class="text-emerald-600 font-mono">{{ op.completed_qty }} pcs</strong>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        v-if="op.status === 'pending'"
                                        class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs"
                                    >
                                        Start Step
                                    </button>
                                    <button
                                        v-if="op.status === 'in_progress'"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs"
                                    >
                                        Log Complete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Raw Material Consumption -->
            <div v-if="activeTab === 'materials'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Exploded Bill of Materials Pick List</h3>
                    <button class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-200">
                        Issue All Staged Items
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-3">Component / Material</th>
                                <th class="px-4 py-3">Required Qty</th>
                                <th class="px-4 py-3">Issued Qty</th>
                                <th class="px-4 py-3">Lot / Batch #</th>
                                <th class="px-4 py-3">Source Warehouse</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Unit Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="mat in order.materials" :key="mat.id" class="hover:bg-slate-50/50">
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ mat.item_name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ mat.item_code }}</div>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                    {{ mat.required_qty }} {{ mat.unit }}
                                </td>
                                <td class="px-4 py-3.5 font-bold" :class="mat.issued_qty >= mat.required_qty ? 'text-emerald-600' : 'text-amber-500'">
                                    {{ mat.issued_qty }} {{ mat.unit }}
                                </td>
                                <td class="px-4 py-3.5 text-xs font-mono text-slate-600 dark:text-slate-400">
                                    {{ mat.lot_number || 'Auto-assign on issue' }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500">
                                    {{ mat.warehouse }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <Badge :variant="mat.status === 'issued' ? 'success' : 'warning'" size="sm">
                                        {{ mat.status.toUpperCase() }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-slate-700 dark:text-slate-300">
                                    ${{ mat.unit_cost.toFixed(2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Cost Ledger & Variance -->
            <div v-if="activeTab === 'costing'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Actual Manufacturing Cost Breakdown</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm py-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400">Direct Raw Materials:</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100">${{ order.actual_material_cost.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm py-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400">Direct Labor (Shop Floor Hours):</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100">${{ order.actual_labor_cost.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm py-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-600 dark:text-slate-400">Machine & Factory Overhead:</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100">${{ order.actual_overhead_cost.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-base py-3 border-t border-slate-200 dark:border-slate-700 font-bold">
                            <span class="text-slate-900 dark:text-white">Total Accumulated Cost:</span>
                            <span class="text-indigo-600 dark:text-indigo-400 font-black">${{ totalActualCost.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Standard vs Actual Cost Variance</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Standard BOM Cost per Unit:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">${{ (order.standard_cost / order.planned_qty).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Actual Cost per Completed Unit:</span>
                            <span class="font-bold" :class="(totalActualCost / (order.completed_qty || 1)) > (order.standard_cost / order.planned_qty) ? 'text-amber-500' : 'text-emerald-500'">
                                ${{ (totalActualCost / (order.completed_qty || 1)).toFixed(2) }}
                            </span>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-xs space-y-1">
                            <div class="font-bold text-slate-700 dark:text-slate-300">Variance Analysis:</div>
                            <p class="text-slate-500">Labor efficiency is 96% within target bounds. Raw material consumption matches BOM tolerances with 0 material over-usage.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yield / FG Receipt Modal -->
            <div v-if="showProduceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 max-w-md w-full shadow-2xl space-y-4">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Record Finished Goods Receipt</h3>
                    <p class="text-xs text-slate-500">Receive completed inventory into depot and log scrap waste.</p>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Good Units Produced ({{ order.unit }})</label>
                            <input v-model="produceQty" type="number" min="1" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Scrapped Units</label>
                            <input v-model="scrapQty" type="number" min="0" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" />
                        </div>
                        <div v-if="scrapQty > 0">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Scrap Reason</label>
                            <input v-model="scrapReason" placeholder="e.g. PCB solder bridge defect during reflow" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button @click="showProduceModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                        <button @click="showProduceModal = false" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md">Confirm Receipt</button>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
