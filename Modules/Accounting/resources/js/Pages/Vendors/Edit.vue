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

interface VendorData {
    id: number;
    vendor_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    payable_account_id: number | null;
    payment_terms_days: number;
    credit_limit: string;
    billing_address_line_1: string | null;
    billing_address_line_2: string | null;
    billing_city: string | null;
    billing_state: string | null;
    billing_postcode: string | null;
    billing_country: string | null;
}

interface Props {
    vendor: VendorData;
    accounts: Account[];
}

const props = defineProps<Props>();

const form = useForm({
    name: props.vendor.name ?? '',
    email: props.vendor.email ?? '',
    phone: props.vendor.phone ?? '',
    tax_number: props.vendor.tax_number ?? '',
    payable_account_id: props.vendor.payable_account_id ? String(props.vendor.payable_account_id) : '',
    payment_terms_days: props.vendor.payment_terms_days ?? 0,
    credit_limit: props.vendor.credit_limit ?? '0.00',
    billing_address_line_1: props.vendor.billing_address_line_1 ?? '',
    billing_address_line_2: props.vendor.billing_address_line_2 ?? '',
    billing_city: props.vendor.billing_city ?? '',
    billing_state: props.vendor.billing_state ?? '',
    billing_postcode: props.vendor.billing_postcode ?? '',
    billing_country: props.vendor.billing_country ?? '',
});

const payableAccountOptions = computed<SelectOption[]>(() => [
    { label: 'Default Accounts Payable (2110)', value: '' },
    ...props.accounts.map((account) => ({
        label: `${account.code} - ${account.name}`,
        value: account.id,
    })),
]);

const submit = () => {
    form.put(`/admin/accounting/vendors/${props.vendor.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Edit Vendor"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Vendors', href: '/admin/accounting/vendors' },
            { label: vendor.name, href: `/admin/accounting/vendors/${vendor.id}` },
            { label: 'Edit' },
        ]"
    >
        <Head :title="`Edit ${vendor.name} - Accounting`" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Edit Vendor: {{ vendor.name }}
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Vendor Code: <span class="font-mono font-semibold">{{ vendor.vendor_code }}</span>
                    </p>
                </div>

                <Link :href="`/admin/accounting/vendors/${vendor.id}`">
                    <Button variant="secondary" size="sm">
                        View Vendor
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
                                Vendor Code
                            </label>
                            <input
                                :value="vendor.vendor_code"
                                type="text"
                                disabled
                                class="mt-1.5 w-full rounded-lg border border-zinc-200 bg-zinc-100 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400 cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Vendor Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
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
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                            <p v-if="form.errors.tax_number" class="mt-1 text-xs text-rose-500">{{ form.errors.tax_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Accounting & Terms Card -->
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
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
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
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
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
                                class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Address Line 2</label>
                            <input
                                v-model="form.billing_address_line_2"
                                type="text"
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
                                class="mt-1.5 w-full uppercase rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="`/admin/accounting/vendors/${vendor.id}`">
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
