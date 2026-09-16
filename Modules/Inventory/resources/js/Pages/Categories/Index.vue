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
    parent_id?: number | null;
    parent?: { id: number; name: string } | null;
    items_count?: number;
    description?: string;
    status: 'active' | 'inactive';
}

const props = defineProps<{
    categories: CategoryItem[];
    parentCategories: Array<{ id: number; name: string }>;
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('inventory.manage') || can('inventory.categories.manage'));

const showModal = ref(false);
const editingCategory = ref<CategoryItem | null>(null);

const form = useForm({
    name: '',
    code: '',
    parent_id: '' as number | '',
    description: '',
    status: 'active' as 'active' | 'inactive',
});

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    form.status = 'active';
    showModal.value = true;
};

const openEditModal = (cat: CategoryItem) => {
    editingCategory.value = cat;
    form.name = cat.name;
    form.code = cat.code || '';
    form.parent_id = cat.parent_id || '';
    form.description = cat.description || '';
    form.status = cat.status || 'active';
    showModal.value = true;
};

const saveCategory = () => {
    if (editingCategory.value) {
        form.put(`/admin/inventory/categories/${editingCategory.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/inventory/categories', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteCategory = (cat: CategoryItem) => {
    if (confirm(`Are you sure you want to delete category "${cat.name}"?`)) {
        router.delete(`/admin/inventory/categories/${cat.id}`);
    }
};

const columns: TableColumn[] = [
    { key: 'name', label: 'Category Name' },
    { key: 'code', label: 'Code' },
    { key: 'parent', label: 'Parent Category' },
    { key: 'items_count', label: 'Items Linked', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'right' },
];
</script>

<template>
    <OrganizationLayout>
        <Head title="Categories & Classifications" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Categories</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Item Categories & Hierarchies
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Organize catalog into hierarchical parent and sub-categories for POS speed and financial reporting.
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
                            <div class="font-semibold text-zinc-900 dark:text-white text-sm">
                                {{ row.name }}
                            </div>
                            <div v-if="row.description" class="text-xs text-zinc-400 line-clamp-1">
                                {{ row.description }}
                            </div>
                        </div>
                    </template>

                    <template #cell-code="{ row }">
                        <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                            {{ row.code || '—' }}
                        </span>
                    </template>

                    <template #cell-parent="{ row }">
                        <span v-if="row.parent" class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            📁 {{ row.parent.name }}
                        </span>
                        <span v-else class="text-xs text-zinc-400 italic">
                            Top-level root
                        </span>
                    </template>

                    <template #cell-items_count="{ row }">
                        <span class="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ row.items_count || 0 }} items
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
                                @click="deleteCategory(row)"
                                class="text-xs text-rose-600 hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

            <!-- Quick Create / Edit Modal -->
            <Modal
                :show="showModal"
                :title="editingCategory ? 'Edit Category' : 'Create Category'"
                @close="showModal = false"
            >
                <form @submit.prevent="saveCategory" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Category Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Beverages, Bakery, Electronics"
                            required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Category Code
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. CAT-BEV"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Parent Category
                            </label>
                            <select
                                v-model="form.parent_id"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                            >
                                <option value="">None (Top-Level)</option>
                                <option
                                    v-for="p in parentCategories"
                                    :key="p.id"
                                    :value="p.id"
                                    :disabled="editingCategory && p.id === editingCategory.id"
                                >
                                    {{ p.name }}
                                </option>
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
                            placeholder="Optional category description..."
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300">
                            <input
                                type="checkbox"
                                :checked="form.status === 'active'"
                                @change="form.status = form.status === 'active' ? 'inactive' : 'active'"
                                class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                            />
                            <span>Category is Active</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <Button type="button" variant="ghost" size="sm" @click="showModal = false">
                                Cancel
                            </Button>
                            <Button type="submit" variant="primary" size="sm" :disabled="form.processing">
                                {{ editingCategory ? 'Update' : 'Create' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
