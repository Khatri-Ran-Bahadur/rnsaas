<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Select, DatePicker, type SelectOption } from '@/components';

interface VendorItem {
    id: number;
    vendor_code: string;
    name: string;
    currency?: string;
}

interface AccountItem {
    id: number;
    code: string;
    name: string;
}

interface Props {
    vendors: VendorItem[];
    bankAccounts: AccountItem[];
}

const props = defineProps<Props>();

const today = new Date().toISOString().split('T')[0];

const form = useForm({
    vendor_id: '' as number | '',
    payment_number: `PAY-${new Date().getFullYear()}${String(new Date().getMonth() + 1).padStart(2, '0')}-${Math.floor(1000 + Math.random() * 9000)}`,
    payment_date: today,
    amount: '',
    currency: 'MYR',
    bank_account_id: (props.bankAccounts[0]?.id ?? '') as number | '',
    payment_method: 'bank_transfer',
    reference: '',
    notes: '',
});

const vendorOptions = computed<SelectOption[]>(() => {
    return props.vendors.map((v) => ({
        label: `${v.vendor_code} - ${v.name}`,
        value: v.id,
    }));
});

const bankAccountOptions = computed<SelectOption[]>(() => {
    return props.bankAccounts.map((a) => ({
        label: `${a.code} - ${a.name}`,
        value: a.id,
    }));
});

const paymentMethodOptions: SelectOption[] = [
    { label: 'Bank Transfer (GIRO / DuitNow)', value: 'bank_transfer' },
    { label: 'Cheque', value: 'cheque' },
    { label: 'Cash', value: 'cash' },
    { label: 'Credit Card', value: 'credit_card' },
];

watch(() => form.vendor_id, (newId) => {
    const v = props.vendors.find((item) => item.id === Number(newId));
    if (v && v.currency) {
        form.currency = v.currency;
    }
});

const submit = () => {
    form.post('/admin/accounting/vendor-payments', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Record Vendor Payment"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Vendor Payments', href: '/admin/accounting/vendor-payments' },
            { label: 'Record Payment' },
        ]"
    >
        <Head title="Record Vendor Payment - Accounting" />

        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Record Vendor Payment
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Record a payment disbursement from your bank or cash account to a supplier.
                    </p>
                </div>

                <Link href="/admin/accounting/vendor-payments">
                    <Button variant="secondary" size="sm">
                        Cancel
                    </Button>
                </Link>
            </div>

            <!-- Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        Payment Details
                    </h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Vendor Select -->
                        <div class="sm:col-span-2">
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

                        <!-- Payment Number -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Payment Number <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.payment_number"
                                type="text"
                                required
                                class="mt-1.5 w-full font-mono rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.payment_number" class="mt-1 text-xs text-rose-500">{{ form.errors.payment_number }}</p>
                        </div>

                        <!-- Payment Date (DatePicker) -->
                        <div>
                            <DatePicker
                                v-model="form.payment_date"
                                label="Payment Date"
                                :required="true"
                                :error="form.errors.payment_date"
                            />
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Amount <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <input
                                    v-model="form.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    placeholder="0.00"
                                    class="w-full font-mono rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                                />
                                <span class="absolute right-3 top-2 text-xs font-semibold text-zinc-400">{{ form.currency }}</span>
                            </div>
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-500">{{ form.errors.amount }}</p>
                        </div>

                        <!-- Disbursed From (Select) -->
                        <div>
                            <Select
                                v-model="form.bank_account_id"
                                label="Disbursed From (Bank / Cash Account)"
                                :options="bankAccountOptions"
                                placeholder="Select bank account..."
                                :searchable="true"
                                :required="true"
                                :error="form.errors.bank_account_id"
                            />
                        </div>

                        <!-- Payment Method (Select) -->
                        <div>
                            <Select
                                v-model="form.payment_method"
                                label="Payment Method"
                                :options="paymentMethodOptions"
                                placeholder="Select method..."
                                :required="true"
                                :error="form.errors.payment_method"
                            />
                        </div>

                        <!-- Reference -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Reference / Cheque #
                            </label>
                            <input
                                v-model="form.reference"
                                type="text"
                                placeholder="e.g. CHQ-100234 or TXN-4491"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.reference" class="mt-1 text-xs text-rose-500">{{ form.errors.reference }}</p>
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Internal Notes / Remarks
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                placeholder="Additional details, memo, or authorization notes..."
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-xs text-rose-500">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link href="/admin/accounting/vendor-payments">
                        <Button variant="secondary" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Draft Payment' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
