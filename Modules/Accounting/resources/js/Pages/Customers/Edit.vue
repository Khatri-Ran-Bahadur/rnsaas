<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Select } from '@/components';

interface AccountOption {
    id: number;
    code: string;
    name: string;
}

interface CustomerData {
    id: number;
    public_id: string;
    customer_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    receivable_account_id: number | null;
    credit_limit: string;
    payment_terms_days: number;
    billing_address_line_1: string | null;
    billing_address_line_2: string | null;
    billing_city: string | null;
    billing_state: string | null;
    billing_postcode: string | null;
    billing_country: string | null;
}

interface Props {
    customer: CustomerData;
    accounts: AccountOption[];
}

const props = defineProps<Props>();

const accountOptions = computed(() => [
    { label: 'Default Accounts Receivable', value: '' },
    ...props.accounts.map(a => ({ label: `${a.code} - ${a.name}`, value: a.id })),
]);

const form = useForm({
    name: props.customer.name,
    email: props.customer.email ?? '',
    phone: props.customer.phone ?? '',
    tax_number: props.customer.tax_number ?? '',
    receivable_account_id: props.customer.receivable_account_id ?? '',
    credit_limit: props.customer.credit_limit,
    payment_terms_days: props.customer.payment_terms_days,
    billing_address_line_1: props.customer.billing_address_line_1 ?? '',
    billing_address_line_2: props.customer.billing_address_line_2 ?? '',
    billing_city: props.customer.billing_city ?? '',
    billing_state: props.customer.billing_state ?? '',
    billing_postcode: props.customer.billing_postcode ?? '',
    billing_country: props.customer.billing_country ?? 'MY',
});

const submit = () => {
    form.put(`/admin/accounting/customers/${props.customer.public_id || props.customer.id}`);
};
</script>

<template>
    <Head :title="`Edit ${customer.name} - Accounting`" />

    <OrganizationLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="`/admin/accounting/customers/${customer.public_id || customer.id}`"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; {{ customer.name }}
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Edit Customer: {{ customer.name }}
                    </h1>
                </div>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Customer Information</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Basic contact information and identifying details.</p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1 sm:col-span-2">
                            <label for="name" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Customer / Company Name <span class="text-rose-500">*</span></label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                required
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Customer Code</label>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono text-slate-600 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400">
                                {{ customer.customer_code }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="tax_number" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Tax / VAT / SST Number</label>
                            <input
                                id="tax_number"
                                v-model="form.tax_number"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1">
                            <label for="email" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Email Address</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                            <p v-if="form.errors.email" class="text-xs text-rose-500">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-1">
                            <label for="phone" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Phone Number</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Financial & Terms -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Financial & Account Configuration</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Define the default chart of account mapping and payment expectations.</p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <Select
                                v-model="form.receivable_account_id"
                                :options="accountOptions"
                                label="Receivable Account"
                                placeholder="Default Accounts Receivable"
                                searchable
                            />
                        </div>

                        <div class="space-y-1">
                            <label for="credit_limit" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Credit Limit ($)</label>
                            <input
                                id="credit_limit"
                                v-model="form.credit_limit"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1">
                            <label for="payment_terms_days" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Payment Terms (Days)</label>
                            <input
                                id="payment_terms_days"
                                v-model="form.payment_terms_days"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Billing Address -->
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Billing Address</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-zinc-400">Registered address printed on invoices and statements.</p>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1 sm:col-span-2">
                            <label for="billing_address_line_1" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Address Line 1</label>
                            <input
                                id="billing_address_line_1"
                                v-model="form.billing_address_line_1"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label for="billing_address_line_2" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Address Line 2</label>
                            <input
                                id="billing_address_line_2"
                                v-model="form.billing_address_line_2"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1">
                            <label for="billing_city" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">City</label>
                            <input
                                id="billing_city"
                                v-model="form.billing_city"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1">
                            <label for="billing_state" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">State / Province</label>
                            <input
                                id="billing_state"
                                v-model="form.billing_state"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1">
                            <label for="billing_postcode" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Postal Code</label>
                            <input
                                id="billing_postcode"
                                v-model="form.billing_postcode"
                                type="text"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>

                        <div class="space-y-1">
                            <label for="billing_country" class="block text-xs font-medium text-slate-700 dark:text-zinc-300">Country Code</label>
                            <input
                                id="billing_country"
                                v-model="form.billing_country"
                                type="text"
                                maxlength="2"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="`/admin/accounting/customers/${customer.public_id || customer.id}`">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Customer' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
