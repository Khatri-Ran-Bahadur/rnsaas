<script setup lang="ts">
import { ref } from 'vue';
import { Badge } from '@/components';

interface BomNode {
    id: string;
    name: string;
    sku: string;
    quantity: number;
    unit: string;
    unit_cost: number;
    scrap_percent?: number;
    warehouse?: string;
    is_subassembly?: boolean;
    optional?: boolean;
    substitute?: string;
    children?: BomNode[];
}

const props = defineProps<{
    node: BomNode;
    depth?: number;
}>();

const isExpanded = ref(true);

const toggle = () => {
    isExpanded.value = !isExpanded.value;
};
</script>

<template>
    <div class="relative font-sans text-sm select-none">
        <div
            class="flex items-center justify-between p-3.5 my-1.5 rounded-xl border transition-all duration-150"
            :class="[
                node.is_subassembly
                    ? 'bg-slate-50/90 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 shadow-sm'
                    : 'bg-white dark:bg-slate-900 border-slate-150 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
            ]"
            :style="{ marginLeft: `${(depth || 0) * 24}px` }"
        >
            <div class="flex items-center space-x-3">
                <button
                    v-if="node.children && node.children.length"
                    type="button"
                    @click="toggle"
                    class="w-6 h-6 flex items-center justify-center rounded-lg bg-slate-200/70 dark:bg-slate-700/80 hover:bg-slate-300 text-slate-700 dark:text-slate-200 transition-colors"
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
                <div v-else class="w-6 flex items-center justify-center text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                </div>

                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ node.name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            {{ node.sku }}
                        </span>
                        <Badge v-if="node.is_subassembly" variant="primary" size="sm">Subassembly</Badge>
                        <Badge v-if="node.optional" variant="warning" size="sm">Optional</Badge>
                    </div>
                    <div class="flex items-center space-x-4 mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <span>Qty: <strong class="text-slate-700 dark:text-slate-200">{{ node.quantity }} {{ node.unit }}</strong></span>
                        <span v-if="node.scrap_percent !== undefined && node.scrap_percent > 0">
                            Scrap: <span class="text-amber-600 dark:text-amber-400 font-medium">+{{ node.scrap_percent }}%</span>
                        </span>
                        <span v-if="node.warehouse">Location: {{ node.warehouse }}</span>
                        <span v-if="node.substitute" class="text-indigo-600 dark:text-indigo-400">
                            Alt: {{ node.substitute }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <div class="font-semibold text-slate-900 dark:text-slate-100">
                    ${{ ((node.unit_cost || 0) * (node.quantity || 1)).toFixed(2) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    ${{ (node.unit_cost || 0).toFixed(2) }} / {{ node.unit }}
                </div>
            </div>
        </div>

        <div v-if="isExpanded && node.children && node.children.length" class="border-l-2 border-slate-200 dark:border-slate-800 ml-4 pl-1">
            <BomHierarchyTree
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :depth="(depth || 0) + 1"
            />
        </div>
    </div>
</template>
