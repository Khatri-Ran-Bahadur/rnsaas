<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn, Modal } from '@/components';
import { usePermissions } from '@/composables/usePermissions';

interface LocationItem {
    id: number;
    name: string;
    code: string;
    type: 'store' | 'warehouse' | 'kitchen' | 'transit' | 'returns';
    branch_name?: string;
    address?: string;
    is_default: boolean;
    total_stock_count?: number;
    status: 'active' | 'inactive';
}

const props = defineProps<{
    locations: LocationItem[];
    branches?: Array<{ id: number; name: string }>;
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('inventory.manage') || can('inventory.locations.manage'));

const showModal = ref(false);
const editingLocation = ref<LocationItem | null>(null);

const form = useForm({
    name: '',
    code: '',
    type: 'warehouse' as 'store' | 'warehouse' | 'kitchen' | 'transit' | 'returns',
    branch_id: '' as number | '',
    address: '',
    is_default: false,
    status: 'active' as 'active' | 'inactive',
});

const openCreateModal = () => {
    editingLocation.value = null;
    form.reset();
    form.type = 'warehouse';
    form.status = 'active';
    showModal.value = true;
};

const openEditModal = (loc: LocationItem) => {
    editingLocation.value = loc;
    form.name = loc.name;
    form.code = loc.code || '';
    form.type = loc.type || 'warehouse';
    form.address = loc.address || '';
    form.is_default = Boolean(loc.is_default);
    form.status = loc.status || 'active';
    showModal.value = true;
};

const saveLocation = () => {
    if (editingLocation.value) {
        form.put(`/admin/inventory/locations/${editingLocation.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post('/admin/inventory/locations', {
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const columns: TableColumn[] = [
    { key: 'name', label: 'Warehouse / Location' },
    { key: 'type', label: 'Type' },
    { key: 'branch', label: 'Branch Context' },
    { key: 'stock', label: 'Active Items', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'actions', label: 'Actions', align: 'right' },
];
</script>

<template>
    <OrganizationLayout>
        <Head title="Warehouses & Storage Locations" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Warehouses & Locations</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Warehouses, Stores & Locations
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Manage physical storage facilities, retail store shelves, kitchen holding areas, and in-transit hubs.
                    </p>
                </div>

                <Button
                    v-if="canManage"
                    variant="primary"
                    size="sm"
                    @click="openCreateModal"
                >
                    + Add Location
                </Button>
            </div>

            <!-- Locations Table Card -->
            <Card class="p-0 overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="locations"
                >
                    <template #cell-name="{ row }">
                        <div class="py-2">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-900 dark:text-white text-sm">
                                    {{ row.name }}
                                </span>
                                <span v-if="row.is_default" class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200">
                                    DEFAULT
                                </span>
                            </div>
                            <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                Code: {{ row.code || '—' }} <span v-if="row.address">• {{ row.address }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-type="{ row }">
                        <span class="capitalize px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                            {{ row.type }}
                        </span>
                    </template>

                    <template #cell-branch="{ row }">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">
                            {{ row.branch_name || 'All Branches / Head Office' }}
                        </span>
                    </template>

                    <template #cell-stock="{ row }">
                        <span class="font-mono text-xs font-semibold text-zinc-900 dark:text-white">
                            {{ (row.total_stock_count || 0).toLocaleString() }} units
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
                        <button
                            v-if="canManage"
                            type="button"
                            @click="openEditModal(row)"
                            class="text-xs text-indigo-600 hover:underline"
                        >
                            Edit
                        </button>
                    </template>
                </DataTable>
            </Card>

            <!-- Quick Modal -->
            <Modal
                :show="showModal"
                :title="editingLocation ? 'Edit Storage Location' : 'Create Storage Location'"
                @close="showModal = false"
            >
                <form @submit.prevent="saveLocation" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Location Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Main Central Warehouse, Storefront Floor, Cold Room"
                            required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Location Code <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="e.g. WH-01, STORE-01"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Location Type
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                            >
                                <option value="warehouse">Warehouse (Bulk Storage)</option>
                                <option value="store">Store / Retail Shelf</option>
                                <option value="kitchen">Kitchen / Preparation</option>
                                <option value="transit">In-Transit Hub</option>
                                <option value="returns">Quarantine / Returns</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Physical Address / Notes
                        </label>
                        <input
                            v-model="form.address"
                            type="text"
                            placeholder="Address, building, or specific zone..."
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_default"
                                class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                            />
                            <span>Set as Default Fulfillment Location</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <Button type="button" variant="ghost" size="sm" @click="showModal = false">
                                Cancel
                            </Button>
                            <Button type="submit" variant="primary" size="sm" :disabled="form.processing">
                                {{ editingLocation ? 'Update' : 'Create' }}
                            </Button>
                        </div>
                    </div>
                </form>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
