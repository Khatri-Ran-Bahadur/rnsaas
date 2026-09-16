<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button, Card } from '@/components';
import BomHierarchyTree from '../../Components/BomHierarchyTree.vue';

interface Costing {
    material_cost: number;
    labor_cost: number;
    machine_cost: number;
    overhead_cost: number;
    scrap_cost: number;
    total_cost: number;
    cost_per_unit: number;
}

interface VersionItem {
    version: string;
    effective_from: string;
    status: string;
    cost: number;
    author: string;
}

interface BomDetails {
    id: number;
    bom_number: string;
    product_name?: string;
    item_name?: string;
    sku?: string;
    item_sku?: string;
    bom_type: string;
    version: string;
    status: string;
    is_active: boolean;
    quantity: number;
    unit: string;
    effective_from: string;
    effective_to?: string | null;
    created_by: string;
    approved_by?: string;
    description?: string;
    costing?: Costing;
    versions?: VersionItem[];
    tree?: any;
}

const props = defineProps<{
    bom: BomDetails;
}>();

const formatCurrency = (val?: number) => {
    return '$' + Number(val || 0).toFixed(2);
};
</script>

<template>
    <Head :title="`BOM: ${bom.bom_number}`" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <Link
                            href="/admin/mrp/bom"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition-colors"
                        >
                            ← Back to BOM List
                        </Link>
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span class="text-xs font-mono text-slate-500">{{ bom.bom_number }}</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                        {{ bom.product_name || bom.item_name }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        SKU: <span class="font-mono">{{ bom.sku || bom.item_sku }}</span> • Type: {{ bom.bom_type }} • Output: {{ bom.quantity }} {{ bom.unit }}
                    </p>
                </div>
                <div class="flex items-center space-x-2.5">
                    <Badge variant="success" size="lg">{{ bom.status }}</Badge>
                    <Link
                        :href="`/admin/mrp/bom/${bom.id}/edit`"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-colors shadow-sm"
                    >
                        Edit BOM
                    </Link>
                </div>
            </div>

            <!-- Costing Summary Cards -->
            <div v-if="bom.costing" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Unit Cost</span>
                    <p class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ formatCurrency(bom.costing.cost_per_unit) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Materials</span>
                    <p class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatCurrency(bom.costing.material_cost) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Direct Labor</span>
                    <p class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatCurrency(bom.costing.labor_cost) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Machine Run</span>
                    <p class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatCurrency(bom.costing.machine_cost) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Overhead</span>
                    <p class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ formatCurrency(bom.costing.overhead_cost) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Scrap / Waste</span>
                    <p class="text-xl font-bold text-rose-500 mt-1">{{ formatCurrency(bom.costing.scrap_cost) }}</p>
                </div>
            </div>

            <!-- Main Details & Tree -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Hierarchy Tree -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-base font-black text-slate-900 dark:text-white mb-4">Multi-Level Component Tree</h2>
                    <div v-if="bom.tree" class="overflow-x-auto">
                        <BomHierarchyTree :node="bom.tree" />
                    </div>
                    <div v-else class="text-center py-10 text-slate-500 text-sm">
                        No component tree defined.
                    </div>
                </div>

                <!-- Right: Metadata & Versions -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Specification Details</h3>
                        <dl class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <div class="py-2 flex justify-between">
                                <dt class="text-slate-500">BOM Version</dt>
                                <dd class="font-bold text-slate-900 dark:text-white">{{ bom.version }}</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-slate-500">Effective Date</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ bom.effective_from }}</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-slate-500">Created By</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ bom.created_by }}</dd>
                            </div>
                            <div v-if="bom.approved_by" class="py-2 flex justify-between">
                                <dt class="text-slate-500">Approved By</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ bom.approved_by }}</dd>
                            </div>
                        </dl>
                        <p v-if="bom.description" class="text-xs text-slate-600 dark:text-slate-400 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            {{ bom.description }}
                        </p>
                    </div>

                    <div v-if="bom.versions && bom.versions.length" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Version History</h3>
                        <div class="space-y-2.5">
                            <div
                                v-for="v in bom.versions"
                                :key="v.version"
                                class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center justify-between text-xs"
                            >
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-slate-900 dark:text-slate-100">{{ v.version }}</span>
                                        <Badge :variant="v.status === 'Active' ? 'success' : 'secondary'" size="sm">{{ v.status }}</Badge>
                                    </div>
                                    <span class="text-slate-400 text-[11px]">{{ v.effective_from }} • by {{ v.author }}</span>
                                </div>
                                <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ formatCurrency(v.cost) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
