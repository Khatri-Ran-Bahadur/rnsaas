<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Select, DatePicker, type SelectOption } from '@/components';
import { useCurrency } from '@/composables/useCurrency';
import { getTaxTerminology } from '@/utils/taxTerminology';

interface CustomerOption {
    id: number;
    public_id: string;
    customer_code: string;
    name: string;
    payment_terms_days: number;
}

interface AccountOption {
    id: number;
    code: string;
    name: string;
}

interface ItemOption {
    id: number;
    name: string;
    sku: string;
    selling_price: number;
    cost_price: number;
    on_hand_stock: number;
    tax_rate?: number;
}

interface TaxRateOption {
    id: number;
    name: string;
    code: string;
    rate: number;
    rate_type?: string;
}

interface Props {
    customers: CustomerOption[];
    accounts: AccountOption[];
    suggestedInvoiceNumber: string;
    items?: ItemOption[];
    taxRates?: TaxRateOption[];
    taxSettings?: any;
}

const props = withDefaults(defineProps<Props>(), {
    items: () => [],
    taxRates: () => [],
    taxSettings: null,
});

interface InvoiceLineItem {
    item_id?: number | '';
    description: string;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    tax_rate: number;
    tax_amount: number;
    subtotal: number;
    total: number;
    revenue_account_id: number | '';
}

const defaultAccount = props.accounts.length > 0 ? props.accounts[0].id : '';

const lines = ref<InvoiceLineItem[]>([
    {
        item_id: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_amount: 0,
        tax_rate: 0,
        tax_amount: 0,
        subtotal: 0,
        total: 0,
        revenue_account_id: defaultAccount,
    },
]);

const page = usePage();
const tenant = computed(() => (page.props as any).current_tenant || {});
const terms = computed(() => getTaxTerminology(tenant.value.country_code, tenant.value.tax_regime));
const { currencyCode, currencySymbol, formatMoney } = useCurrency();
const defaultCurrency = computed(() => tenant.value.currency || currencyCode.value || 'USD');

const isTaxInclusive = ref(props.taxSettings?.default_pricing_mode === 'inclusive');

const today = new Date().toISOString().split('T')[0];

const form = useForm({
    customer_id: '' as number | '',
    invoice_number: props.suggestedInvoiceNumber,
    invoice_date: today,
    due_date: today,
    currency: defaultCurrency.value,
    reference: '',
    notes: '',
    lines: [] as any[],
});

const customerOptions = computed<SelectOption[]>(() => {
    return props.customers.map((c) => ({
        label: `${c.name} (${c.customer_code})`,
        value: c.id,
    }));
});

const accountOptions = computed<SelectOption[]>(() => {
    return props.accounts.map((a) => ({
        label: `${a.code} - ${a.name}`,
        value: a.id,
    }));
});

const itemOptions = computed<SelectOption[]>(() => {
    return [
        { label: '-- Custom Item / None --', value: '' },
        ...props.items.map((item) => ({
            label: item.name,
            sublabel: `${item.sku ? `[${item.sku}] ` : ''}${form.currency || defaultCurrency.value} ${Number(item.selling_price || 0).toFixed(2)}${item.on_hand_stock !== undefined ? ` • Stock: ${item.on_hand_stock}` : ''}`,
            value: item.id,
        })),
    ];
});

// Watch customer selection to update due date based on payment terms
watch(() => form.customer_id, (newVal) => {
    if (!newVal) return;
    const cust = props.customers.find((c) => c.id === Number(newVal));
    if (cust && cust.payment_terms_days && form.invoice_date) {
        const d = new Date(form.invoice_date);
        d.setDate(d.getDate() + cust.payment_terms_days);
        form.due_date = d.toISOString().split('T')[0];
    }
});

const onItemSelect = (line: InvoiceLineItem, selectedId: any) => {
    const id = Number(selectedId);
    line.item_id = id || '';
    if (!id) return;
    const item = props.items.find((i) => i.id === id);
    if (item) {
        line.item_id = item.id;
        line.description = item.name + (item.sku ? ` (${item.sku})` : '');
        line.unit_price = Number(item.selling_price || 0);
        if (item.tax_rate !== undefined && item.tax_rate !== null) {
            line.tax_rate = Number(item.tax_rate);
        }
        calculateLine(line);
    }
};

const calculateLine = (line: InvoiceLineItem) => {
    const qty = Number(line.quantity) || 0;
    const price = Number(line.unit_price) || 0;
    const discount = Number(line.discount_amount) || 0;
    const taxRate = Number(line.tax_rate) || 0;

    const rawAmount = Math.max(0, (qty * price) - discount);

    if (isTaxInclusive.value && taxRate > 0) {
        const baseAmount = rawAmount / (1 + (taxRate / 100));
        const taxAmount = rawAmount - baseAmount;
        line.subtotal = Number(baseAmount.toFixed(4));
        line.tax_amount = Number(taxAmount.toFixed(4));
        line.total = Number(rawAmount.toFixed(4));
    } else {
        const baseAmount = rawAmount;
        const taxAmount = (baseAmount * taxRate) / 100;
        const total = baseAmount + taxAmount;
        line.subtotal = Number(baseAmount.toFixed(4));
        line.tax_amount = Number(taxAmount.toFixed(4));
        line.total = Number(total.toFixed(4));
    }
};

watch(isTaxInclusive, () => {
    lines.value.forEach((l) => calculateLine(l));
});

const addLine = () => {
    lines.value.push({
        item_id: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_amount: 0,
        tax_rate: 0,
        tax_amount: 0,
        subtotal: 0,
        total: 0,
        revenue_account_id: defaultAccount,
    });
};

const removeLine = (index: number) => {
    if (lines.value.length === 1) return;
    lines.value.splice(index, 1);
};

const totals = computed(() => {
    let subtotal = 0;
    let discountTotal = 0;
    let taxTotal = 0;
    let grandTotal = 0;

    for (const l of lines.value) {
        calculateLine(l);
        const qty = Number(l.quantity) || 0;
        const price = Number(l.unit_price) || 0;
        const disc = Number(l.discount_amount) || 0;

        subtotal += qty * price;
        discountTotal += disc;
        taxTotal += Number(l.tax_amount) || 0;
        grandTotal += Number(l.total) || 0;
    }

    return {
        subtotal: subtotal.toFixed(2),
        discountTotal: discountTotal.toFixed(2),
        taxTotal: taxTotal.toFixed(2),
        grandTotal: grandTotal.toFixed(2),
    };
});

const submit = () => {
    lines.value.forEach(calculateLine);
    form.lines = lines.value.map((l) => ({
        description: l.description,
        quantity: l.quantity,
        unit_price: l.unit_price,
        discount_amount: l.discount_amount,
        tax_rate: l.tax_rate,
        tax_amount: l.tax_amount,
        subtotal: l.subtotal,
        total: l.total,
        revenue_account_id: l.revenue_account_id,
    }));

    form.post('/admin/accounting/invoices');
};
</script>

<template>
    <Head title="Create Sales Invoice - Accounting" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/accounting/invoices"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; Sales Invoices
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Create Sales Invoice
                    </h1>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Invoice Info Card -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Invoice Details</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Customer Select -->
                        <div class="sm:col-span-2">
                            <Select
                                v-model="form.customer_id"
                                label="Customer"
                                :options="customerOptions"
                                placeholder="Select customer..."
                                :searchable="true"
                                :required="true"
                                :error="form.errors.customer_id"
                            />
                        </div>

                        <!-- Invoice Number -->
                        <div class="space-y-1">
                            <label for="invoice_number" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Invoice Number <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="invoice_number"
                                v-model="form.invoice_number"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                required
                            />
                            <p v-if="form.errors.invoice_number" class="text-xs text-rose-500">{{ form.errors.invoice_number }}</p>
                        </div>

                        <!-- Currency -->
                        <div class="space-y-1">
                            <label for="currency" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Currency</label>
                            <input
                                id="currency"
                                v-model="form.currency"
                                type="text"
                                maxlength="3"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                required
                            />
                        </div>

                        <!-- Invoice Date (DatePicker) -->
                        <div>
                            <DatePicker
                                v-model="form.invoice_date"
                                label="Invoice Date"
                                :required="true"
                                :error="form.errors.invoice_date"
                            />
                        </div>

                        <!-- Due Date (DatePicker) -->
                        <div>
                            <DatePicker
                                v-model="form.due_date"
                                label="Due Date"
                                :required="true"
                                :error="form.errors.due_date"
                            />
                        </div>

                        <!-- Reference -->
                        <div class="sm:col-span-2 space-y-1">
                            <label for="reference" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Client PO / Reference #</label>
                            <input
                                id="reference"
                                v-model="form.reference"
                                type="text"
                                placeholder="e.g. PO-88219"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Line Items Card -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Invoice Items</h2>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">Add the billable goods or services.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1 p-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-[11px] font-medium">
                                <button
                                    type="button"
                                    @click="isTaxInclusive = false"
                                    :class="[
                                        'px-2.5 py-1 rounded-md transition-all',
                                        !isTaxInclusive
                                            ? 'bg-white dark:bg-zinc-900 shadow-xs text-indigo-600 dark:text-indigo-400 font-semibold'
                                            : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                    ]"
                                >
                                    Tax Exclusive
                                </button>
                                <button
                                    type="button"
                                    @click="isTaxInclusive = true"
                                    :class="[
                                        'px-2.5 py-1 rounded-md transition-all',
                                        isTaxInclusive
                                            ? 'bg-white dark:bg-zinc-900 shadow-xs text-purple-600 dark:text-purple-400 font-semibold'
                                            : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'
                                    ]"
                                >
                                    Tax Inclusive
                                </button>
                            </div>
                            <Button type="button" variant="outline" size="sm" @click="addLine">
                                + Add Line
                            </Button>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-zinc-400">
                            <thead class="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                <tr>
                                    <th class="px-3 py-2.5 w-56 min-w-[200px]">Item / Product</th>
                                    <th class="px-3 py-2.5 min-w-[180px]">Description <span class="text-rose-500">*</span></th>
                                    <th class="px-3 py-2.5 w-52 min-w-[170px]">Revenue Account <span class="text-rose-500">*</span></th>
                                    <th class="px-3 py-2.5 w-20 text-right">Qty</th>
                                    <th class="px-3 py-2.5 w-28 text-right">Unit Price</th>
                                    <th class="px-3 py-2.5 w-24 text-right">Discount</th>
                                    <th class="px-3 py-2.5 w-20 text-right">{{ terms.taxLabel }} (%)</th>
                                    <th class="px-3 py-2.5 w-28 text-right">Total</th>
                                    <th class="px-3 py-2.5 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                                <tr v-for="(line, index) in lines" :key="index" class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/40">
                                    <td class="px-3 py-2">
                                        <Select
                                            v-model="line.item_id"
                                            :options="itemOptions"
                                            placeholder="Select product..."
                                            :searchable="true"
                                            size="sm"
                                            @update:model-value="onItemSelect(line, $event)"
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input
                                            v-model="line.description"
                                            type="text"
                                            placeholder="Item description..."
                                            class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                            required
                                        />
                                    </td>
                                    <td class="px-3 py-2">
                                        <Select
                                            v-model="line.revenue_account_id"
                                            :options="accountOptions"
                                            placeholder="Account..."
                                            :searchable="true"
                                            :required="true"
                                            size="sm"
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            v-model="line.quantity"
                                            type="number"
                                            step="0.0001"
                                            min="0.0001"
                                            class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-right text-xs text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                            required
                                            @input="calculateLine(line)"
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            v-model="line.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-right text-xs text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                            required
                                            @input="calculateLine(line)"
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            v-model="line.discount_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-right text-xs text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                            @input="calculateLine(line)"
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            v-model="line.tax_rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-right text-xs text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                            @input="calculateLine(line)"
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                        {{ form.currency || currencyCode }} {{ Number(line.total || 0).toFixed(2) }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button
                                            v-if="lines.length > 1"
                                            type="button"
                                            class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-400"
                                            @click="removeLine(index)"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals summary -->
                    <div class="mt-6 flex flex-col items-end gap-1.5 border-t border-slate-200 pt-4 text-xs text-slate-600 dark:border-zinc-800 dark:text-zinc-400">
                        <div class="flex w-64 justify-between">
                            <span>Subtotal:</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ form.currency || currencyCode }} {{ totals.subtotal }}</span>
                        </div>
                        <div class="flex w-64 justify-between">
                            <span>Discount Total:</span>
                            <span class="font-medium text-rose-500">-{{ form.currency || currencyCode }} {{ totals.discountTotal }}</span>
                        </div>
                        <div class="flex w-64 justify-between">
                            <span>{{ terms.taxLabel }} Total:</span>
                            <span class="font-medium text-slate-900 dark:text-white">+{{ form.currency || currencyCode }} {{ totals.taxTotal }}</span>
                        </div>
                        <div class="flex w-64 justify-between border-t border-slate-200 pt-2 text-sm font-bold text-slate-900 dark:border-zinc-800 dark:text-white">
                            <span>Grand Total:</span>
                            <span class="text-indigo-600 dark:text-indigo-400">{{ form.currency || currencyCode }} {{ totals.grandTotal }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes Card -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Notes / Payment Instructions</label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Thank you for your business. Payment terms and bank transfer info..."
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    ></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link href="/admin/accounting/invoices">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Draft Invoice' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
