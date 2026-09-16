<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, Select, DatePicker, type SelectOption } from '@/components';

interface LocationItem {
    id: number;
    name: string;
    branch_name?: string;
}

interface TransferItemLine {
    id?: number | string;
    item_id: number | '';
    item_name?: string;
    item_sku?: string;
    quantity: number;
    available_at_source?: number;
    unit_name?: string;
}

interface AvailableProduct {
    id: number;
    name: string;
    sku: string;
    unit?: string;
    on_hand?: number;
}

const props = defineProps<{
    locations: LocationItem[];
    products?: AvailableProduct[];
    initialItemId?: string | number;
}>();

const availableItems = computed<AvailableProduct[]>(() => {
    if (props.products && props.products.length > 0) return props.products;
    return [
        { id: 1, name: 'Espresso Arabica Coffee Beans', sku: 'COF-ARA-001', unit: 'Kg', on_hand: 145 },
        { id: 2, name: 'Fresh Whole Milk 1L', sku: 'MLK-FRE-002', unit: 'Litre', on_hand: 68 },
        { id: 3, name: 'Takeaway Cup 12oz', sku: 'CUP-12OZ-003', unit: 'Pcs', on_hand: 520 },
        { id: 4, name: 'Vanilla Flavor Syrup', sku: 'SYR-VAN-004', unit: 'Bottle', on_hand: 24 },
    ];
});

const form = useForm({
    source_location_id: '' as number | '',
    destination_location_id: '' as number | '',
    transfer_date: new Date().toISOString().slice(0, 10),
    reference: `TR-${Date.now().toString().slice(-6)}`,
    notes: '',
    items: [
        {
            id: Date.now(),
            item_id: props.initialItemId ? Number(props.initialItemId) : ('' as any),
            quantity: 1,
            available_at_source: 100,
            unit_name: 'Pcs',
        },
    ] as TransferItemLine[],
});

const addItemLine = () => {
    form.items.push({
        id: Date.now(),
        item_id: '',
        quantity: 1,
        available_at_source: 0,
        unit_name: 'Pcs',
    });
};

const removeItemLine = (index: number) => {
    form.items.splice(index, 1);
};

const onItemChange = (line: TransferItemLine) => {
    const prod = availableItems.value.find(p => p.id === line.item_id);
    if (prod) {
        line.item_name = prod.name;
        line.item_sku = prod.sku;
        line.unit_name = prod.unit || 'Pcs';
        line.available_at_source = prod.on_hand || 0;
    }
};

const submit = () => {
    if (form.source_location_id === form.destination_location_id) {
        alert('Source and destination locations cannot be the same.');
        return;
    }
    form.post('/admin/inventory/transfers', {
        preserveScroll: true,
    });
};

const locationOptions = computed<SelectOption[]>(() =>
    props.locations.map((l) => ({
        label: l.branch_name ? `${l.name} (${l.branch_name})` : l.name,
        value: l.id,
    })),
);

const destinationOptions = computed<SelectOption[]>(() =>
    props.locations.map((l) => ({
        label: l.branch_name ? `${l.name} (${l.branch_name})` : l.name,
        value: l.id,
        disabled: l.id === form.source_location_id,
    })),
);

const itemOptions = computed<SelectOption[]>(() =>
    availableItems.value.map((p) => ({
        label: `${p.name} (${p.sku})`,
        value: p.id,
    })),
);
</script>

<template>
    <OrganizationLayout>
        <Head title="Inter-Location Stock Transfer" />

        <div class="space-y-6 max-w-5xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/inventory/items" class="hover:text-zinc-700 dark:hover:text-zinc-300">Inventory</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Stock Transfer</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Inter-Location Stock Transfer
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Transfer stock balances between central warehouses, branch stores, and prep stations.
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
                        {{ form.processing ? 'Dispatching...' : 'Dispatch Transfer' }}
                    </Button>
                </div>
            </div>

            <!-- Transfer Header Configuration -->
            <Card class="p-6">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                    Transfer Route & Metadata
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <Select
                            v-model="form.source_location_id"
                            label="Origin / Source Location"
                            :options="locationOptions"
                            placeholder="Select source location..."
                            required
                            size="sm"
                        />
                    </div>

                    <div>
                        <Select
                            v-model="form.destination_location_id"
                            label="Destination Location"
                            :options="destinationOptions"
                            placeholder="Select destination location..."
                            required
                            size="sm"
                        />
                    </div>

                    <div>
                        <DatePicker
                            v-model="form.transfer_date"
                            label="Transfer Date"
                        />
                    </div>

                    <!-- Reference Number -->
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
                        Transfer Reason / Notes
                    </label>
                    <input
                        v-model="form.notes"
                        type="text"
                        placeholder="e.g. Branch stock replenishment, Kitchen daily prep stock"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-primary-500 dark:text-white"
                    />
                </div>
            </Card>

            <!-- Line Items Table -->
            <Card class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider">
                        Items to Transfer ({{ form.items.length }})
                    </h3>
                    <Button type="button" variant="outline" size="sm" @click="addItemLine">
                        + Add Item Line
                    </Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold">
                                <th class="pb-3 px-2">Item / Product</th>
                                <th class="pb-3 px-2 text-right">Available at Source</th>
                                <th class="pb-3 px-2">Transfer Quantity</th>
                                <th class="pb-3 px-2">Unit</th>
                                <th class="pb-3 px-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="(line, idx) in form.items" :key="line.id || idx">
                                <td class="py-2.5 px-2 min-w-[240px]">
                                    <Select
                                        v-model="line.item_id"
                                        :options="itemOptions"
                                        placeholder="Select item..."
                                        size="sm"
                                        @change="onItemChange(line)"
                                    />
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold text-zinc-600 dark:text-zinc-400">
                                    {{ line.available_at_source || '—' }}
                                </td>
                                <td class="py-2.5 px-2">
                                    <input
                                        v-model.number="line.quantity"
                                        type="number"
                                        min="1"
                                        :max="line.available_at_source"
                                        class="w-24 px-2 py-1 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded focus:ring-1 focus:ring-primary-500 dark:text-white font-mono font-bold"
                                    />
                                </td>
                                <td class="py-2.5 px-2 text-zinc-500">
                                    {{ line.unit_name || 'Units' }}
                                </td>
                                <td class="py-2.5 px-2 text-right">
                                    <button
                                        type="button"
                                        @click="removeItemLine(idx)"
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
