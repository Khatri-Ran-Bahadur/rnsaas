<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, TextInput, Select, Badge } from '@/components';

interface ComponentRow {
    id: number;
    material_id: string;
    material_name: string;
    sku: string;
    quantity: number;
    unit: string;
    unit_cost: number;
    scrap_percent: number;
    is_subassembly: boolean;
    substitute_material: string;
    routing_step: string;
}

interface RoutingStep {
    step_number: number;
    operation_name: string;
    work_center: string;
    setup_time_mins: number;
    run_time_mins: number;
    cost_per_hour: number;
}

interface ByProductRow {
    id: number;
    product_name: string;
    quantity: number;
    unit: string;
    cost_allocation_percent: number;
}

const props = defineProps<{
    id?: number | string;
    is_editing?: boolean;
}>();

const form = useForm({
    product_name: 'Industrial Valve Body Complete',
    sku: 'FG-VALVE-01',
    bom_number: 'BOM-ENG-2026-004',
    version: 'v2.0',
    output_qty: 1,
    unit: 'Units',
    is_default: true,
    routing_name: 'Main Machining & Assembly Line A',
    notes: 'Engineering release for heavy-duty standard valve assembly',
    components: [
        { id: 1, material_id: '1', material_name: 'Cast Iron Casting Block 10kg', sku: 'RM-IRON-01', quantity: 1, unit: 'Pcs', unit_cost: 45.00, scrap_percent: 2.5, is_subassembly: false, substitute_material: 'Cast Iron Grade B', routing_step: 'Step 10: CNC Milling' },
        { id: 2, material_id: '2', material_name: 'Brass Stem Core Spindle', sku: 'RM-BRASS-04', quantity: 1, unit: 'Pcs', unit_cost: 18.50, scrap_percent: 1.0, is_subassembly: false, substitute_material: '', routing_step: 'Step 20: Lathe Turning' },
        { id: 3, material_id: '3', material_name: 'Gasket Sealing Subassembly Kit', sku: 'SA-GSK-09', quantity: 2, unit: 'Sets', unit_cost: 6.20, scrap_percent: 0.0, is_subassembly: true, substitute_material: '', routing_step: 'Step 30: Final Assembly' },
        { id: 4, material_id: '4', material_name: 'High-Tensile Stainless Bolts M10', sku: 'HDW-BLT-10', quantity: 6, unit: 'Pcs', unit_cost: 0.85, scrap_percent: 5.0, is_subassembly: false, substitute_material: 'Zinc Plated Bolts M10', routing_step: 'Step 30: Final Assembly' },
    ] as ComponentRow[],
    routing_steps: [
        { step_number: 10, operation_name: 'CNC Milling & Facing', work_center: 'WC-01 (CNC Milling Center)', setup_time_mins: 20, run_time_mins: 15, cost_per_hour: 65 },
        { step_number: 20, operation_name: 'Lathe Precision Turning', work_center: 'WC-02 (Lathe Work Center)', setup_time_mins: 10, run_time_mins: 12, cost_per_hour: 50 },
        { step_number: 30, operation_name: 'Manual Assembly & Torque', work_center: 'WC-04 (Assembly Line 1)', setup_time_mins: 5, run_time_mins: 20, cost_per_hour: 35 },
        { step_number: 40, operation_name: 'Hydrostatic Pressure Testing', work_center: 'WC-05 (QC Testing Bay)', setup_time_mins: 5, run_time_mins: 10, cost_per_hour: 45 },
    ] as RoutingStep[],
    by_products: [
        { id: 1, product_name: 'Cast Iron Metal Scrap Turnings', quantity: 0.8, unit: 'kg', cost_allocation_percent: 2.0 },
    ] as ByProductRow[],
});

const activeTab = ref<'components' | 'routing' | 'byproducts' | 'costing'>('components');

const addComponent = () => {
    form.components.push({
        id: Date.now(),
        material_id: '',
        material_name: '',
        sku: '',
        quantity: 1,
        unit: 'Pcs',
        unit_cost: 0,
        scrap_percent: 0,
        is_subassembly: false,
        substitute_material: '',
        routing_step: 'Step 10: General',
    });
};

const removeComponent = (index: number) => {
    form.components.splice(index, 1);
};

const addRoutingStep = () => {
    const nextStep = (form.routing_steps.length + 1) * 10;
    form.routing_steps.push({
        step_number: nextStep,
        operation_name: '',
        work_center: 'WC-01 (General)',
        setup_time_mins: 10,
        run_time_mins: 15,
        cost_per_hour: 45,
    });
};

const removeRoutingStep = (index: number) => {
    form.routing_steps.splice(index, 1);
};

const addByProduct = () => {
    form.by_products.push({
        id: Date.now(),
        product_name: '',
        quantity: 1,
        unit: 'kg',
        cost_allocation_percent: 0,
    });
};

const removeByProduct = (index: number) => {
    form.by_products.splice(index, 1);
};

const totalMaterialCost = computed(() => {
    return form.components.reduce((sum, item) => {
        const effectiveQty = item.quantity * (1 + (item.scrap_percent || 0) / 100);
        return sum + (effectiveQty * (item.unit_cost || 0));
    }, 0);
});

const totalLaborMachineCost = computed(() => {
    return form.routing_steps.reduce((sum, step) => {
        const totalHours = (step.setup_time_mins / 60) + (step.run_time_mins / 60);
        return sum + (totalHours * step.cost_per_hour);
    }, 0);
});

const totalBomEstimatedCost = computed(() => {
    return totalMaterialCost.value + totalLaborMachineCost.value;
});

const submit = () => {
    form.post('/admin/mrp/bom');
};
</script>

<template>
    <Head title="Create Bill of Materials" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link href="/admin/mrp/bom" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 hover:bg-slate-200 transition-colors">
                        &larr;
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Create Bill of Materials (BOM)</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Engineering structure, component recipes, operations routing, and cost model</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="secondary" href="/admin/mrp/bom">Cancel</Button>
                    <Button variant="primary" @click="submit" :loading="form.processing">Save & Release BOM</Button>
                </div>
            </div>

            <!-- Master Info Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Finished Item & Recipe Header</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Finished Product Name</label>
                        <TextInput v-model="form.product_name" placeholder="e.g. Industrial Valve Assembly" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Product SKU</label>
                        <TextInput v-model="form.sku" placeholder="e.g. FG-VALVE-01" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">BOM Code / Version</label>
                        <div class="flex space-x-2">
                            <TextInput v-model="form.bom_number" class="w-2/3" />
                            <TextInput v-model="form.version" class="w-1/3" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Output Batch Size</label>
                        <div class="flex space-x-2">
                            <TextInput v-model="form.output_qty" type="number" class="w-1/2" />
                            <TextInput v-model="form.unit" class="w-1/2" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Routing Line Preset</label>
                        <TextInput v-model="form.routing_name" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Engineering Notes</label>
                        <TextInput v-model="form.notes" />
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200 dark:border-slate-800 space-x-4">
                <button
                    type="button"
                    @click="activeTab = 'components'"
                    class="pb-3 text-sm font-bold border-b-2 transition-all flex items-center space-x-2"
                    :class="activeTab === 'components' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    <span>1. Components & Scrap Rate</span>
                    <Badge variant="secondary" size="sm">{{ form.components.length }}</Badge>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'routing'"
                    class="pb-3 text-sm font-bold border-b-2 transition-all flex items-center space-x-2"
                    :class="activeTab === 'routing' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    <span>2. Routing Operations & Work Centers</span>
                    <Badge variant="secondary" size="sm">{{ form.routing_steps.length }}</Badge>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'byproducts'"
                    class="pb-3 text-sm font-bold border-b-2 transition-all flex items-center space-x-2"
                    :class="activeTab === 'byproducts' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    <span>3. By-Products & Co-Products</span>
                    <Badge variant="secondary" size="sm">{{ form.by_products.length }}</Badge>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'costing'"
                    class="pb-3 text-sm font-bold border-b-2 transition-all flex items-center space-x-2"
                    :class="activeTab === 'costing' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700'"
                >
                    <span>4. Live Cost Simulator</span>
                </button>
            </div>

            <!-- Tab 1: Components -->
            <div v-if="activeTab === 'components'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Raw Materials & Subassemblies</h3>
                        <p class="text-xs text-slate-500">Specify material requirements, scrap allowances, and alternate substitutes</p>
                    </div>
                    <Button variant="secondary" size="sm" @click="addComponent">+ Add Component</Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Component / Material</th>
                                <th class="px-3 py-3">SKU</th>
                                <th class="px-3 py-3 w-28">Quantity</th>
                                <th class="px-3 py-3 w-20">Unit</th>
                                <th class="px-3 py-3 w-28">Cost ($)</th>
                                <th class="px-3 py-3 w-24">Scrap %</th>
                                <th class="px-3 py-3 text-center">Subassembly?</th>
                                <th class="px-3 py-3">Alternate Substitute</th>
                                <th class="px-2 py-3 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(comp, index) in form.components" :key="comp.id">
                                <td class="px-4 py-2.5">
                                    <TextInput v-model="comp.material_name" placeholder="Component name" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="comp.sku" placeholder="SKU" class="font-mono text-xs" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="comp.quantity" type="number" step="0.01" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="comp.unit" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="comp.unit_cost" type="number" step="0.01" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="comp.scrap_percent" type="number" step="0.1" />
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <input type="checkbox" v-model="comp.is_subassembly" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="comp.substitute_material" placeholder="Optional backup SKU" />
                                </td>
                                <td class="px-2 py-2.5 text-center">
                                    <button type="button" @click="removeComponent(index)" class="text-red-500 hover:text-red-700 p-1">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-500">Material Cost Subtotal (incl scrap):</span>
                    <span class="font-bold text-base text-slate-900 dark:text-white">${{ totalMaterialCost.toFixed(2) }}</span>
                </div>
            </div>

            <!-- Tab 2: Routing Operations -->
            <div v-if="activeTab === 'routing'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Shop Floor Routing & Work Centers</h3>
                        <p class="text-xs text-slate-500">Define operation sequence, setup time, run duration, and hourly center cost</p>
                    </div>
                    <Button variant="secondary" size="sm" @click="addRoutingStep">+ Add Operation Step</Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="px-3 py-3 w-16">Step #</th>
                                <th class="px-4 py-3">Operation Description</th>
                                <th class="px-4 py-3">Assigned Work Center</th>
                                <th class="px-3 py-3 w-28">Setup (Mins)</th>
                                <th class="px-3 py-3 w-28">Run (Mins)</th>
                                <th class="px-3 py-3 w-28">Rate ($/hr)</th>
                                <th class="px-2 py-3 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(step, index) in form.routing_steps" :key="index">
                                <td class="px-3 py-2.5 font-bold font-mono text-xs text-slate-600">
                                    {{ step.step_number }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <TextInput v-model="step.operation_name" placeholder="e.g. CNC Milling" />
                                </td>
                                <td class="px-4 py-2.5">
                                    <TextInput v-model="step.work_center" placeholder="Work Center Name" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="step.setup_time_mins" type="number" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="step.run_time_mins" type="number" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="step.cost_per_hour" type="number" />
                                </td>
                                <td class="px-2 py-2.5 text-center">
                                    <button type="button" @click="removeRoutingStep(index)" class="text-red-500 hover:text-red-700 p-1">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-500">Labor & Machine Cost Subtotal:</span>
                    <span class="font-bold text-base text-slate-900 dark:text-white">${{ totalLaborMachineCost.toFixed(2) }}</span>
                </div>
            </div>

            <!-- Tab 3: By-Products -->
            <div v-if="activeTab === 'byproducts'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">By-Products & Co-Products Yield</h3>
                        <p class="text-xs text-slate-500">Secondary sellable or recycled goods generated during production run</p>
                    </div>
                    <Button variant="secondary" size="sm" @click="addByProduct">+ Add By-Product</Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">By-Product Name</th>
                                <th class="px-3 py-3 w-32">Expected Yield</th>
                                <th class="px-3 py-3 w-28">Unit</th>
                                <th class="px-3 py-3 w-40">Cost Allocation %</th>
                                <th class="px-2 py-3 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(bp, index) in form.by_products" :key="bp.id">
                                <td class="px-4 py-2.5">
                                    <TextInput v-model="bp.product_name" placeholder="e.g. Metal Turnings / Scrap" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="bp.quantity" type="number" step="0.1" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="bp.unit" />
                                </td>
                                <td class="px-3 py-2.5">
                                    <TextInput v-model="bp.cost_allocation_percent" type="number" step="0.1" />
                                </td>
                                <td class="px-2 py-2.5 text-center">
                                    <button type="button" @click="removeByProduct(index)" class="text-red-500 hover:text-red-700 p-1">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 4: Live Cost Simulator -->
            <div v-if="activeTab === 'costing'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">BOM Standard Cost Simulation</h3>
                    <p class="text-xs text-slate-500">Live aggregated cost buildup per 1 {{ form.unit }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700">
                        <span class="text-xs font-semibold text-slate-500 block">Total Material Cost</span>
                        <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">${{ totalMaterialCost.toFixed(2) }}</span>
                        <span class="text-[11px] text-slate-400 mt-1 block">Includes scrap allowances</span>
                    </div>

                    <div class="p-5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700">
                        <span class="text-xs font-semibold text-slate-500 block">Total Labor & Machine Run</span>
                        <span class="text-2xl font-black text-slate-900 dark:text-white mt-1 block">${{ totalLaborMachineCost.toFixed(2) }}</span>
                        <span class="text-[11px] text-slate-400 mt-1 block">{{ form.routing_steps.length }} Operation steps</span>
                    </div>

                    <div class="p-5 bg-indigo-50 dark:bg-indigo-950/40 rounded-2xl border border-indigo-200 dark:border-indigo-800">
                        <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 block">Total Unit Standard Cost</span>
                        <span class="text-3xl font-black text-indigo-700 dark:text-indigo-300 mt-1 block">${{ totalBomEstimatedCost.toFixed(2) }}</span>
                        <span class="text-[11px] text-indigo-500 dark:text-indigo-400 mt-1 block">Base finished good valuation</span>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
