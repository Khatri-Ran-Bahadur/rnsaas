<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button, Modal } from '@/components';

export interface TableItem {
    id: number;
    name: string;
    capacity: number;
    status: 'available' | 'occupied' | 'reserved' | 'cleaning';
    current_order_id?: string;
    current_order_total?: number;
    server_name?: string;
    occupied_since?: string;
    floor_id: number;
}

export interface FloorItem {
    id: number;
    name: string;
    tables_count: number;
}

const props = defineProps<{
    floors: FloorItem[];
    tables: TableItem[];
    currentFloorId: number;
}>();

const selectedFloorId = ref(props.currentFloorId || props.floors[0]?.id || 1);
const selectedTable = ref<TableItem | null>(null);
const showActionModal = ref(false);

const filteredTables = () => {
    return props.tables.filter((t) => t.floor_id === selectedFloorId.value);
};

const getStatusBadge = (status: TableItem['status']) => {
    switch (status) {
        case 'available': return 'success';
        case 'occupied': return 'danger';
        case 'reserved': return 'warning';
        case 'cleaning': return 'secondary';
        default: return 'secondary';
    }
};

const handleTableClick = (table: TableItem) => {
    selectedTable.value = table;
    if (table.status === 'available') {
        router.visit(`/admin/pos?table=${encodeURIComponent(table.name)}`);
    } else {
        showActionModal.value = true;
    }
};

const handleOpenTableOrder = (table: TableItem) => {
    router.visit(`/admin/pos?table=${encodeURIComponent(table.name)}`);
};
</script>

<template>
    <Head title="Restaurant Floor Plan - POS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/pos" class="hover:text-emerald-600 transition">POS Register</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Floor Plan</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Restaurant Floor Plan & Dining Areas</span>
                    </h1>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin/pos/kds"
                        class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 transition"
                    >
                        Kitchen KDS
                    </Link>
                    <Link
                        href="/admin/pos"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2"
                    >
                        <span>Open POS Register</span>
                    </Link>
                </div>
            </div>

            <!-- Floor Selector Pills & Stats -->
            <Card class="p-4 space-y-3">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center space-x-2 overflow-x-auto pb-1">
                        <button
                            v-for="floor in floors"
                            :key="floor.id"
                            type="button"
                            @click="selectedFloorId = floor.id"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 border cursor-pointer',
                                selectedFloorId === floor.id
                                    ? 'bg-emerald-50 dark:bg-zinc-800 text-emerald-800 dark:text-emerald-400 border-emerald-300 dark:border-emerald-500/50 shadow-xs'
                                    : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                            ]"
                        >
                            <span>{{ floor.name }}</span>
                            <span class="text-[10px] bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded-full text-zinc-500">
                                {{ floor.tables_count }} tables
                            </span>
                        </button>
                    </div>

                    <!-- Status Legend -->
                    <div class="flex items-center space-x-3 text-xs text-zinc-500">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Available
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Occupied
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Reserved
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-zinc-400"></span> Cleaning
                        </span>
                    </div>
                </div>
            </Card>

            <!-- Table Layout Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div
                    v-for="table in filteredTables()"
                    :key="table.id"
                    @click="handleTableClick(table)"
                    class="p-4 rounded-2xl border transition-all duration-150 transform hover:-translate-y-0.5 cursor-pointer shadow-xs flex flex-col justify-between h-44"
                    :class="[
                        table.status === 'available'
                            ? 'bg-white dark:bg-zinc-900 border-emerald-300 dark:border-emerald-800/60 hover:border-emerald-500 hover:shadow-md'
                            : table.status === 'occupied'
                            ? 'bg-rose-50/50 dark:bg-rose-950/20 border-rose-300 dark:border-rose-900/60 hover:border-rose-500'
                            : table.status === 'reserved'
                            ? 'bg-amber-50/50 dark:bg-amber-950/20 border-amber-300 dark:border-amber-900/60 hover:border-amber-500'
                            : 'bg-zinc-50 dark:bg-zinc-900/50 border-zinc-300 dark:border-zinc-800'
                    ]"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ table.name }}</h3>
                            <span class="text-[10px] text-zinc-500 font-mono">{{ table.capacity }} Seats</span>
                        </div>
                        <Badge :variant="getStatusBadge(table.status)" class="uppercase text-[10px]">
                            {{ table.status }}
                        </Badge>
                    </div>

                    <!-- Occupied Info -->
                    <div v-if="table.status === 'occupied'" class="space-y-1 my-1">
                        <div class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400">
                            RM {{ table.current_order_total?.toFixed(2) || '0.00' }}
                        </div>
                        <div class="text-[10px] text-zinc-500 truncate">Server: {{ table.server_name }}</div>
                        <div class="text-[9px] text-zinc-400 font-mono">Since {{ table.occupied_since }}</div>
                    </div>

                    <div v-else-if="table.status === 'available'" class="text-center py-2 text-zinc-400">
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">+ Start Order</span>
                    </div>

                    <div v-else class="text-center py-2 text-xs text-zinc-400 italic">
                        {{ table.status === 'reserved' ? 'Reserved Table' : 'Staff Cleaning' }}
                    </div>

                    <!-- Footer Action Pill -->
                    <div class="pt-2 border-t border-zinc-200/80 dark:border-zinc-800 flex justify-between items-center text-[10px] text-zinc-500">
                        <span>#{{ table.id }}</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">Tap to Manage →</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Management Modal -->
        <Modal :show="showActionModal" @close="showActionModal = false">
            <div v-if="selectedTable" class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white">Manage {{ selectedTable.name }}</h3>
                        <p class="text-xs text-zinc-500 font-mono">Capacity: {{ selectedTable.capacity }} guests</p>
                    </div>
                    <Badge :variant="getStatusBadge(selectedTable.status)">{{ selectedTable.status }}</Badge>
                </div>

                <div v-if="selectedTable.status === 'occupied'" class="space-y-2 text-xs">
                    <div class="p-3 bg-slate-50 dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-1">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Active Order ID:</span>
                            <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200">{{ selectedTable.current_order_id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Current Total:</span>
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">RM {{ selectedTable.current_order_total?.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Assigned Server:</span>
                            <span class="text-zinc-800 dark:text-zinc-200">{{ selectedTable.server_name }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button
                        type="button"
                        @click="handleOpenTableOrder(selectedTable)"
                        class="p-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow text-center cursor-pointer"
                    >
                        Open Table in POS
                    </button>
                    <button
                        type="button"
                        @click="showActionModal = false"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-semibold text-center cursor-pointer"
                    >
                        Transfer / Move Table
                    </button>
                </div>
            </div>
        </Modal>
    </OrganizationLayout>
</template>
