<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button } from '@/components';

interface ProductionOrder {
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
    progress_percent: number;
}

const props = withDefaults(
    defineProps<{
        orders?: ProductionOrder[];
    }>(),
    {
        orders: () => [],
    }
);

const selectedStatus = ref('all');
const searchQuery = ref('');

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

const getPriorityBadgeVariant = (priority: string) => {
    switch (priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'normal': return 'secondary';
        case 'low': return 'secondary';
        default: return 'secondary';
    }
};
</script>

<template>
    <Head title="Manufacturing Orders" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Production Orders (MO / WO)</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Track shop floor work orders, material release, WIP stages, and job completions</p>
                </div>
                <div class="flex items-center space-x-2.5">
                    <Link
                        href="/admin/mrp/work-orders/create"
                        class="inline-flex items-center px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm shadow-lg shadow-indigo-900/30 transition-all"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Production Order
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-2 overflow-x-auto pb-1 sm:pb-0">
                    <button
                        @click="selectedStatus = 'all'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                        :class="selectedStatus === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 hover:bg-slate-200'"
                    >
                        All ({{ orders.length }})
                    </button>
                    <button
                        @click="selectedStatus = 'in_progress'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                        :class="selectedStatus === 'in_progress' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 hover:bg-slate-200'"
                    >
                        In Progress
                    </button>
                    <button
                        @click="selectedStatus = 'released'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                        :class="selectedStatus === 'released' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 hover:bg-slate-200'"
                    >
                        Released
                    </button>
                    <button
                        @click="selectedStatus = 'planned'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                        :class="selectedStatus === 'planned' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 hover:bg-slate-200'"
                    >
                        Planned
                    </button>
                    <button
                        @click="selectedStatus = 'completed'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                        :class="selectedStatus === 'completed' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 hover:bg-slate-200'"
                    >
                        Completed
                    </button>
                </div>

                <div class="w-full sm:w-72">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search MO #, Product, SKU..."
                        class="w-full px-3.5 py-1.5 rounded-xl text-xs border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">MO / WO #</th>
                                <th class="px-4 py-3.5">Product & BOM</th>
                                <th class="px-4 py-3.5">Progress & Qty</th>
                                <th class="px-4 py-3.5">Work Center</th>
                                <th class="px-4 py-3.5">Due Schedule</th>
                                <th class="px-4 py-3.5 text-center">Priority</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="order in orders"
                                :key="order.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="font-black text-slate-900 dark:text-slate-100">{{ order.order_number }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">Origin: {{ order.source }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ order.product_name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ order.sku }} • {{ order.bom_number }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                            <div
                                                class="bg-indigo-600 h-full rounded-full transition-all"
                                                :style="{ width: `${order.progress_percent}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ order.progress_percent }}%</span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ order.completed_qty }} / {{ order.planned_qty }} {{ order.unit }}
                                        <span v-if="order.scrapped_qty > 0" class="text-red-500 ml-1">({{ order.scrapped_qty }} scrapped)</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ order.work_center }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-xs font-semibold text-slate-900 dark:text-slate-100">{{ order.due_date }}</div>
                                    <div class="text-[11px] text-slate-400">Start: {{ order.start_date }}</div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Badge :variant="getPriorityBadgeVariant(order.priority)" size="sm">
                                        {{ order.priority.toUpperCase() }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Badge :variant="getStatusBadgeVariant(order.status)" size="sm">
                                        {{ order.status.replace('_', ' ').toUpperCase() }}
                                    </Badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Link
                                        :href="`/admin/mrp/work-orders/${order.id}`"
                                        class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-600 dark:text-indigo-400 text-xs font-semibold transition-colors inline-block"
                                    >
                                        Execute Order &rarr;
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
