<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn, Modal } from '@/components';
import { usePermissions } from '@/composables/usePermissions';

interface UnitItem {
    id: number | string;
    name: string;
    code: string;
    unit_category: 'count' | 'weight' | 'volume' | 'length' | 'time' | 'area';
    is_base: boolean;
    precision: number;
}

interface UnitConversion {
    id: number | string;
    from_unit_name: string;
    to_unit_name: string;
    conversion_factor: number;
    description?: string;
}

const props = defineProps<{
    units: UnitItem[];
    conversions?: UnitConversion[];
}>();

const { can, isAdmin } = usePermissions();
const canManage = computed(() => isAdmin.value || can('inventory.manage') || can('inventory.units.manage'));

const showUnitModal = ref(false);
const editingUnit = ref<UnitItem | null>(null);

const unitForm = useForm({
    name: '',
    code: '',
    unit_category: 'count' as 'count' | 'weight' | 'volume' | 'length' | 'time' | 'area',
    precision: 0,
});

const openCreateUnit = () => {
    editingUnit.value = null;
    unitForm.reset();
    showUnitModal.value = true;
};

const saveUnit = () => {
    unitForm.post('/admin/inventory/units', {
        onSuccess: () => {
            showUnitModal.value = false;
        },
    });
};

const columns: TableColumn[] = [
    { key: 'name', label: 'Unit Name' },
    { key: 'code', label: 'Symbol / Code' },
    { key: 'category', label: 'Category' },
    { key: 'precision', label: 'Decimal Precision', align: 'center' },
];
</script>

<template>
    <OrganizationLayout>
        <Head title="Units of Measure (UOM)" />

        <div class="space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Units of Measure</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Units of Measure & Multi-UOM Conversions
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Define discrete pieces, mass, volume, cartons, and global conversion multiplier formulas.
                    </p>
                </div>

                <Button
                    v-if="canManage"
                    variant="primary"
                    size="sm"
                    @click="openCreateUnit"
                >
                    + Add Unit
                </Button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Units Table (2 cols) -->
                <div class="lg:col-span-2">
                    <Card class="p-0 overflow-hidden">
                        <DataTable
                            :columns="columns"
                            :data="units"
                        >
                            <template #cell-name="{ row }">
                                <div class="py-2">
                                    <span class="font-semibold text-zinc-900 dark:text-white text-sm">
                                        {{ row.name }}
                                    </span>
                                </div>
                            </template>

                            <template #cell-code="{ row }">
                                <span class="font-mono text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-bold">
                                    {{ row.code }}
                                </span>
                            </template>

                            <template #cell-category="{ row }">
                                <span class="capitalize text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ row.unit_category }}
                                </span>
                            </template>

                            <template #cell-precision="{ row }">
                                <span class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                    {{ row.precision }} decimals
                                </span>
                            </template>
                        </DataTable>
                    </Card>
                </div>

                <!-- Global Conversion Rules (1 col) -->
                <div class="space-y-4">
                    <Card class="p-5">
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-3">
                            Standard Conversion Multipliers
                        </h4>
                        <p class="text-xs text-zinc-500 mb-4">
                            Automatic conversion rules applied across Purchasing, Stock Valuation, and Invoicing.
                        </p>

                        <div class="space-y-2.5 text-xs">
                            <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between font-mono">
                                <span>1 Carton (ctn)</span>
                                <span class="text-zinc-400">=</span>
                                <span class="font-bold text-zinc-900 dark:text-white">24 Pieces (pcs)</span>
                            </div>

                            <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between font-mono">
                                <span>1 Box (box)</span>
                                <span class="text-zinc-400">=</span>
                                <span class="font-bold text-zinc-900 dark:text-white">12 Pieces (pcs)</span>
                            </div>

                            <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between font-mono">
                                <span>1 Kilogram (kg)</span>
                                <span class="text-zinc-400">=</span>
                                <span class="font-bold text-zinc-900 dark:text-white">1,000 Grams (g)</span>
                            </div>

                            <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between font-mono">
                                <span>1 Litre (L)</span>
                                <span class="text-zinc-400">=</span>
                                <span class="font-bold text-zinc-900 dark:text-white">1,000 Millilitres (ml)</span>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Create Unit Modal -->
            <Modal
                :show="showUnitModal"
                title="Create Unit of Measure"
                @close="showUnitModal = false"
            >
                <form @submit.prevent="saveUnit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Unit Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="unitForm.name"
                            type="text"
                            placeholder="e.g. Kilogram, Piece, Box, Litre"
                            required
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Code / Symbol <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="unitForm.code"
                                type="text"
                                placeholder="e.g. kg, pcs, box"
                                required
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                                Category
                            </label>
                            <select
                                v-model="unitForm.unit_category"
                                class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                            >
                                <option value="count">Count / Quantity</option>
                                <option value="weight">Mass / Weight</option>
                                <option value="volume">Volume / Liquid</option>
                                <option value="length">Length / Distance</option>
                                <option value="time">Time / Duration</option>
                                <option value="area">Area</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                            Decimal Display Precision
                        </label>
                        <input
                            v-model.number="unitForm.precision"
                            type="number"
                            min="0"
                            max="4"
                            placeholder="0 for integers, 2-3 for weight"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white font-mono"
                        />
                        <p class="text-[11px] text-zinc-400 mt-1">
                            Use 0 for discrete units (Pcs, Box). Use 2 or 3 for fractional quantities (Kg, Litre).
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <Button type="button" variant="ghost" size="sm" @click="showUnitModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" size="sm" :disabled="unitForm.processing">
                            Create Unit
                        </Button>
                    </div>
                </form>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
