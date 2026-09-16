<script setup lang="ts">
import { computed } from 'vue';
import { TextInput, Select, Card, Badge, MediaPicker } from '@/components';

interface Category {
    id: number;
    name: string;
    parent_id?: number | null;
}

interface Brand {
    id: number | string;
    name: string;
}

interface Branch {
    id: number | string;
    name: string;
    code?: string;
}

const props = defineProps<{
    form: {
        name: string;
        sku: string;
        item_code: string;
        type: 'stock' | 'service' | 'non_stock';
        category_id: number | '';
        subcategory_id: number | '';
        branch_id?: number | '';
        brand_id?: number | string | '';
        brand_name?: string;
        manufacturer: string;
        status: 'active' | 'draft' | 'archived';
        description: string;
        internal_notes: string;
        image_url: string | null;
    };
    categories: Category[];
    brands?: Brand[];
    branches?: Branch[];
    brandSuggestions?: string[];
}>();

const itemTypes = [
    {
        value: 'stock',
        label: 'Stock Item',
        tag: 'Physical Product',
        tagColor: 'emerald',
        description: 'Physical product with tracked inventory balance, warehouses, reorder levels, batch/lot numbers, or serial tracking (e.g. burgers, drinks, t-shirts, electronics).',
        icon: 'cube',
    },
    {
        value: 'service',
        label: 'Service',
        tag: 'Intangible / Billable',
        tagColor: 'blue',
        description: 'Billable time, consulting, labour, delivery charge, or professional services with no physical stock or warehouse tracking.',
        icon: 'briefcase',
    },
    {
        value: 'non_stock',
        label: 'Non-Stock Item',
        tag: 'Consumable / Misc',
        tagColor: 'amber',
        description: 'Consumable materials, cleaning supplies, packaging, or direct-expense items purchased and sold without inventory valuation or stock tracking.',
        icon: 'archive',
    },
];

const parentCategories = computed(() => {
    return props.categories.filter((c) => !c.parent_id);
});

const subcategories = computed(() => {
    if (!props.form.category_id) return [];
    return props.categories.filter((c) => c.parent_id === Number(props.form.category_id));
});

const categoryOptions = computed(() => {
    return parentCategories.value.map((c) => ({
        label: c.name,
        value: c.id,
    }));
});

const subcategoryOptions = computed(() => {
    return subcategories.value.map((c) => ({
        label: c.name,
        value: c.id,
    }));
});

const brandOptions = computed(() => {
    return (props.brands || []).map((b) => ({
        label: b.name,
        value: b.id,
    }));
});

const branchOptions = computed(() => {
    return (props.branches || []).map((b) => ({
        label: b.code ? `${b.name} (${b.code})` : b.name,
        value: b.id,
    }));
});

const statusOptions = [
    { label: 'Active (Available for sales & purchase)', value: 'active' },
    { label: 'Draft (In preparation, hidden from POS)', value: 'draft' },
    { label: 'Archived / Inactive', value: 'archived' },
];

const generateSku = () => {
    const prefix = props.form.type === 'service' ? 'SRV' : (props.form.type === 'non_stock' ? 'NON' : 'SKU');
    const random = Math.floor(10000 + Math.random() * 90000);
    props.form.sku = `${prefix}-${random}`;
};
</script>

<template>
    <div class="space-y-6">
        <!-- 1. Item Classification Card -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Item Type & Classification
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                        Select how this item behaves across inventory, point of sale, purchase, and financial workflows.
                    </p>
                </div>
                <Badge :variant="form.type === 'stock' ? 'success' : (form.type === 'service' ? 'info' : 'warning')">
                    {{ form.type === 'stock' ? 'Physical Stock' : (form.type === 'service' ? 'Service / Labor' : 'Non-Stock Item') }}
                </Badge>
            </div>

            <!-- Radio-Card Grid for Item Type Selection -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label
                    v-for="t in itemTypes"
                    :key="t.value"
                    :class="[
                        'relative flex flex-col justify-between p-4 rounded-xl border-2 cursor-pointer transition-all duration-150 select-none',
                        form.type === t.value
                            ? 'border-indigo-600 bg-indigo-50/40 dark:border-indigo-500 dark:bg-indigo-950/20 shadow-xs'
                            : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 hover:border-slate-300 dark:hover:border-zinc-700',
                    ]"
                >
                    <input
                        v-model="form.type"
                        type="radio"
                        :value="t.value"
                        class="sr-only"
                    />
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-slate-900 dark:text-white">
                                {{ t.label }}
                            </span>
                            <span
                                class="h-4 w-4 rounded-full border flex items-center justify-center"
                                :class="form.type === t.value ? 'border-indigo-600 bg-indigo-600 dark:border-indigo-400' : 'border-slate-300 dark:border-zinc-700'"
                            >
                                <span v-if="form.type === t.value" class="h-1.5 w-1.5 rounded-full bg-white"></span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-zinc-400 mt-2 leading-relaxed">
                            {{ t.description }}
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <!-- 2. Primary Information Card -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 shadow-xs">
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">
                Basic Master Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Item Name -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Item Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="e.g. Chicken Burger Deluxe / Coca Cola 320ml / Web Development Consultation"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                        required
                    />
                </div>

                <!-- SKU with Auto-generate -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider">
                            SKU (Stock Keeping Unit) <span class="text-red-500">*</span>
                        </label>
                        <button
                            type="button"
                            class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 cursor-pointer flex items-center gap-1"
                            @click="generateSku"
                        >
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Auto Generate
                        </button>
                    </div>
                    <div class="relative">
                        <input
                            v-model="form.sku"
                            type="text"
                            placeholder="e.g. BGR-001"
                            class="w-full font-mono rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            required
                        />
                    </div>
                </div>

                <!-- Internal Item Code -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Internal Item Code
                    </label>
                    <input
                        v-model="form.item_code"
                        type="text"
                        placeholder="e.g. ITM-2026-99"
                        class="w-full font-mono rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                    />
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <Select
                        v-model="form.category_id"
                        :options="categoryOptions"
                        placeholder="Select primary category..."
                        :searchable="true"
                    />
                </div>

                <!-- Subcategory -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Subcategory (Optional)
                    </label>
                    <Select
                        v-model="form.subcategory_id"
                        :options="subcategoryOptions"
                        placeholder="Select subcategory..."
                        :disabled="!form.category_id || subcategoryOptions.length === 0"
                    />
                </div>

                <!-- Branch (शाखा) Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Branch (शाखा)
                    </label>
                    <Select
                        v-model="form.branch_id"
                        :options="branchOptions"
                        placeholder="Select branch (Optional)..."
                        :searchable="true"
                    />
                </div>

                <!-- Brand / Manufacturer -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Brand / Manufacturer
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.brand_name"
                            type="text"
                            placeholder="e.g. Apple, Samsung, Unilever, Local..."
                            list="item-brand-suggestions"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                        />
                        <datalist id="item-brand-suggestions">
                            <option v-for="b in (brandSuggestions || [])" :key="b" :value="b" />
                        </datalist>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Status
                    </label>
                    <Select
                        v-model="form.status"
                        :options="statusOptions"
                    />
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Public Description (Shows on POS / Invoices / Quotes)
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Enter item description, customer-facing notes, specifications, or allergen notices..."
                        class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                    ></textarea>
                </div>

                <!-- Internal Notes -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                        Internal Staff Notes (Private)
                    </label>
                    <textarea
                        v-model="form.internal_notes"
                        rows="2"
                        placeholder="Internal notes, supplier contact details, special handling instructions..."
                        class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>
</template>
