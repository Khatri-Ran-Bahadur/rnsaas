<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Select, type SelectOption } from '@/components';

interface Account {
    id: number;
    code: string;
    name: string;
}

interface Props {
    accounts: Account[];
}

const props = defineProps<Props>();

const form = useForm({
    vendor_code: '',
    name: '',
    email: '',
    phone: '',
    tax_number: '',
    payable_account_id: '',
    payment_terms_days: 0,
    credit_limit: '0.00',
    billing_address_line_1: '',
    billing_address_line_2: '',
    billing_city: '',
    billing_state: '',
    billing_postcode: '',
    billing_country: '',
});

const payableAccountOptions = computed<SelectOption[]>(() => [
    { label: 'Default Accounts Payable (2110)', value: '' },
    ...props.accounts.map((account) => ({
        label: `${account.code} - ${account.name}`,
        value: account.id,
    })),
]);

const submit = () => {
    form.post('/admin/accounting/vendors', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Create Vendor"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Vendors', href: '/admin/accounting/vendors' },
            { label: 'Create' },
        ]"
    >
        <Head title="Create Vendor - Accounting" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Create Vendor
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Register a new vendor/supplier for accounts payable tracking and purchase bills.
                    </p>
                </div>

                <Link href="/admin/accounting/vendors">
                    <Button variant="secondary" size="sm">
                        Back to Vendors
                    </Button>
                </Link>
            </div>

            <!-- Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Primary Information Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        General Information
                    </h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Vendor Code <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.vendor_code"
                                type="text"
                                required
                                placeholder="e.g. VEN-0001"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.vendor_code" class="mt-1 text-xs text-rose-500">{{ form.errors.vendor_code }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Vendor Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Acme Supplies Sdn Bhd"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Email Address
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="vendor@example.com"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Phone Number
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="+60 12-345 6789"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-500">{{ form.errors.phone }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Tax Number / SST / VAT ID
                            </label>
                            <input
                                v-model="form.tax_number"
                                type="text"
                                placeholder="e.g. W10-1808-32000018"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.tax_number" class="mt-1 text-xs text-rose-500">{{ form.errors.tax_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Accounting & Credit Terms Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        Accounting & Terms
                    </h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <Select
                                v-model="form.payable_account_id"
                                label="Default Accounts Payable Account"
                                :options="payableAccountOptions"
                                placeholder="Select payable account..."
                                :searchable="true"
                                :error="form.errors.payable_account_id"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Payment Terms (Days)
                            </label>
                            <input
                                v-model="form.payment_terms_days"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p class="mt-1 text-[11px] text-zinc-400">Days from invoice date before payment is due.</p>
                            <p v-if="form.errors.payment_terms_days" class="mt-1 text-xs text-rose-500">{{ form.errors.payment_terms_days }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Credit Limit
                            </label>
                            <input
                                v-model="form.credit_limit"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p class="mt-1 text-[11px] text-zinc-400">Maximum allowed outstanding balance.</p>
                            <p v-if="form.errors.credit_limit" class="mt-1 text-xs text-rose-500">{{ form.errors.credit_limit }}</p>
                        </div>
                    </div>
                </div>

                <!-- Billing Address Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        Billing Address
                    </h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Address Line 1</label>
                            <input
                                v-model="form.billing_address_line_1"
                                type="text"
                                placeholder="Street address or P.O. Box"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Address Line 2</label>
                            <input
                                v-model="form.billing_address_line_2"
                                type="text"
                                placeholder="Apartment, suite, unit, building, floor"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">City</label>
                            <input
                                v-model="form.billing_city"
                                type="text"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">State / Province</label>
                            <input
                                v-model="form.billing_state"
                                type="text"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Postcode / ZIP</label>
                            <input
                                v-model="form.billing_postcode"
                                type="text"
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Country (ISO 2-letter)</label>
                            <input
                                v-model="form.billing_country"
                                type="text"
                                maxlength="2"
                                placeholder="e.g. MY"
                                class="mt-1.5 w-full uppercase rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link href="/admin/accounting/vendors">
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Vendor' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
