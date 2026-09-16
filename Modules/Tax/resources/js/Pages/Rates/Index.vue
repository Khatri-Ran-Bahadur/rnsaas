<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn, Modal } from '@/components';
import TaxRateTimelineBadge from '../../Components/TaxRateTimelineBadge.vue';
import TaxPreviewModal from '../../Components/TaxPreviewModal.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useCurrency } from '@/composables/useCurrency';

interface TaxRateItem {
    id: number;
    name: string;
    code: string;
    tax_type_id: number;
    tax_type_name: string;
    tax_category: string;
    rate_type: 'percentage' | 'fixed';
    rate: number;
    fixed_amount?: number | null;
    effective_from: string;
    effective_until?: string | null;
    timeline_status: 'active' | 'scheduled' | 'expired' | 'draft' | string;
    country: string;
    region: string;
    is_recoverable: boolean;
    is_compound: boolean;
    description?: string;
}

interface TaxTypeOption {
    id: number;
    name: string;
}

const props = defineProps<{
    taxRates: TaxRateItem[];
    taxTypes: TaxTypeOption[];
    filters: {
        search?: string;
        tax_type_id?: string;
        timeline_status?: string;
        tax_category?: string;
    };
}>();

const { currencyCode, currencySymbol } = useCurrency();
const currency = computed(() => currencyCode?.value || 'NPR');

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('tax.manage') || can('tax.manage_rates'));

const showModal = ref(false);
const showSimulationModal = ref(false);
const editingRate = ref<TaxRateItem | null>(null);

const form = useForm({
    name: '',
    code: '',
    tax_type_id: '' as number | '',
    tax_category: 'standard',
    rate_type: 'percentage' as 'percentage' | 'fixed',
    rate: 0.00 as number | string,
    fixed_amount: '' as number | string,
    effective_from: new Date().toISOString().slice(0, 10),
    effective_until: '',
    country: 'Global',
    region: 'All Regions',
    is_recoverable: true,
    is_compound: false,
    description: '',
});

const selectedTimeline = ref(props.filters.timeline_status || '');
const selectedType = ref(props.filters.tax_type_id || '');
const search = ref(props.filters.search || '');

const applyFilters = () => {
    router.get(
        '/admin/tax/rates',
        {
            search: search.value || undefined,
            tax_type_id: selectedType.value || undefined,
            timeline_status: selectedTimeline.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const openCreateModal = () => {
    editingRate.value = null;
    form.reset();
    form.rate_type = 'percentage';
    form.tax_category = 'standard';
    form.is_recoverable = true;
    showModal.value = true;
};

const openEditModal = (r: TaxRateItem) => {
    editingRate.value = r;
    form.name = r.name;
    form.code = r.code;
    form.tax_type_id = r.tax_type_id;
    form.tax_category = r.tax_category;
    form.rate_type = r.rate_type;
    form.rate = r.rate;
    form.fixed_amount = r.fixed_amount || '';
    form.effective_from = r.effective_from;
    form.effective_until = r.effective_until || '';
    form.country = r.country;
    form.region = r.region;
    form.is_recoverable = Boolean(r.is_recoverable);
    form.is_compound = Boolean(r.is_compound);
    form.description = r.description || '';
    showModal.value = true;
};

const saveRate = () => {
    if (editingRate.value) {
        form.put(`/admin/tax/rates/${editingRate.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/tax/rates', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteRate = (r: TaxRateItem) => {
    if (confirm(`Are you sure you want to delete rate "${r.name}"?`)) {
        router.delete(`/admin/tax/rates/${r.id}`);
    }
};

const columns: TableColumn[] = [
    { key: 'name', label: 'Tax Rate & Code' },
    { key: 'type', label: 'Tax Type & Category' },
    { key: 'rate_value', label: 'Rate Value', align: 'right' },
    { key: 'timeline', label: 'Effective Timeline Status', align: 'center' },
    { key: 'validity', label: 'Valid Period' },
    { key: 'actions', label: 'Actions', align: 'right' },
];
</script>

<template>
    <OrganizationLayout>
        <Head title="Tax Rates & Effective-Date Management" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Tax Rates</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Tax Rates & Effective Dates
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Schedule future tax rate adjustments without modifying historical locked transaction audit snapshots.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        @click="showSimulationModal = true"
                    >
                        <svg class="w-4 h-4 mr-1.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Simulate Calculation
                    </Button>
                    <Button
                        v-if="canManage"
                        variant="primary"
                        size="sm"
                        @click="openCreateModal"
                    >
                        + Add Tax Rate
                    </Button>
                </div>
            </div>

            <!-- Filters Bar -->
            <Card class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search rate name, code (e.g. SR-8, ZR-0)..."
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <select
                            v-model="selectedTimeline"
                            @change="applyFilters"
                            class="w-full px-2.5 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        >
                            <option value="">All Timeline States</option>
                            <option value="active">Active Now</option>
                            <option value="scheduled">Scheduled Future</option>
                            <option value="expired">Expired Past</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="selectedType"
                            @change="applyFilters"
                            class="w-full px-2.5 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        >
                            <option value="">All Tax Types</option>
                            <option v-for="t in taxTypes" :key="t.id" :value="t.id">
                                {{ t.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </Card>

            <!-- Rates Table -->
            <Card class="p-0 overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="taxRates"
                >
                    <template #cell-name="{ row }">
                        <div class="py-2">
                            <div class="font-semibold text-zinc-900 dark:text-white text-sm">
                                {{ row.name }}
                            </div>
                            <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                Code: {{ row.code }} <span v-if="row.region">• {{ row.region }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-type="{ row }">
                        <div class="text-xs">
                            <div class="font-medium text-zinc-800 dark:text-zinc-200">
                                {{ row.tax_type_name }}
                            </div>
                            <div class="capitalize text-zinc-400 text-[11px]">
                                {{ row.tax_category.replace('_', ' ') }}
                            </div>
                        </div>
                    </template>

                    <template #cell-rate_value="{ row }">
                        <div class="text-right font-mono font-bold text-sm text-zinc-900 dark:text-white">
                            <span v-if="row.rate_type === 'percentage'">{{ Number(row.rate).toFixed(2) }}%</span>
                            <span v-else>{{ currency }} {{ Number(row.fixed_amount).toFixed(2) }}</span>
                        </div>
                    </template>

                    <template #cell-timeline="{ row }">
                        <TaxRateTimelineBadge
                            :status="row.timeline_status"
                            :effective-from="row.effective_from"
                            :effective-until="row.effective_until"
                        />
                    </template>

                    <template #cell-validity="{ row }">
                        <div class="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                            <div>From: {{ row.effective_from }}</div>
                            <div>Until: {{ row.effective_until || 'Indefinite' }}</div>
                        </div>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                v-if="canManage"
                                type="button"
                                @click="openEditModal(row)"
                                class="text-xs text-indigo-600 hover:underline"
                            >
                                Edit
                            </button>
                            <button
                                v-if="canManage"
                                type="button"
                                @click="deleteRate(row)"
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

            <!-- Rate Modal -->
            <Modal
                :show="showModal"
                :title="editingRate ? 'Edit Tax Rate' : 'Create Tax Rate'"
                @close="showModal = false"
            >
                <form @submit.prevent="saveRate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Rate Title <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Standard Rate (8%), Zero-Rated Export"
                            required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Rate Code <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. SR-8, ZR-0"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Tax Type <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.tax_type_id"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="">Select tax type...</option>
                                <option v-for="t in taxTypes" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Rate Value Mode
                            </label>
                            <select
                                v-model="form.rate_type"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="percentage">Percentage Rate (%)</option>
                                <option value="fixed">Fixed Monetary Amount</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Rate / Amount <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-if="form.rate_type === 'percentage'"
                                    v-model.number="form.rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    required
                                    class="w-full pr-8 pl-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                                />
                                <input
                                    v-else
                                    v-model.number="form.fixed_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    required
                                    class="w-full pl-8 pr-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                                />
                                <span v-if="form.rate_type === 'percentage'" class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 font-bold">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Effective Date Range -->
                    <div class="grid grid-cols-2 gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Effective From <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.effective_from"
                                type="date"
                                required
                                class="w-full px-2.5 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Effective Until / Expiry
                            </label>
                            <input
                                v-model="form.effective_until"
                                type="date"
                                class="w-full px-2.5 py-1.5 text-xs bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            />
                            <span class="text-[10px] text-zinc-400">Leave blank for ongoing validity.</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 pt-2">
                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_recoverable"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                            />
                            <span>Input Tax is Recoverable / Claimable</span>
                        </label>

                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_compound"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                            />
                            <span>Compound Tax (Tax on Tax)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <Button type="button" variant="ghost" size="sm" @click="showModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" size="sm" :disabled="form.processing">
                            {{ editingRate ? 'Update Rate' : 'Register Rate' }}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>

        <TaxPreviewModal
            :show="showSimulationModal"
            :rates="taxRates"
            @close="showSimulationModal = false"
        />
    </OrganizationLayout>
</template>
