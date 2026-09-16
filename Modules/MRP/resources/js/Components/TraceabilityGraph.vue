<script setup lang="ts">
import { ref } from 'vue';
import { Badge } from '@/components';

interface TreeNode {
    type: string;
    label: string;
    sku?: string;
    qty?: string;
    date?: string;
    status?: string;
    operator?: string;
    inspector?: string;
    supplier?: string;
    consumed_qty?: string;
    location?: string;
    journal?: string;
    qc_status?: string;
    children?: TreeNode[];
}

const props = defineProps<{
    node: TreeNode;
    depth?: number;
}>();

const isExpanded = ref(true);

const toggle = () => {
    isExpanded.value = !isExpanded.value;
};

const getNodeTypeBadge = (type: string) => {
    switch (type.toLowerCase()) {
        case 'finished good': return 'primary';
        case 'production run': return 'info';
        case 'work order': return 'secondary';
        case 'bom version': return 'warning';
        case 'raw material batch': return 'danger';
        case 'quality inspection': return 'success';
        case 'stock movement & ledger': return 'neutral';
        default: return 'secondary';
    }
};
</script>

<template>
    <div class="relative text-sm select-none">
        <div
            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 my-2 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition-all"
            :style="{ marginLeft: `${(depth || 0) * 28}px` }"
        >
            <div class="flex items-start sm:items-center space-x-3.5">
                <button
                    v-if="node.children && node.children.length"
                    type="button"
                    @click="toggle"
                    class="mt-1 sm:mt-0 w-6 h-6 flex-shrink-0 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-colors"
                >
                    <svg
                        class="w-3.5 h-3.5 transform transition-transform"
                        :class="{ 'rotate-90': isExpanded }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <div v-else class="mt-1 sm:mt-0 w-6 flex-shrink-0 flex items-center justify-center text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :variant="getNodeTypeBadge(node.type)" size="sm">
                            {{ node.type }}
                        </Badge>
                        <span class="font-bold text-slate-900 dark:text-slate-100">{{ node.label }}</span>
                        <span v-if="node.sku" class="text-xs px-2 py-0.5 rounded font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            {{ node.sku }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <span v-if="node.qty">Output: <strong class="text-slate-700 dark:text-slate-200">{{ node.qty }}</strong></span>
                        <span v-if="node.consumed_qty">Consumed: <strong class="text-slate-700 dark:text-slate-200">{{ node.consumed_qty }}</strong></span>
                        <span v-if="node.supplier">Supplier: <strong class="text-slate-700 dark:text-slate-200">{{ node.supplier }}</strong></span>
                        <span v-if="node.qc_status" class="text-emerald-600 dark:text-emerald-400 font-semibold">QC: {{ node.qc_status }}</span>
                        <span v-if="node.operator">Operator: {{ node.operator }}</span>
                        <span v-if="node.inspector">Inspector: {{ node.inspector }}</span>
                        <span v-if="node.location">Location: {{ node.location }}</span>
                        <span v-if="node.journal" class="text-indigo-600 dark:text-indigo-400 font-mono">{{ node.journal }}</span>
                    </div>
                </div>
            </div>

            <div v-if="node.status || node.date" class="mt-2 sm:mt-0 text-right text-xs">
                <span v-if="node.status" class="inline-block font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ node.status }}
                </span>
                <div v-if="node.date" class="text-slate-400 dark:text-slate-500 mt-0.5">{{ node.date }}</div>
            </div>
        </div>

        <div v-if="isExpanded && node.children && node.children.length" class="border-l-2 border-slate-200 dark:border-slate-800 ml-5 pl-2">
            <TraceabilityGraph
                v-for="(child, idx) in node.children"
                :key="idx"
                :node="child"
                :depth="(depth || 0) + 1"
            />
        </div>
    </div>
</template>
