<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import Combobox from '@/components/Combobox.vue';
import Card from '@/components/Card.vue';
import {
    COUNTRY_OPTIONS,
    CURRENCY_OPTIONS,
    TIMEZONE_OPTIONS,
    LOCALE_OPTIONS,
    findCountryDefaults,
} from '@/constants/referenceData';
import type { Tenant } from '@/types/tenancy';

const props = defineProps<{
    tenant: Tenant;
}>();

const form = useForm({
    name: props.tenant.name,
    slug: props.tenant.slug,
    industry: props.tenant.industry ?? '',
    country_code: props.tenant.country_code ?? 'MY',
    timezone: props.tenant.timezone ?? 'Asia/Kuala_Lumpur',
    locale: props.tenant.locale ?? 'en',
    currency: props.tenant.currency ?? 'MYR',
});

const handleCountryChange = (countryCode: string | number) => {
    const defaults = findCountryDefaults(String(countryCode));
    if (defaults) {
        form.currency = defaults.currency;
        form.timezone = defaults.timezone;
        form.locale = defaults.locale;
    }
};

const submit = () => {
    form.put(`/tenants/${props.tenant.id}`);
};

const industryOptions = [
    { label: 'Food & Beverage / Restaurant', value: 'restaurant' },
    { label: 'Retail & E-commerce', value: 'retail' },
    { label: 'Software & Technology', value: 'technology' },
    { label: 'Healthcare & Medical', value: 'healthcare' },
    { label: 'Financial Services', value: 'finance' },
    { label: 'Education & Training', value: 'education' },
    { label: 'Hospitality & Tourism', value: 'hospitality' },
    { label: 'Other', value: 'other' },
];
</script>

<template>
    <AdminLayout
        :title="`Edit ${tenant.name}`"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Tenants', href: '/tenants' },
            { label: tenant.name, href: `/tenants/${tenant.id}` },
            { label: 'Edit' },
        ]"
    >
        <!-- Page Header -->
        <div class="pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center gap-3">
                <Link
                    :href="`/tenants/${tenant.id}`"
                    class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Edit {{ tenant.name }}
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Update configuration and profile attributes for this organization.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="mt-8 max-w-4xl">
            <form class="space-y-8" @submit.prevent="submit">
                <!-- Card 1: General Information -->
                <Card
                    title="General Information"
                    description="Core identity and routing identifiers for this organization."
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Business Name -->
                        <div>
                            <TextInput
                                v-model="form.name"
                                label="Business Name"
                                placeholder="e.g. Acme Corporation"
                                :error="form.errors.name"
                                required
                            />
                        </div>

                        <!-- Slug -->
                        <div>
                            <TextInput
                                v-model="form.slug"
                                label="URL Slug"
                                prefix-addon="app.sathisaas.com/"
                                :error="form.errors.slug"
                                mono
                                required
                            />
                        </div>

                        <!-- Industry -->
                        <div class="sm:col-span-2">
                            <Select
                                v-model="form.industry"
                                label="Industry / Sector"
                                placeholder="Select industry classification"
                                :options="industryOptions"
                                :error="form.errors.industry"
                            />
                        </div>
                    </div>
                </Card>

                <!-- Card 2: Regional with Searchable Comboboxes -->
                <Card
                    title="Regional & Localization"
                    description="Timezone, currency and regional settings applied to this tenant."
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Country Combobox -->
                        <div>
                            <Combobox
                                v-model="form.country_code"
                                label="Country / Region"
                                placeholder="Search & select country..."
                                search-placeholder="Search all 240+ countries..."
                                :options="COUNTRY_OPTIONS"
                                required
                                @change="handleCountryChange"
                            />
                        </div>

                        <!-- Currency Combobox -->
                        <div>
                            <Combobox
                                v-model="form.currency"
                                label="Billing Currency"
                                placeholder="Search & select currency..."
                                search-placeholder="Search currencies..."
                                :options="CURRENCY_OPTIONS"
                                required
                            />
                        </div>

                        <!-- Timezone Combobox -->
                        <div>
                            <Combobox
                                v-model="form.timezone"
                                label="Default Timezone"
                                placeholder="Search & select timezone..."
                                search-placeholder="Search timezones..."
                                :options="TIMEZONE_OPTIONS"
                                required
                            />
                        </div>

                        <!-- Locale Combobox -->
                        <div>
                            <Combobox
                                v-model="form.locale"
                                label="Default Platform Locale"
                                placeholder="Search & select locale..."
                                search-placeholder="Search languages..."
                                :options="LOCALE_OPTIONS"
                                required
                            />
                        </div>
                    </div>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button
                        :href="`/tenants/${tenant.id}`"
                        variant="outline"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="form.processing"
                    >
                        Save Changes
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
