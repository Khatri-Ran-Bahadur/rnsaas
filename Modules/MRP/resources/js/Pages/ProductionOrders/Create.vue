<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, TextInput, Select, Card, Badge } from '@/components';

interface BomOption {
    id: number;
    bom_number: string;
    product_name: string;
    sku: string;
    output_qty: number;
    unit: string;
    estimated_cost: number;
    routing_name: string;
}

const props = withDefaults(
    defineProps<{
        boms?: BomOption[];
    }>(),
    {
        boms: () => [],
    }
);

const form = useForm({
    order_number: 'MO-2026-0089',
    bom_id: props.boms?.[0]?.id || 1,
    planned_qty: 100,
    source: 'Manual Schedule',
    sales_order_ref: '',
    work_center: 'WC-01 (Main Assembly Line A)',
    warehouse_source: 'Raw Material Central Warehouse (WH-RM-01)',
    warehouse_target: 'Finished Goods Main Depot (WH-FG-01)',
    start_date: '2026-09-12',
    due_date: '2026-09-18',
    priority: 'high',
    assigned_supervisor: 'Alex Thorne (Shop Supervisor)',
    notes: 'Prioritized production run for batch delivery commitment.',
});

const selectedBom = computed(() => {
    return props.boms?.find(b => b.id === Number(form.bom_id)) || props.boms?.[0];
});

const estimatedTotalCost = computed(() => {
    if (!selectedBom.value) return 0;
    return (form.planned_qty / (selectedBom.value.output_qty || 1)) * selectedBom.value.estimated_cost;
});

const submit = () => {
    form.post('/admin/mrp/work-orders');
};
</script>

<template>
    <Head title="Create Production Order" />

    <OrganizationLayout>
        <div class="space-y-6 max-w-5xl">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link href="/admin/mrp/work-orders" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 hover:bg-slate-200 transition-colors">
                        &larr;
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Create Production Order</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Release a manufacturing work order from an engineering BOM recipe</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="secondary" href="/admin/mrp/work-orders">Cancel</Button>
                    <Button variant="primary" @click="submit" :loading="form.processing">Create & Release Order</Button>
                </div>
            </div>

            <!-- Form Body -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Configuration -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">1. Product & Recipe Selection</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Production Order #</label>
                                <TextInput v-model="form.order_number" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Select BOM Recipe</label>
                                <select
                                    v-model="form.bom_id"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option v-for="b in boms" :key="b.id" :value="b.id">
                                        {{ b.product_name }} ({{ b.bom_number }} - {{ b.sku }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Planned Target Quantity</label>
                                <div class="flex space-x-2">
                                    <TextInput v-model="form.planned_qty" type="number" min="1" class="w-2/3" />
                                    <div class="w-1/3 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-600 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700">
                                        {{ selectedBom?.unit || 'Units' }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Demand Origin / Trigger</label>
                                <select
                                    v-model="form.source"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="Manual Schedule">Manual Schedule</option>
                                    <option value="Sales Order Demand">Sales Order Demand</option>
                                    <option value="MRP Automated Run">MRP Automated Run</option>
                                    <option value="Safety Stock Replenishment">Safety Stock Replenishment</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.source === 'Sales Order Demand'">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Sales Order Reference</label>
                            <TextInput v-model="form.sales_order_ref" placeholder="e.g. SO-2026-9901 (Customer Acme Corp)" />
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3">2. Work Center & Warehouse Routing</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Primary Work Center</label>
                                <TextInput v-model="form.work_center" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Assigned Supervisor</label>
                                <TextInput v-model="form.assigned_supervisor" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Raw Material Source Warehouse</label>
                                <TextInput v-model="form.warehouse_source" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Finished Goods Target Warehouse</label>
                                <TextInput v-model="form.warehouse_target" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Scheduled Start Date</label>
                                <TextInput v-model="form.start_date" type="date" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Due / Completion Date</label>
                                <TextInput v-model="form.due_date" type="date" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Priority</label>
                                <select
                                    v-model="form.priority"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Summary -->
                <div class="space-y-6">
                    <div class="bg-indigo-900 text-white rounded-2xl p-6 shadow-md space-y-4">
                        <h4 class="text-xs uppercase font-bold tracking-wider text-indigo-300">Order Estimate Summary</h4>
                        <div class="space-y-3 pt-2">
                            <div class="flex justify-between text-xs text-indigo-200">
                                <span>Output Target:</span>
                                <strong class="text-white">{{ form.planned_qty }} {{ selectedBom?.unit }}</strong>
                            </div>
                            <div class="flex justify-between text-xs text-indigo-200">
                                <span>BOM Unit Cost:</span>
                                <strong class="text-white">${{ selectedBom?.estimated_cost.toFixed(2) }}</strong>
                            </div>
                            <div class="flex justify-between text-xs text-indigo-200">
                                <span>Routing:</span>
                                <span class="text-white text-right text-[11px] max-w-[140px] truncate">{{ selectedBom?.routing_name }}</span>
                            </div>
                            <div class="pt-3 border-t border-indigo-800 flex justify-between items-center">
                                <span class="text-xs font-semibold text-indigo-200">Est. Total Job Cost:</span>
                                <span class="text-xl font-black text-emerald-400">${{ estimatedTotalCost.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 mb-2">Automated Next Steps</h4>
                        <ul class="text-xs text-slate-500 dark:text-slate-400 space-y-2 list-disc list-inside">
                            <li>Explode BOM into required raw material reservations</li>
                            <li>Generate job traveler routing tickets for work centers</li>
                            <li>Notify warehouse to stage materials for assembly</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
