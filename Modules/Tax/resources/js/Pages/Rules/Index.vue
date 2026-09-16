<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn, Modal } from '@/components';
import TaxRuleConditionBuilder from '../../Components/TaxRuleConditionBuilder.vue';
import { usePermissions } from '@/composables/usePermissions';

interface TaxRuleItem {
    id: number;
    name: string;
    priority: number;
    transaction_type: 'sales' | 'purchases' | 'both';
    sales_channel: string;
    customer_type: string;
    item_type: string;
    applied_tax_rate_id: number;
    applied_tax_rate_name: string;
    tax_inclusive_mode: boolean;
    is_active: boolean;
    description?: string;
}

interface TaxRateOption {
    id: number;
    name: string;
}

const props = defineProps<{
    rules: TaxRuleItem[];
    taxRates: TaxRateOption[];
    filters: {
        search?: string;
        transaction_type?: string;
        is_active?: string;
    };
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('tax.manage') || can('tax.manage_rules'));

const showModal = ref(false);
const editingRule = ref<TaxRuleItem | null>(null);

const form = useForm({
    name: '',
    priority: 10 as number | string,
    transaction_type: 'sales' as 'sales' | 'purchases' | 'both',
    sales_channel: 'all',
    customer_type: 'all',
    item_type: 'all',
    applied_tax_rate_id: '' as number | string | '',
    tax_inclusive_mode: false,
    is_active: true,
    description: '',
});

const openCreateModal = () => {
    editingRule.value = null;
    form.reset();
    form.priority = (props.rules.length + 1) * 10;
    form.transaction_type = 'sales';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (r: TaxRuleItem) => {
    editingRule.value = r;
    form.name = r.name;
    form.priority = r.priority;
    form.transaction_type = r.transaction_type;
    form.sales_channel = r.sales_channel;
    form.customer_type = r.customer_type;
    form.item_type = r.item_type;
    form.applied_tax_rate_id = r.applied_tax_rate_id;
    form.tax_inclusive_mode = Boolean(r.tax_inclusive_mode);
    form.is_active = Boolean(r.is_active);
    form.description = r.description || '';
    showModal.value = true;
};

const saveRule = () => {
    if (editingRule.value) {
        form.put(`/admin/tax/rules/${editingRule.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/tax/rules', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteRule = (r: TaxRuleItem) => {
    if (confirm(`Are you sure you want to delete rule "${r.name}"?`)) {
        router.delete(`/admin/tax/rules/${r.id}`);
    }
};

const columns: TableColumn[] = [
    { key: 'priority', label: 'Priority', align: 'center' },
    { key: 'name', label: 'Rule Title & Description' },
    { key: 'criteria', label: 'Matching Criteria' },
    { key: 'applied_rate', label: 'Assigned Tax Rate' },
    { key: 'mode', label: 'Quotation Mode', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'right' },
];
</script>

<template>
    <OrganizationLayout>
        <Head title="Conditional Tax Rules Engine" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Tax Rules Engine</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Conditional Tax Rules Engine
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Automated decision logic evaluating customer type, sales channel, destination jurisdiction, and product categories.
                    </p>
                </div>

                <Button
                    v-if="canManage"
                    variant="primary"
                    size="sm"
                    @click="openCreateModal"
                >
                    + Add Tax Rule
                </Button>
            </div>

            <!-- Rules Table -->
            <Card class="p-0 overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="rules"
                >
                    <template #cell-priority="{ row }">
                        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                            #{{ row.priority }}
                        </span>
                    </template>

                    <template #cell-name="{ row }">
                        <div class="py-2">
                            <div class="font-semibold text-zinc-900 dark:text-white text-sm">
                                {{ row.name }}
                            </div>
                            <div v-if="row.description" class="text-xs text-zinc-400 line-clamp-1 mt-0.5">
                                {{ row.description }}
                            </div>
                        </div>
                    </template>

                    <template #cell-criteria="{ row }">
                        <div class="text-xs space-y-0.5 font-mono text-zinc-600 dark:text-zinc-400">
                            <div>Scope: <strong class="capitalize">{{ row.transaction_type }}</strong></div>
                            <div>Customer: <strong>{{ row.customer_type }}</strong></div>
                            <div>Channel: <strong>{{ row.sales_channel }}</strong></div>
                        </div>
                    </template>

                    <template #cell-applied_rate="{ row }">
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ row.applied_tax_rate_name }}
                        </span>
                    </template>

                    <template #cell-mode="{ row }">
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-[10px] font-semibold',
                                row.tax_inclusive_mode ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                            ]"
                        >
                            {{ row.tax_inclusive_mode ? 'Tax-Inclusive' : 'Tax-Exclusive' }}
                        </span>
                    </template>

                    <template #cell-status="{ row }">
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-[10px] font-semibold uppercase',
                                row.is_active
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900'
                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                            ]"
                        >
                            {{ row.is_active ? 'Active' : 'Disabled' }}
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
                                @click="deleteRule(row)"
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

            <!-- Rule Modal -->
            <Modal
                :show="showModal"
                :title="editingRule ? 'Edit Tax Rule' : 'Create Tax Rule'"
                max-width="3xl"
                @close="showModal = false"
            >
                <form @submit.prevent="saveRule" class="space-y-6 pt-2">
                    <TaxRuleConditionBuilder
                        :form="form"
                        :tax-rates="taxRates"
                    />

                    <div class="flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <label class="flex items-center gap-2.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="w-4 h-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>Rule is Active & Enforced</span>
                        </label>

                        <div class="flex items-center gap-3">
                            <Button type="button" variant="secondary" size="sm" @click="showModal = false">
                                Cancel
                            </Button>
                            <Button type="submit" variant="primary" size="sm" :disabled="form.processing">
                                {{ editingRule ? 'Update Tax Rule' : 'Create Tax Rule' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
