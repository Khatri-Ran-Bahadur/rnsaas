<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn, Modal } from '@/components';
import { usePermissions } from '@/composables/usePermissions';

interface CategoryItem {
    id: number;
    name: string;
    code: string;
    treatment: 'taxable' | 'zero_rated' | 'exempt' | 'out_of_scope';
    default_rate: string;
    claimable_input: boolean;
    description?: string;
    items_count?: number;
    is_system?: boolean;
}

const props = defineProps<{
    categories: CategoryItem[];
    filters: {
        search?: string;
        treatment?: string;
    };
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('tax.manage') || can('tax.manage_categories'));

const showModal = ref(false);
const editingCategory = ref<CategoryItem | null>(null);

const form = useForm({
    name: '',
    code: '',
    treatment: 'taxable' as 'taxable' | 'zero_rated' | 'exempt' | 'out_of_scope',
    claimable_input: true,
    description: '',
});

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    form.treatment = 'taxable';
    form.claimable_input = true;
    showModal.value = true;
};

const openEditModal = (cat: CategoryItem) => {
    editingCategory.value = cat;
    form.name = cat.name;
    form.code = cat.code;
    form.treatment = cat.treatment;
    form.claimable_input = Boolean(cat.claimable_input);
    form.description = cat.description || '';
    showModal.value = true;
};

const saveCategory = () => {
    if (editingCategory.value) {
        form.put(`/admin/tax/categories/${editingCategory.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/tax/categories', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteCategory = (cat: CategoryItem) => {
    if (confirm(`Are you sure you want to delete "${cat.name}"?`)) {
        router.delete(`/admin/tax/categories/${cat.id}`);
    }
};

const columns: TableColumn[] = [
    { key: 'name', label: 'Category & Code' },
    { key: 'treatment', label: 'Statutory Treatment' },
    { key: 'input_tax', label: 'Input Tax Recoverability' },
    { key: 'items', label: 'Catalog Products', align: 'right' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

const getTreatmentBadge = (treatment: string) => {
    switch (treatment) {
        case 'taxable':
            return { label: 'Taxable (Standard/Reduced)', class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200' };
        case 'zero_rated':
            return { label: 'Zero-Rated (0%)', class: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200' };
        case 'exempt':
            return { label: 'Statutory Exempt', class: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200' };
        case 'out_of_scope':
            return { label: 'Out of Scope', class: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200' };
        default:
            return { label: treatment, class: 'bg-zinc-100 text-zinc-700' };
    }
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Tax Categories & Classifications" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Tax Categories</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Tax Categories & Classifications
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Statutory classification matrix used by item catalogs, invoicing lines, and e-invoicing tax reports.
                    </p>
                </div>

                <Button
                    v-if="canManage"
                    variant="primary"
                    size="sm"
                    @click="openCreateModal"
                >
                    + Add Category
                </Button>
            </div>

            <!-- Categories Table -->
            <Card class="p-0 overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="categories"
                >
                    <template #cell-name="{ row }">
                        <div class="py-2">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-900 dark:text-white text-sm">
                                    {{ row.name }}
                                </span>
                                <span v-if="row.is_system" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-500">
                                    SYSTEM
                                </span>
                            </div>
                            <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                Code: {{ row.code }} <span v-if="row.description">• {{ row.description }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-treatment="{ row }">
                        <span :class="['px-2.5 py-1 rounded-md text-xs font-semibold border', getTreatmentBadge(row.treatment).class]">
                            {{ getTreatmentBadge(row.treatment).label }}
                        </span>
                    </template>

                    <template #cell-input_tax="{ row }">
                        <span v-if="row.claimable_input" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            ✓ Fully Recoverable (Input Tax Credit)
                        </span>
                        <span v-else class="text-xs font-medium text-zinc-400 flex items-center gap-1">
                            ✕ Non-Claimable
                        </span>
                    </template>

                    <template #cell-items="{ row }">
                        <span class="font-mono text-xs font-bold text-zinc-900 dark:text-white">
                            {{ row.items_count || 0 }} items linked
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
                                v-if="canManage && !row.is_system"
                                type="button"
                                @click="deleteCategory(row)"
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

            <!-- Category Modal -->
            <Modal
                :show="showModal"
                :title="editingCategory ? 'Edit Tax Category' : 'Create Tax Category'"
                @close="showModal = false"
            >
                <form @submit.prevent="saveCategory" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Category Title <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Standard Rateable, Digital Services"
                            required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Category Code <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. STANDARD, EXEMPT"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Statutory Treatment
                            </label>
                            <select
                                v-model="form.treatment"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                            >
                                <option value="taxable">Taxable Supply (Standard / Concession)</option>
                                <option value="zero_rated">Zero-Rated Supply (0% with Input Credit)</option>
                                <option value="exempt">Statutory Exempt (0% No Input Credit)</option>
                                <option value="out_of_scope">Out of Scope / Non-Taxable</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Statutory guidance and scope notes..."
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.claimable_input"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                            />
                            <span>Input tax is fully claimable / recoverable on purchases</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <Button type="button" variant="ghost" size="sm" @click="showModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" size="sm" :disabled="form.processing">
                            {{ editingCategory ? 'Update' : 'Create' }}
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
