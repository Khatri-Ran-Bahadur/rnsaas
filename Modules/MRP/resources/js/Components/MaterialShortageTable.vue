<script setup lang="ts">
import { Badge, Button } from '@/components';

interface MaterialReq {
    id: number;
    material_name: string;
    sku: string;
    required_qty: number;
    available_qty: number;
    reserved_qty: number;
    incoming_qty: number;
    net_requirement: number;
    unit: string;
    warehouse: string;
    lead_time_days: number;
    status: string;
    suggested_action: string;
}

const props = defineProps<{
    materials: MaterialReq[];
}>();

const emit = defineEmits<{
    (e: 'createPo', material: MaterialReq): void;
    (e: 'createWo', material: MaterialReq): void;
}>();

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'available': return 'success';
        case 'shortage': return 'danger';
        case 'critical shortage': return 'danger';
        case 'partial shortage': return 'warning';
        default: return 'secondary';
    }
};
</script>

<template>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-5 py-3.5">Material / SKU</th>
                    <th class="px-4 py-3.5 text-right">Required</th>
                    <th class="px-4 py-3.5 text-right">Available</th>
                    <th class="px-4 py-3.5 text-right">Reserved</th>
                    <th class="px-4 py-3.5 text-right">Incoming</th>
                    <th class="px-4 py-3.5 text-right">Net Shortage</th>
                    <th class="px-4 py-3.5">Warehouse</th>
                    <th class="px-4 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-right">Suggested Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr
                    v-for="item in materials"
                    :key="item.id"
                    class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
                >
                    <td class="px-5 py-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ item.material_name }}</div>
                        <div class="text-xs font-mono text-slate-500 mt-0.5">{{ item.sku }} • {{ item.lead_time_days }}d Lead Time</div>
                    </td>
                    <td class="px-4 py-4 text-right font-medium text-slate-900 dark:text-slate-100">
                        {{ item.required_qty.toFixed(2) }} <span class="text-xs text-slate-400">{{ item.unit }}</span>
                    </td>
                    <td class="px-4 py-4 text-right text-slate-700 dark:text-slate-300">
                        {{ item.available_qty.toFixed(2) }}
                    </td>
                    <td class="px-4 py-4 text-right text-amber-600 dark:text-amber-400">
                        {{ item.reserved_qty.toFixed(2) }}
                    </td>
                    <td class="px-4 py-4 text-right text-indigo-600 dark:text-indigo-400">
                        {{ item.incoming_qty.toFixed(2) }}
                    </td>
                    <td class="px-4 py-4 text-right font-bold" :class="item.net_requirement > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                        {{ item.net_requirement.toFixed(2) }} <span class="text-xs">{{ item.unit }}</span>
                    </td>
                    <td class="px-4 py-4 text-xs text-slate-600 dark:text-slate-400">
                        {{ item.warehouse }}
                    </td>
                    <td class="px-4 py-4 text-center">
                        <Badge :variant="getStatusBadgeVariant(item.status)" size="sm">
                            {{ item.status }}
                        </Badge>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300 mr-1 hidden sm:inline">
                                {{ item.suggested_action }}
                            </span>
                            <Button
                                v-if="item.net_requirement > 0 && item.suggested_action.includes('Purchase')"
                                size="sm"
                                variant="primary"
                                @click="emit('createPo', item)"
                            >
                                Create PO
                            </Button>
                            <Button
                                v-else-if="item.net_requirement > 0 && item.suggested_action.includes('Work Order')"
                                size="sm"
                                variant="secondary"
                                @click="emit('createWo', item)"
                            >
                                Plan WO
                            </Button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
