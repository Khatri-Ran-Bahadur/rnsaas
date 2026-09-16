<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button, Modal } from '@/components';

interface WorkCenter {
    id: number;
    code: string;
    name: string;
    type?: string;
    capacity_hours_per_day: number;
    hourly_rate?: number;
    hourly_cost?: number;
    efficiency_percentage?: number;
    oee_percentage?: number;
    current_load_percentage?: number;
    utilization_percent?: number;
    status: string;
    active_job_order?: string;
    branch?: string;
    machines_count?: number;
}

interface Machine {
    id: number;
    code: string;
    name: string;
    work_center: string;
    status: string;
    health_percent: number;
    last_maintenance: string;
    next_maintenance: string;
}

const props = withDefaults(defineProps<{
    workCenters?: WorkCenter[];
    machines?: Machine[];
}>(), {
    workCenters: () => [],
    machines: () => [],
});

const activeTab = ref<'work_centers' | 'machines'>('work_centers');
const selectedStatus = ref<string>('all');
const showNewModal = ref(false);

const form = useForm({
    code: '',
    name: '',
    type: 'machine',
    capacity_hours_per_day: 8,
    hourly_rate: 35,
    status: 'operational',
});

const submitWorkCenter = () => {
    form.post('/admin/mrp/work-centers', {
        onSuccess: () => {
            showNewModal.value = false;
            form.reset();
        }
    });
};

const getStatusBadge = (status: string = '') => {
    switch (status.toLowerCase()) {
        case 'operational':
        case 'active':
        case 'running':
            return 'success';
        case 'idle':
        case 'available':
            return 'secondary';
        case 'maintenance':
        case 'maintenance required':
            return 'warning';
        case 'breakdown':
            return 'danger';
        default:
            return 'secondary';
    }
};

const averageOee = computed(() => {
    const list = props.workCenters || [];
    if (!list.length) return 0;
    const total = list.reduce((acc, c) => acc + (Number(c.oee_percentage) || 0), 0);
    return Math.round(total / list.length);
});

const activeCentersCount = computed(() => {
    return (props.workCenters || []).filter(w => {
        const s = (w.status || '').toLowerCase();
        return s === 'operational' || s === 'active';
    }).length;
});

const maintenanceCentersCount = computed(() => {
    return (props.workCenters || []).filter(w => {
        const s = (w.status || '').toLowerCase();
        return s === 'maintenance' || s === 'breakdown' || s.includes('maintenance');
    }).length;
});

const filteredWorkCenters = computed(() => {
    const list = props.workCenters || [];
    if (selectedStatus.value === 'all') return list;
    return list.filter(w => (w.status || '').toLowerCase() === selectedStatus.value.toLowerCase());
});
</script>

<template>
    <Head title="Work Centers & Machinery - MRP" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Work Centers & Manufacturing Cells
                    </h1>
                    <p class="text-xs text-slate-500">
                        Manage shop floor production machines, assembly lines, capacity limits, hourly operating costs, and Overall Equipment Effectiveness (OEE).
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <button
                        @click="showNewModal = true"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-lg shadow-indigo-900/20 transition-all flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>New Work Center</span>
                    </button>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Work Centers</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ workCenters.length }}</div>
                    <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">Shop floor production lines</div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Operations</div>
                    <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                        {{ activeCentersCount }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Currently running job cards</div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Average Shop OEE</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                        {{ averageOee }}%
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">World-class benchmark &gt; 85%</div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Maintenance & Downtime</div>
                    <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                        {{ maintenanceCentersCount }}
                    </div>
                    <div class="text-[11px] text-rose-500 mt-1">Requiring technician service</div>
                </div>
            </div>

            <!-- Tab Switcher & Status Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <button
                        @click="activeTab = 'work_centers'"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition-all"
                        :class="activeTab === 'work_centers' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
                    >
                        Work Centers ({{ workCenters.length }})
                    </button>
                    <button
                        @click="activeTab = 'machines'"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition-all"
                        :class="activeTab === 'machines' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
                    >
                        Machinery & Equipment ({{ machines.length }})
                    </button>
                </div>

                <div v-if="activeTab === 'work_centers'" class="flex items-center space-x-2">
                    <span class="text-xs text-slate-500">Filter:</span>
                    <select
                        v-model="selectedStatus"
                        class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="all">All Statuses</option>
                        <option value="operational">Operational</option>
                        <option value="idle">Idle</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>

            <!-- Tab 1: Work Centers Grid -->
            <div v-if="activeTab === 'work_centers'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="wc in filteredWorkCenters"
                    :key="wc.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm hover:border-indigo-200 dark:hover:border-indigo-900 transition-all flex flex-col justify-between space-y-4"
                >
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-[11px] font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded">
                                    {{ wc.code }}
                                </span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1.5">{{ wc.name }}</h3>
                                <span class="text-xs text-slate-500 capitalize">{{ (wc.type || 'machine').replace(/_/g, ' ') }}</span>
                                <div v-if="wc.branch" class="text-[11px] text-slate-400 mt-0.5">Location: {{ wc.branch }}</div>
                            </div>
                            <Badge :variant="getStatusBadge(wc.status)" size="sm">
                                {{ (wc.status || 'ACTIVE').toUpperCase() }}
                            </Badge>
                        </div>

                        <!-- Capacity & Load Gauge -->
                        <div class="space-y-1.5 pt-2">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-slate-500">Current Load</span>
                                <span :class="(wc.current_load_percentage ?? wc.utilization_percent ?? 0) > 90 ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-300'">
                                    {{ wc.current_load_percentage ?? wc.utilization_percent ?? 0 }}%
                                </span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="(wc.current_load_percentage ?? wc.utilization_percent ?? 0) > 90 ? 'bg-rose-500' : (wc.current_load_percentage ?? wc.utilization_percent ?? 0) > 70 ? 'bg-amber-500' : 'bg-indigo-600'"
                                    :style="{ width: `${Math.min(wc.current_load_percentage ?? wc.utilization_percent ?? 0, 100)}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Metrics Grid -->
                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-center">
                            <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Rate / Hr</div>
                                <div class="text-xs font-black text-slate-800 dark:text-slate-200 mt-0.5">
                                    ${{ Number(wc.hourly_rate ?? wc.hourly_cost ?? 0).toFixed(2) }}
                                </div>
                            </div>
                            <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Efficiency</div>
                                <div class="text-xs font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                                    {{ wc.efficiency_percentage ?? 85 }}%
                                </div>
                            </div>
                            <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <div class="text-[10px] text-slate-400 font-bold uppercase">OEE</div>
                                <div class="text-xs font-black text-indigo-600 dark:text-indigo-400 mt-0.5">
                                    {{ wc.oee_percentage ?? 80 }}%
                                </div>
                            </div>
                        </div>

                        <!-- Active Job Card Notice -->
                        <div v-if="wc.active_job_order" class="p-2.5 bg-indigo-50 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-900/40 text-xs flex items-center justify-between">
                            <span class="text-indigo-900 dark:text-indigo-300 font-medium truncate">Job: {{ wc.active_job_order }}</span>
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                            </span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span class="text-xs text-slate-400">Capacity: {{ wc.capacity_hours_per_day }}h/day</span>
                        <div class="flex items-center space-x-2">
                            <span v-if="wc.machines_count" class="text-xs text-slate-500 font-medium">{{ wc.machines_count }} Machines</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Machinery List -->
            <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-black text-slate-900 dark:text-white mb-4">Shop Floor Machine Inventory</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">Machine Code & Name</th>
                                <th class="px-4 py-3.5">Assigned Work Center</th>
                                <th class="px-4 py-3.5 text-center">Health Status</th>
                                <th class="px-4 py-3.5">Last Maintenance</th>
                                <th class="px-4 py-3.5">Next Due</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="m in machines" :key="m.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">{{ m.name }}</div>
                                    <span class="text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ m.code }}</span>
                                </td>
                                <td class="px-4 py-4 text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ m.work_center }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-bold"
                                        :class="m.health_percent > 90 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400'"
                                    >
                                        {{ m.health_percent }}% Health
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500">{{ m.last_maintenance }}</td>
                                <td class="px-4 py-4 text-xs font-medium" :class="m.next_maintenance.includes('Overdue') ? 'text-rose-600 font-bold' : 'text-slate-600 dark:text-slate-400'">
                                    {{ m.next_maintenance }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Badge :variant="getStatusBadge(m.status)" size="sm">{{ m.status }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- New Work Center Modal -->
            <Modal :show="showNewModal" @close="showNewModal = false" max-width="lg">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Create Work Center</h3>
                        <button @click="showNewModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitWorkCenter" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Center Code</label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. WC-MILL-01"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Center Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="e.g. High Precision CNC Milling"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Type</label>
                                <select
                                    v-model="form.type"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                >
                                    <option value="machine">Machine</option>
                                    <option value="assembly_line">Assembly Line</option>
                                    <option value="manual_station">Manual Station</option>
                                    <option value="subcontractor">Subcontractor</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Hourly Cost ($)</label>
                                <input
                                    v-model="form.hourly_rate"
                                    type="number"
                                    step="0.5"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Daily Capacity (Hours)</label>
                            <input
                                v-model="form.capacity_hours_per_day"
                                type="number"
                                step="0.5"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                required
                            />
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                            <Button variant="secondary" @click="showNewModal = false" type="button">Cancel</Button>
                            <Button variant="primary" type="submit" :loading="form.processing">Save Work Center</Button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
