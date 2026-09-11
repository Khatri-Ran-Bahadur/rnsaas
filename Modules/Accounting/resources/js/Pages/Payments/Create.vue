<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Select, DatePicker, type SelectOption } from '@/components';

interface CustomerOption {
    id: number;
    public_id: string;
    customer_code: string;
    name: string;
}

interface BankAccountOption {
    id: number;
    code: string;
    name: string;
}

interface OpenInvoiceOption {
    id: number;
    public_id: string;
    customer_id: number;
    invoice_number: string;
    due_date: string;
    grand_total: string;
    customer?: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    customers: CustomerOption[];
    bankAccounts: BankAccountOption[];
    openInvoices: OpenInvoiceOption[];
    suggestedPaymentNumber: string;
    selectedCustomerId?: string | number;
}

const props = defineProps<Props>();

const today = new Date().toISOString().split('T')[0];

const form = useForm({
    customer_id: (props.selectedCustomerId ? Number(props.selectedCustomerId) : '') as number | '',
    payment_number: props.suggestedPaymentNumber,
    payment_date: today,
    amount: '',
    bank_account_id: props.bankAccounts.length > 0 ? props.bankAccounts[0].id : '',
    payment_method: 'bank_transfer',
    reference: '',
    notes: '',
});

const customerOptions = computed<SelectOption[]>(() => {
    return props.customers.map((c) => ({
        label: `${c.name} (${c.customer_code})`,
        value: c.id,
    }));
});

const bankAccountOptions = computed<SelectOption[]>(() => {
    return props.bankAccounts.map((b) => ({
        label: `${b.code} - ${b.name}`,
        value: b.id,
    }));
});

const paymentMethodOptions = [
    { label: 'Bank Transfer / Wire', value: 'bank_transfer' },
    { label: 'Cash', value: 'cash' },
    { label: 'Cheque', value: 'cheque' },
    { label: 'Credit Card', value: 'credit_card' },
    { label: 'Other', value: 'other' },
];

const customerOpenInvoices = computed(() => {
    if (!form.customer_id) return [];
    return props.openInvoices.filter((inv) => inv.customer_id === Number(form.customer_id));
});

const totalOpenBalance = computed(() => {
    return customerOpenInvoices.value
        .reduce((sum, inv) => sum + parseFloat(inv.grand_total || '0'), 0)
        .toFixed(2);
});

const formatMoney = (val: string | number) => {
    return parseFloat(String(val || 0)).toFixed(2);
};

const submit = () => {
    form.post('/admin/accounting/payments');
};
</script>

<template>
    <Head title="Record Customer Receipt - Accounting" />

    <OrganizationLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/accounting/payments"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; Customer Receipts
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Record Customer Payment
                    </h1>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Receipt Details</h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Customer Select Component -->
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

                        <!-- Receipt Number -->
                        <div class="space-y-1">
                            <label for="payment_number" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Receipt Number <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="payment_number"
                                v-model="form.payment_number"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                required
                            />
                            <p v-if="form.errors.payment_number" class="text-xs text-rose-500">{{ form.errors.payment_number }}</p>
                        </div>

                        <!-- DatePicker Component -->
                        <div>
                            <DatePicker
                                v-model="form.payment_date"
                                label="Receipt Date"
                                :required="true"
                                :error="form.errors.payment_date"
                            />
                        </div>

                        <!-- Amount Received -->
                        <div class="space-y-1">
                            <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Amount Received ($) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                required
                            />
                            <p v-if="form.errors.amount" class="text-xs text-rose-500">{{ form.errors.amount }}</p>
                        </div>

                        <!-- Bank Account Select Component -->
                        <div>
                            <Select
                                v-model="form.bank_account_id"
                                label="Deposit To (Account)"
                                :options="bankAccountOptions"
                                placeholder="Select bank account..."
                                :searchable="true"
                                :required="true"
                                :error="form.errors.bank_account_id"
                            />
                        </div>

                        <!-- Payment Method Select Component -->
                        <div>
                            <Select
                                v-model="form.payment_method"
                                label="Payment Method"
                                :options="paymentMethodOptions"
                                placeholder="Select method..."
                                :error="form.errors.payment_method"
                            />
                        </div>

                        <!-- Reference -->
                        <div class="sm:col-span-2 space-y-1">
                            <label for="reference" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Reference / Transaction ID
                            </label>
                            <input
                                id="reference"
                                v-model="form.reference"
                                type="text"
                                placeholder="e.g. TXN-998273"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Open Invoices for Selected Customer -->
                <div v-if="form.customer_id && customerOpenInvoices.length > 0" class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Outstanding Invoices for Customer</h2>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">
                                You can allocate this receipt to these open invoices after saving.
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-400 dark:text-zinc-500">Total Open:</span>
                            <div class="text-base font-bold text-rose-600 dark:text-rose-400">${{ totalOpenBalance }}</div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-zinc-400">
                            <thead class="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                <tr>
                                    <th class="px-4 py-2.5">Invoice #</th>
                                    <th class="px-4 py-2.5">Due Date</th>
                                    <th class="px-4 py-2.5 text-right">Invoice Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                                <tr v-for="inv in customerOpenInvoices" :key="inv.id" class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/40">
                                    <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-white">{{ inv.invoice_number }}</td>
                                    <td class="px-4 py-2.5">{{ inv.due_date }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-slate-900 dark:text-white">${{ formatMoney(inv.grand_total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Notes Card -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Notes</label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        placeholder="Additional remarks or receipt details..."
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link href="/admin/accounting/payments">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Receipt' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
