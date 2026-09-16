<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn, Modal } from '@/components';
import { usePermissions } from '@/composables/usePermissions';

interface TaxTypeItem {
    id: number;
    name: string;
    code: string;
    scope: 'sales' | 'purchases' | 'both';
    country_code: string;
    tax_authority?: string;
    description?: string;
    rates_count?: number;
    status: 'active' | 'inactive';
    created_at?: string;
}

const props = defineProps<{
    taxTypes: TaxTypeItem[];
    filters: {
        search?: string;
        scope?: string;
        status?: string;
    };
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('tax.manage') || can('tax.manage_types'));

const showModal = ref(false);
const editingType = ref<TaxTypeItem | null>(null);

const form = useForm({
    name: '',
    code: '',
    scope: 'both' as 'sales' | 'purchases' | 'both',
    tax_authority: '',
    description: '',
    status: 'active' as 'active' | 'inactive',
});

const openCreateModal = () => {
    editingType.value = null;
    form.reset();
    form.scope = 'both';
    form.status = 'active';
    showModal.value = true;
};

const openEditModal = (t: TaxTypeItem) => {
    editingType.value = t;
    form.name = t.name;
    form.code = t.code;
    form.scope = t.scope;
    form.tax_authority = t.tax_authority || '';
    form.description = t.description || '';
    form.status = t.status || 'active';
    showModal.value = true;
};

const saveTaxType = () => {
    if (editingType.value) {
        form.put(`/admin/tax/types/${editingType.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/tax/types', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteTaxType = (t: TaxTypeItem) => {
    if (confirm(`Are you sure you want to delete "${t.name}"?`)) {
        router.delete(`/admin/tax/types/${t.id}`);
    }
};

const columns: TableColumn[] = [
    { key: 'name', label: 'Tax Type & Code' },
    { key: 'scope', label: 'Transaction Scope' },
    { key: 'authority', label: 'Tax Authority' },
    { key: 'rates', label: 'Configured Rates', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const getScopeBadge = (scope: string) => {
    switch (scope) {
        case 'sales':
            return { label: 'Sales (Output Tax)', class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200' };
        case 'purchases':
            return { label: 'Purchases (Input Tax)', class: 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200' };
        case 'both':
            return { label: 'Both Sales & Purchases', class: 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200' };
        default:
            return { label: scope, class: 'bg-zinc-100 text-zinc-700' };
    }
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Tax Types & Regimes" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Tax Types</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Tax Types & Regimes
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Define top-level tax categories (VAT, GST, SST, Withholding Tax, Customs Excise, Environmental Fees).
                    </p>
                </div>

                <Button
                    v-if="canManage"
                    variant="primary"
                    size="sm"
                    @click="openCreateModal"
                >
                    + Add Tax Type
                </Button>
            </div>

            <!-- Tax Types Table -->
            <Card class="p-0 overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="taxTypes"
                >
                    <template #cell-name="{ row }">
                        <div class="py-2">
                            <div class="font-semibold text-zinc-900 dark:text-white text-sm">
                                {{ row.name }}
                            </div>
                            <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                Code: {{ row.code }} <span v-if="row.description">• {{ row.description }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-scope="{ row }">
                        <span :class="['px-2.5 py-1 rounded-md text-xs font-semibold border', getScopeBadge(row.scope).class]">
                            {{ getScopeBadge(row.scope).label }}
                        </span>
                    </template>

                    <template #cell-authority="{ row }">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">
                            {{ row.tax_authority || 'Statutory Authority' }}
                        </span>
                    </template>

                    <template #cell-rates="{ row }">
                        <span class="font-mono text-xs font-bold text-zinc-900 dark:text-white">
                            {{ row.rates_count || 0 }} active rates
                        </span>
                    </template>

                    <template #cell-status="{ row }">
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-[10px] font-semibold uppercase',
                                row.status === 'active'
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900'
                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                            ]"
                        >
                            {{ row.status }}
                        </span>
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
                                @click="deleteTaxType(row)"
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

            <!-- Quick Modal -->
            <Modal
                :show="showModal"
                :title="editingType ? 'Edit Tax Type' : 'Create Tax Type'"
                @close="showModal = false"
            >
                <form @submit.prevent="saveTaxType" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Tax Type Title <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Value Added Tax (VAT), Withholding Tax"
                            required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Unique Code <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. VAT, SST, WHT"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Scope
                            </label>
                            <select
                                v-model="form.scope"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="both">Both Sales & Purchases</option>
                                <option value="sales">Sales (Output Tax Only)</option>
                                <option value="purchases">Purchases (Input Tax Only)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Tax Authority / Board
                        </label>
                        <input
                            v-model="form.tax_authority"
                            type="text"
                            placeholder="e.g. Inland Revenue Board, Customs Agency"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Statutory scope and applicability notes..."
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                type="checkbox"
                                :checked="form.status === 'active'"
                                @change="form.status = form.status === 'active' ? 'inactive' : 'active'"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                            />
                            <span>Active Tax Type</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <Button type="button" variant="ghost" size="sm" @click="showModal = false">
                                Cancel
                            </Button>
                            <Button type="submit" variant="primary" size="sm" :disabled="form.processing">
                                {{ editingType ? 'Update' : 'Create' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
