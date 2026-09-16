<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, Select, DatePicker, type SelectOption } from '@/components';

interface LocationItem {
    id: number;
    name: string;
    branch_name?: string;
}

interface AdjustmentLine {
    id?: number | string;
    item_id: number | '';
    item_name?: string;
    item_sku?: string;
    system_qty: number;
    counted_qty: number;
    unit_cost: number;
    unit_name?: string;
    reason_code: string;
}

interface AvailableProduct {
    id: number;
    name: string;
    sku: string;
    unit?: string;
    on_hand?: number;
    cost_price?: number;
}

const props = defineProps<{
    locations: LocationItem[];
    products?: AvailableProduct[];
    initialItemId?: string | number;
}>();

import { useCurrency } from '@/composables/useCurrency';

const { currencyCode } = useCurrency();
const currency = computed(() => currencyCode.value);

const availableItems = computed<AvailableProduct[]>(() => {
    if (props.products && props.products.length > 0) return props.products;
    return [
        { id: 1, name: 'Espresso Arabica Coffee Beans', sku: 'COF-ARA-001', unit: 'Kg', on_hand: 145, cost_price: 75.00 },
        { id: 2, name: 'Fresh Whole Milk 1L', sku: 'MLK-FRE-002', unit: 'Litre', on_hand: 68, cost_price: 6.50 },
        { id: 3, name: 'Takeaway Cup 12oz', sku: 'CUP-12OZ-003', unit: 'Pcs', on_hand: 520, cost_price: 0.35 },
    ];
});

const form = useForm({
    location_id: '' as number | '',
    adjustment_date: new Date().toISOString().slice(0, 10),
    reference: `ADJ-${Date.now().toString().slice(-6)}`,
    notes: '',
    items: [
        {
            id: Date.now(),
            item_id: props.initialItemId ? Number(props.initialItemId) : ('' as any),
            system_qty: 100,
            counted_qty: 98,
            unit_cost: 10,
            unit_name: 'Pcs',
            reason_code: 'physical_variance',
        },
    ] as AdjustmentLine[],
});

const addAdjustmentLine = () => {
    form.items.push({
        id: Date.now(),
        item_id: '',
        system_qty: 0,
        counted_qty: 0,
        unit_cost: 0,
        unit_name: 'Pcs',
        reason_code: 'physical_variance',
    });
};

const removeAdjustmentLine = (index: number) => {
    form.items.splice(index, 1);
};

const onItemChange = (line: AdjustmentLine) => {
    const prod = availableItems.value.find(p => p.id === line.item_id);
    if (prod) {
        line.item_name = prod.name;
        line.item_sku = prod.sku;
        line.unit_name = prod.unit || 'Pcs';
        line.system_qty = prod.on_hand || 0;
        line.counted_qty = prod.on_hand || 0;
        line.unit_cost = prod.cost_price || 0;
    }
};

const totalNetVarianceValue = computed(() => {
    return form.items.reduce((sum, line) => {
        const variance = (line.counted_qty || 0) - (line.system_qty || 0);
        return sum + (variance * (line.unit_cost || 0));
    }, 0);
});

const locationOptions = computed<SelectOption[]>(() =>
    props.locations.map((l) => ({
        label: l.branch_name ? `${l.name} (${l.branch_name})` : l.name,
        value: l.id,
    })),
);

const itemOptions = computed<SelectOption[]>(() =>
    availableItems.value.map((p) => ({
        label: `${p.name} (${p.sku})`,
        value: p.id,
    })),
);

const reasonOptions: SelectOption[] = [
    { label: 'Physical Count Variance', value: 'physical_variance' },
    { label: 'Damaged / Broken', value: 'damaged' },
    { label: 'Expired / Spoilage', value: 'expired' },
    { label: 'Theft / Shrinkage', value: 'theft' },
    { label: 'Opening Balance Adjustment', value: 'opening_balance' },
    { label: 'Internal Office / Kitchen Use', value: 'internal_use' },
];

const submit = () => {
    form.post('/admin/inventory/adjustments', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Stock Count & Adjustment" />

        <div class="space-y-6 max-w-5xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Stock Adjustment</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Physical Stock Count & Variance Adjustment
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Reconcile physical stock with system counts, write-off damaged goods, or adjust opening balances.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/inventory/items">
                        <Button variant="ghost" size="sm">Cancel</Button>
                    </Link>
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{ form.processing ? 'Posting...' : 'Post Adjustment' }}
                    </Button>
                </div>
            </div>

            <!-- Adjustment Header Form -->
            <Card class="p-6">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                    Adjustment Details
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <Select
                            v-model="form.location_id"
                            label="Storage Location"
                            :options="locationOptions"
                            placeholder="Select location being counted..."
                            required
                            size="sm"
                        />
                    </div>

                    <div>
                        <DatePicker
                            v-model="form.adjustment_date"
                            label="Count Date"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Reference Number
                        </label>
                        <input
                            v-model="form.reference"
                            type="text"
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white font-mono"
                        />
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Adjustment Description / Audit Notes
                    </label>
                    <input
                        v-model="form.notes"
                        type="text"
                        placeholder="e.g. End of month physical inventory cycle count"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                    />
                </div>
            </Card>

            <!-- Line Items Table -->
            <Card class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                            Counted Items Variance Matrix ({{ form.items.length }})
                        </h3>
                        <p class="text-xs text-zinc-500">
                            Net Cost Variance: <strong :class="totalNetVarianceValue >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ currency }} {{ totalNetVarianceValue.toFixed(2) }}</strong>
                        </p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addAdjustmentLine">
                        + Add Item Line
                    </Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                                <th class="pb-3 px-2">Item</th>
                                <th class="pb-3 px-2 text-right">System Qty</th>
                                <th class="pb-3 px-2">Actual Count</th>
                                <th class="pb-3 px-2 text-right">Variance</th>
                                <th class="pb-3 px-2">Reason Code</th>
                                <th class="pb-3 px-2 text-right">Variance Impact</th>
                                <th class="pb-3 px-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="(line, idx) in form.items" :key="line.id || idx">
                                <td class="py-2.5 px-2 min-w-[200px]">
                                    <Select
                                        v-model="line.item_id"
                                        :options="itemOptions"
                                        placeholder="Select item..."
                                        size="sm"
                                        @change="onItemChange(line)"
                                    />
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono text-zinc-500">
                                    {{ line.system_qty }}
                                </td>
                                <td class="py-2.5 px-2">
                                    <input
                                        v-model.number="line.counted_qty"
                                        type="number"
                                        min="0"
                                        class="w-20 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 dark:text-white font-mono font-bold"
                                    />
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold">
                                    <span :class="((line.counted_qty || 0) - (line.system_qty || 0)) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        {{ ((line.counted_qty || 0) - (line.system_qty || 0)) >= 0 ? '+' : '' }}{{ (line.counted_qty || 0) - (line.system_qty || 0) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-2">
                                    <Select
                                        v-model="line.reason_code"
                                        :options="reasonOptions"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold">
                                    {{ currency }} {{ (((line.counted_qty || 0) - (line.system_qty || 0)) * (line.unit_cost || 0)).toFixed(2) }}
                                </td>
                                <td class="py-2.5 px-2 text-right">
                                    <button
                                        type="button"
                                        @click="removeAdjustmentLine(idx)"
                                        class="text-rose-600 hover:underline"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </OrganizationLayout>
</template>
