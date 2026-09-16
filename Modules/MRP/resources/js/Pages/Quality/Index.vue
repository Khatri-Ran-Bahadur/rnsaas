<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Badge, Button, Modal } from '@/components';

interface QualityInspection {
    id: number;
    inspection_number: string;
    type?: string;
    production_order?: string;
    wo_number?: string;
    item_code?: string;
    item_name?: string;
    product_name?: string;
    lot_or_batch?: string;
    checkpoint_name?: string;
    target?: string;
    actual?: string;
    sample_size?: number;
    inspected_qty?: number;
    passed_qty?: number;
    rejected_qty?: number;
    failed_qty?: number;
    inspector?: string;
    inspection_date?: string;
    status: string;
    ncr_number?: string;
    notes?: string;
}

const props = withDefaults(
    defineProps<{
        inspections?: QualityInspection[];
        summary?: {
            total_inspections: number;
            pass_rate_percentage: number;
            open_ncrs: number;
            quarantined_batches: number;
        };
    }>(),
    {
        inspections: () => [],
        summary: () => ({
            total_inspections: 0,
            pass_rate_percentage: 100,
            open_ncrs: 0,
            quarantined_batches: 0,
        }),
    }
);

const selectedType = ref<string>('all');
const showNewInspectionModal = ref(false);
const selectedInspection = ref<QualityInspection | null>(null);
const showCertificateModal = ref(false);

const form = useForm({
    inspection_number: `QC-2026-${String(Math.floor(Math.random() * 900) + 100)}`,
    type: 'final_finished',
    item_name: '',
    item_code: '',
    lot_or_batch: '',
    inspected_qty: 10,
    passed_qty: 10,
    rejected_qty: 0,
    inspector: 'QA Inspector',
    status: 'passed',
    notes: '',
});

const submitInspection = () => {
    form.post('/admin/mrp/quality', {
        onSuccess: () => {
            showNewInspectionModal.value = false;
            form.reset();
        },
    });
};

const openCertificate = (qc: QualityInspection) => {
    selectedInspection.value = qc;
    showCertificateModal.value = true;
};

const getStatusBadge = (status: string = '') => {
    switch (status.toLowerCase()) {
        case 'passed':
        case 'compliant':
            return 'success';
        case 'failed':
        case 'failed / scrap tagged':
            return 'danger';
        case 'conditional_pass':
            return 'warning';
        case 'pending':
            return 'secondary';
        default:
            return 'secondary';
    }
};

const filteredInspections = computed(() => {
    const list = props.inspections || [];
    if (selectedType.value === 'all') return list;
    return list.filter(qc => (qc.type || '').toLowerCase() === selectedType.value.toLowerCase());
});
</script>

<template>
    <Head title="Quality Control & Compliance - MRP" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Quality Control & Assurance (QA/QC)
                    </h1>
                    <p class="text-xs text-slate-500">
                        Maintain compliance, in-process routing quality gates, finished goods inspection checklists, and Non-Conformance Reports (NCR).
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <button
                        type="button"
                        @click="showNewInspectionModal = true"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-lg shadow-indigo-900/20 transition-all flex items-center space-x-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>New Inspection Record</span>
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Inspected</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ summary.total_inspections }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">Batch inspection records</div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">First Pass Yield</div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ summary.pass_rate_percentage }}%
                    </div>
                    <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">Acceptable quality threshold</div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Open NCRs</div>
                    <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ summary.open_ncrs }}</div>
                    <div class="text-[11px] text-rose-500 mt-1">Non-conformance investigations</div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Quarantined Stock</div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ summary.quarantined_batches }}</div>
                    <div class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold mt-1">Batches locked from dispatch</div>
                </div>
            </div>

            <!-- Quality Inspections List -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Inspection Log & Checkpoints</h3>
                        <p class="text-xs text-slate-500">Quality verification records across raw materials, work-in-progress, and final release.</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-slate-500 font-medium">Filter Stage:</span>
                        <select
                            v-model="selectedType"
                            class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="all">All Stages</option>
                            <option value="incoming_raw">Incoming Raw</option>
                            <option value="in_process">In-Process Routing</option>
                            <option value="final_finished">Final Finished Goods</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-4 py-3">Inspection #</th>
                                <th class="px-4 py-3">Stage / Gate</th>
                                <th class="px-4 py-3">Item / Assembly</th>
                                <th class="px-4 py-3">Batch / Lot #</th>
                                <th class="px-4 py-3">Yield (Pass/Fail)</th>
                                <th class="px-4 py-3">Inspector</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="qc in filteredInspections" :key="qc.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-4 py-3.5 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ qc.inspection_number }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize">
                                        {{ (qc.type || 'in_process').replace(/_/g, ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ qc.item_name || qc.product_name }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ qc.item_code || qc.production_order || 'STD' }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-xs font-mono text-slate-600 dark:text-slate-400">
                                    {{ qc.lot_or_batch || qc.wo_number || 'LOT-001' }}
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="font-bold text-emerald-600">{{ qc.passed_qty ?? qc.sample_size ?? 0 }} passed</span>
                                    <span v-if="(qc.rejected_qty || qc.failed_qty || 0) > 0" class="text-rose-500 ml-1 font-bold">
                                        ({{ qc.rejected_qty || qc.failed_qty }} scrap)
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                                    {{ qc.inspector }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <Badge :variant="getStatusBadge(qc.status)" size="sm">
                                        {{ (qc.status || 'PASSED').replace(/_/g, ' ').toUpperCase() }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <button
                                        type="button"
                                        @click="openCertificate(qc)"
                                        class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/80 text-indigo-600 dark:text-indigo-400 font-bold text-xs transition-colors cursor-pointer"
                                    >
                                        View Certificate
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredInspections.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500 text-xs">
                                    No quality inspection records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- New Inspection Modal -->
            <Modal :show="showNewInspectionModal" @close="showNewInspectionModal = false" max-width="lg">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Record Quality Inspection</h3>
                        <button @click="showNewInspectionModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitInspection" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Inspection #</label>
                                <input
                                    v-model="form.inspection_number"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Stage</label>
                                <select
                                    v-model="form.type"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                >
                                    <option value="incoming_raw">Incoming Raw Material</option>
                                    <option value="in_process">In-Process Routing Gate</option>
                                    <option value="final_finished">Final Finished Goods</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Item / Product Name</label>
                                <input
                                    v-model="form.item_name"
                                    type="text"
                                    placeholder="e.g. Sourdough Loaf or IoT Gateway"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Batch / Lot #</label>
                                <input
                                    v-model="form.lot_or_batch"
                                    type="text"
                                    placeholder="e.g. LOT-2026-0911"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Inspected Qty</label>
                                <input
                                    v-model.number="form.inspected_qty"
                                    type="number"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Passed Qty</label>
                                <input
                                    v-model.number="form.passed_qty"
                                    type="number"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rejected Qty</label>
                                <input
                                    v-model.number="form.rejected_qty"
                                    type="number"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Inspector</label>
                                <input
                                    v-model="form.inspector"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Result Status</label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                >
                                    <option value="passed">Passed</option>
                                    <option value="failed">Failed / Scrap</option>
                                    <option value="conditional_pass">Conditional Pass</option>
                                    <option value="pending">Pending Lab Analysis</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Observation Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Inspection observations, tolerances, or NCR justification..."
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                            <Button variant="secondary" @click="showNewInspectionModal = false" type="button">Cancel</Button>
                            <Button variant="primary" type="submit" :loading="form.processing">Save Inspection</Button>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- View Certificate Modal -->
            <Modal :show="showCertificateModal" @close="showCertificateModal = false" max-width="lg">
                <div v-if="selectedInspection" class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-mono font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded">
                                {{ selectedInspection.inspection_number }}
                            </span>
                            <Badge :variant="getStatusBadge(selectedInspection.status)" size="sm">
                                {{ (selectedInspection.status || 'PASSED').toUpperCase() }}
                            </Badge>
                        </div>
                        <button @click="showCertificateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 space-y-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-base font-black text-slate-900 dark:text-white">
                                    {{ selectedInspection.item_name || selectedInspection.product_name }}
                                </h4>
                                <span class="text-xs font-mono text-slate-500">
                                    Lot: {{ selectedInspection.lot_or_batch || selectedInspection.wo_number }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-slate-500 block">Inspection Date</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    {{ selectedInspection.inspection_date || '2026-09-11' }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-200 dark:border-slate-700">
                            <div>
                                <span class="text-slate-500 block">Stage / Gate:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200 capitalize">
                                    {{ (selectedInspection.type || 'in_process').replace(/_/g, ' ') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Verified Inspector:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ selectedInspection.inspector }}
                                </span>
                            </div>
                            <div v-if="selectedInspection.checkpoint_name">
                                <span class="text-slate-500 block">Checkpoint:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ selectedInspection.checkpoint_name }}
                                </span>
                            </div>
                            <div v-if="selectedInspection.target">
                                <span class="text-slate-500 block">Target / Actual:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ selectedInspection.target }} (Result: {{ selectedInspection.actual }})
                                </span>
                            </div>
                        </div>

                        <div v-if="selectedInspection.notes" class="p-3 bg-white dark:bg-slate-900 rounded-xl text-xs text-slate-600 dark:text-slate-300 border border-slate-100 dark:border-slate-800">
                            <strong>Inspector Notes:</strong> {{ selectedInspection.notes }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800">
                        <span class="text-xs text-slate-400">QA Compliance Certificate Verified</span>
                        <Button variant="secondary" @click="showCertificateModal = false">Close</Button>
                    </div>
                </div>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
