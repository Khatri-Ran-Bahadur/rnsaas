<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button, SearchInput, Modal } from '@/components';
import BomHierarchyTree from '../../Components/BomHierarchyTree.vue';

interface BomItem {
    id: number;
    bom_number: string;
    product_name: string;
    sku: string;
    version: string;
    output_qty: number;
    unit: string;
    total_components_count: number;
    status: string;
    is_default: boolean;
    estimated_cost: number;
    last_updated: string;
    routing_name: string;
    hierarchy?: any;
}

const props = withDefaults(defineProps<{
    boms?: BomItem[];
}>(), {
    boms: () => [],
});

const searchQuery = ref('');
const selectedBom = ref<BomItem | null>(null);
const showTreeModal = ref(false);

const filteredBoms = computed(() => {
    const list = props.boms || [];
    if (!searchQuery.value) return list;
    const q = searchQuery.value.toLowerCase();
    return list.filter(b =>
        (b.product_name && b.product_name.toLowerCase().includes(q)) ||
        (b.sku && b.sku.toLowerCase().includes(q)) ||
        (b.bom_number && b.bom_number.toLowerCase().includes(q))
    );
});

const formatCost = (cost?: number) => {
    return Number(cost || 0).toFixed(2);
};

const openTreeModal = (bom: BomItem) => {
    selectedBom.value = bom;
    showTreeModal.value = true;
};

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'active': return 'success';
        case 'draft': return 'secondary';
        case 'deprecated': return 'danger';
        default: return 'secondary';
    }
};
</script>

<template>
    <Head title="Bill of Materials (BOM)" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Bill of Materials (BOM)</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage multi-level engineering recipes, assembly components, by-products, and costing</p>
                </div>
                <div class="flex items-center space-x-2.5">
                    <Link
                        href="/admin/mrp/bom/create"
                        class="inline-flex items-center px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-900/30 transition-all"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create New BOM
                    </Link>
                </div>
            </div>

            <!-- List of BOMs Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                    <div class="w-full sm:w-80">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search BOM by SKU, product name..."
                            class="w-full px-4 py-2 rounded-xl text-sm border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                    <div class="flex items-center space-x-2 text-xs text-slate-500">
                        <span>Total: <strong>{{ filteredBoms.length }} BOM Recipes</strong></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">BOM # / Version</th>
                                <th class="px-4 py-3.5">Finished Product</th>
                                <th class="px-4 py-3.5">Output Base</th>
                                <th class="px-4 py-3.5">Routing Line</th>
                                <th class="px-4 py-3.5 text-right">Estimated Cost</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="bom in filteredBoms"
                                :key="bom.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-slate-900 dark:text-slate-100">{{ bom.bom_number }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            {{ bom.version }}
                                        </span>
                                        <Badge v-if="bom.is_default" variant="primary" size="sm">Default</Badge>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">{{ bom.total_components_count ?? 0 }} Components • Updated {{ bom.last_updated || 'Recent' }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ bom.product_name }}</div>
                                    <div class="text-xs font-mono text-slate-500">{{ bom.sku }}</div>
                                </td>
                                <td class="px-4 py-4 text-slate-700 dark:text-slate-300 font-medium">
                                    {{ bom.output_qty }} {{ bom.unit }}
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-600 dark:text-slate-400">
                                    {{ bom.routing_name || 'Standard Line' }}
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-slate-900 dark:text-slate-100">
                                    ${{ formatCost(bom.estimated_cost) }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Badge :variant="getStatusBadgeVariant(bom.status)" size="sm">
                                        {{ bom.status }}
                                    </Badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button
                                            type="button"
                                            @click="openTreeModal(bom)"
                                            class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors"
                                        >
                                            Hierarchy Tree
                                        </button>
                                        <Link
                                            :href="`/admin/mrp/bom/${bom.id}/edit`"
                                            class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-600 dark:text-indigo-400 text-xs font-semibold transition-colors"
                                        >
                                            Edit
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredBoms.length === 0">
                                <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                    No Bill of Materials found matching your query.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Multi-Level Tree Modal -->
            <Modal :show="showTreeModal" @close="showTreeModal = false" max-width="3xl">
                <div v-if="selectedBom" class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Multi-Level BOM Hierarchy</h3>
                            <p class="text-xs text-slate-500">{{ selectedBom.product_name }} ({{ selectedBom.sku }}) — {{ selectedBom.bom_number }} {{ selectedBom.version }}</p>
                        </div>
                        <button @click="showTreeModal = false" class="text-slate-400 hover:text-slate-600">
                            ✕
                        </button>
                    </div>

                    <div v-if="selectedBom.hierarchy" class="max-h-[60vh] overflow-y-auto pr-1">
                        <BomHierarchyTree :node="selectedBom.hierarchy" />
                    </div>
                    <div v-else class="p-6 text-center text-sm text-slate-500">
                        No sub-level hierarchy defined for this BOM item.
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-slate-200 dark:border-slate-800">
                        <div class="text-xs text-slate-500">
                            Total Est. Base Cost: <strong class="text-slate-900 dark:text-slate-100">${{ formatCost(selectedBom.estimated_cost) }}</strong>
                        </div>
                        <Button variant="secondary" @click="showTreeModal = false">Close</Button>
                    </div>
                </div>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
