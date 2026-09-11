<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Select, DatePicker, type SelectOption } from '@/components';

interface VendorItem {
    id: number;
    vendor_code: string;
    name: string;
    payment_terms_days: number;
    payable_account_id: number | null;
    currency?: string;
}

interface AccountItem {
    id: number;
    code: string;
    name: string;
}

interface ExistingLine {
    id?: number;
    line_number: number;
    description: string;
    quantity: string | number;
    unit_price: string | number;
    discount_amount: string | number;
    tax_rate: string | number;
    subtotal: string | number;
    total: string | number;
    debit_account_id: number;
    tax_account_id?: number | null;
}

interface BillData {
    id: number;
    vendor_id: number;
    bill_number: string;
    bill_date: string;
    due_date: string;
    currency: string;
    reference?: string | null;
    notes?: string | null;
    lines: ExistingLine[];
}

interface Props {
    bill: BillData;
    vendors: VendorItem[];
    accounts: AccountItem[];
}

const props = defineProps<Props>();

interface BillLineItem {
    line_number: number;
    description: string;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    tax_rate: number;
    debit_account_id: number | '';
    tax_account_id: number | '';
}

const lines = ref<BillLineItem[]>(
    props.bill.lines && props.bill.lines.length > 0
        ? props.bill.lines.map((l) => ({
            line_number: l.line_number,
            description: l.description,
            quantity: Number(l.quantity),
            unit_price: Number(l.unit_price),
            discount_amount: Number(l.discount_amount) || 0,
            tax_rate: Number(l.tax_rate) || 0,
            debit_account_id: l.debit_account_id,
            tax_account_id: l.tax_account_id ?? '',
        }))
        : [
            {
                line_number: 1,
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_amount: 0,
                tax_rate: 0,
                debit_account_id: props.accounts[0]?.id ?? '',
                tax_account_id: '',
            },
        ]
);

const form = useForm({
    vendor_id: props.bill.vendor_id ?? ('' as number | ''),
    bill_number: props.bill.bill_number ?? '',
    bill_date: props.bill.bill_date ? String(props.bill.bill_date).split('T')[0] : '',
    due_date: props.bill.due_date ? String(props.bill.due_date).split('T')[0] : '',
    currency: props.bill.currency ?? 'MYR',
    reference: props.bill.reference ?? '',
    notes: props.bill.notes ?? '',
    lines: [] as any[],
});

const vendorOptions = computed<SelectOption[]>(() => {
    return props.vendors.map((v) => ({
        label: `${v.vendor_code} - ${v.name}`,
        value: v.id,
    }));
});

const accountOptions = computed<SelectOption[]>(() => {
    return props.accounts.map((a) => ({
        label: `${a.code} - ${a.name}`,
        value: a.id,
    }));
});

watch(() => form.vendor_id, (newVendorId) => {
    const selected = props.vendors.find((v) => v.id === Number(newVendorId));
    if (selected) {
        form.currency = selected.currency || 'MYR';
    }
});

const addLine = () => {
    lines.value.push({
        line_number: lines.value.length + 1,
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_amount: 0,
        tax_rate: 0,
        debit_account_id: props.accounts[0]?.id ?? '',
        tax_account_id: '',
    });
};

const removeLine = (index: number) => {
    if (lines.value.length > 1) {
        lines.value.splice(index, 1);
        lines.value.forEach((line, idx) => {
            line.line_number = idx + 1;
        });
    }
};

const calculateLineSubtotal = (line: BillLineItem) => {
    return Math.max(0, (line.quantity * line.unit_price) - line.discount_amount);
};

const calculateLineTax = (line: BillLineItem) => {
    const subtotal = calculateLineSubtotal(line);
    return (subtotal * (line.tax_rate || 0)) / 100;
};

const calculateLineTotal = (line: BillLineItem) => {
    return calculateLineSubtotal(line) + calculateLineTax(line);
};

const totals = computed(() => {
    let subtotal = 0;
    let totalDiscount = 0;
    let totalTax = 0;

    lines.value.forEach((l) => {
        const rawSubtotal = l.quantity * l.unit_price;
        subtotal += rawSubtotal;
        totalDiscount += Number(l.discount_amount) || 0;
        totalTax += calculateLineTax(l);
    });

    const netSubtotal = Math.max(0, subtotal - totalDiscount);
    const grandTotal = netSubtotal + totalTax;

    return {
        subtotal,
        totalDiscount,
        netSubtotal,
        totalTax,
        grandTotal,
    };
});

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(val);
};

const submit = () => {
    form.lines = lines.value.map((line) => {
        const subtotal = calculateLineSubtotal(line);
        const taxAmount = calculateLineTax(line);
        const total = subtotal + taxAmount;

        return {
            line_number: line.line_number,
            description: line.description,
            quantity: String(line.quantity),
            unit_price: String(line.unit_price),
            discount_amount: String(line.discount_amount),
            tax_rate: String(line.tax_rate),
            tax_amount: taxAmount.toFixed(4),
            subtotal: subtotal.toFixed(4),
            total: total.toFixed(4),
            debit_account_id: Number(line.debit_account_id),
            tax_account_id: line.tax_account_id ? Number(line.tax_account_id) : null,
        };
    });

    form.put(`/admin/accounting/purchase-bills/${props.bill.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Edit Purchase Bill"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Purchase Bills', href: '/admin/accounting/purchase-bills' },
            { label: bill.bill_number, href: `/admin/accounting/purchase-bills/${bill.id}` },
            { label: 'Edit' },
        ]"
    >
        <Head :title="`Edit ${bill.bill_number} - Accounting`" />

        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Edit Purchase Bill: {{ bill.bill_number }}
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Modify draft bill lines, amounts, or supplier details before issuing.
                    </p>
                </div>

                <Link :href="`/admin/accounting/purchase-bills/${bill.id}`">
                    <Button variant="secondary" size="sm">
                        Cancel
                    </Button>
                </Link>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <!-- Bill Header Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        Bill Details
                    </h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <Select
                                v-model="form.vendor_id"
                                label="Vendor / Supplier"
                                :options="vendorOptions"
                                placeholder="Select vendor..."
                                :searchable="true"
                                :required="true"
                                :error="form.errors.vendor_id"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Bill Number <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.bill_number"
                                type="text"
                                required
                                class="mt-1.5 w-full font-mono rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.bill_number" class="mt-1 text-xs text-rose-500">{{ form.errors.bill_number }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Reference # / Supplier Inv #
                            </label>
                            <input
                                v-model="form.reference"
                                type="text"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.reference" class="mt-1 text-xs text-rose-500">{{ form.errors.reference }}</p>
                        </div>

                        <div>
                            <DatePicker
                                v-model="form.bill_date"
                                label="Bill Date"
                                :required="true"
                                :error="form.errors.bill_date"
                            />
                        </div>

                        <div>
                            <DatePicker
                                v-model="form.due_date"
                                label="Due Date"
                                :required="true"
                                :error="form.errors.due_date"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Currency
                            </label>
                            <input
                                v-model="form.currency"
                                type="text"
                                maxlength="3"
                                class="mt-1.5 w-full uppercase rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.currency" class="mt-1 text-xs text-rose-500">{{ form.errors.currency }}</p>
                        </div>
                    </div>
                </div>

                <!-- Line Items Table Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Line Items & Expenses
                        </h2>
                        <Button type="button" variant="secondary" size="sm" @click="addLine">
                            + Add Line
                        </Button>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-zinc-200 text-[11px] font-semibold uppercase text-zinc-400 dark:border-zinc-800">
                                <tr>
                                    <th class="w-8 pb-2">#</th>
                                    <th class="min-w-[200px] pb-2">Description <span class="text-rose-500">*</span></th>
                                    <th class="min-w-[180px] pb-2">Expense / Debit Account <span class="text-rose-500">*</span></th>
                                    <th class="w-20 pb-2">Qty</th>
                                    <th class="w-28 pb-2">Unit Price</th>
                                    <th class="w-24 pb-2">Disc</th>
                                    <th class="w-20 pb-2">Tax %</th>
                                    <th class="w-28 pb-2 text-right">Line Total</th>
                                    <th class="w-10 pb-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <tr v-for="(line, index) in lines" :key="index" class="align-top">
                                    <td class="py-3 text-zinc-400 font-mono">{{ index + 1 }}</td>
                                    <td class="py-3 pr-2">
                                        <input
                                            v-model="line.description"
                                            type="text"
                                            required
                                            class="w-full rounded-md border border-zinc-300 bg-zinc-50 px-2.5 py-1.5 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                        />
                                    </td>
                                    <td class="py-3 pr-2 w-56">
                                        <Select
                                            v-model="line.debit_account_id"
                                            :options="accountOptions"
                                            placeholder="Select account..."
                                            :searchable="true"
                                            :required="true"
                                        />
                                    </td>
                                    <td class="py-3 pr-2">
                                        <input
                                            v-model.number="line.quantity"
                                            type="number"
                                            min="0.01"
                                            step="any"
                                            required
                                            class="w-full rounded-md border border-zinc-300 bg-zinc-50 px-2 py-1.5 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white font-mono"
                                        />
                                    </td>
                                    <td class="py-3 pr-2">
                                        <input
                                            v-model.number="line.unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="w-full rounded-md border border-zinc-300 bg-zinc-50 px-2 py-1.5 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white font-mono"
                                        />
                                    </td>
                                    <td class="py-3 pr-2">
                                        <input
                                            v-model.number="line.discount_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full rounded-md border border-zinc-300 bg-zinc-50 px-2 py-1.5 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white font-mono"
                                        />
                                    </td>
                                    <td class="py-3 pr-2">
                                        <input
                                            v-model.number="line.tax_rate"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            class="w-full rounded-md border border-zinc-300 bg-zinc-50 px-2 py-1.5 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white font-mono"
                                        />
                                    </td>
                                    <td class="py-3 text-right font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ formatCurrency(calculateLineTotal(line)) }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <button
                                            type="button"
                                            :disabled="lines.length <= 1"
                                            class="p-1 text-zinc-400 hover:text-rose-600 disabled:opacity-30 cursor-pointer"
                                            title="Delete line"
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

                    <p v-if="form.errors.lines" class="mt-2 text-xs text-rose-500">{{ form.errors.lines }}</p>
                </div>

                <!-- Notes & Summary -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <label class="block text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                            Notes & Memo
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            class="mt-2 w-full rounded-lg border border-zinc-300 bg-zinc-50 p-3 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                            Bill Summary
                        </h3>

                        <div class="mt-3 space-y-2 text-xs">
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Subtotal</span>
                                <span class="font-mono">{{ formatCurrency(totals.subtotal) }}</span>
                            </div>
                            <div v-if="totals.totalDiscount > 0" class="flex justify-between text-rose-600">
                                <span>Total Discount</span>
                                <span class="font-mono">-{{ formatCurrency(totals.totalDiscount) }}</span>
                            </div>
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Tax Amount</span>
                                <span class="font-mono">{{ formatCurrency(totals.totalTax) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-bold text-zinc-900 dark:border-zinc-800 dark:text-zinc-100">
                                <span>Total ({{ form.currency }})</span>
                                <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totals.grandTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="`/admin/accounting/purchase-bills/${bill.id}`">
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
