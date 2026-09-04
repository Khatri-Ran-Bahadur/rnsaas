<script setup lang="ts">
import { ref } from 'vue';
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

interface TenantForm {
    name: string;
    slug: string;
    industry: string;
    country_code: string;
    timezone: string;
    locale: string;
    currency: string;
}

const form = useForm<TenantForm>({
    name: '',
    slug: '',
    industry: '',
    country_code: 'MY',
    timezone: 'Asia/Kuala_Lumpur',
    locale: 'en',
    currency: 'MYR',
});

const isSlugTouched = ref(false);

const slugify = (text: string): string => {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-');
};

const handleNameInput = () => {
    if (!isSlugTouched.value) {
        form.slug = slugify(form.name);
    }
};

const handleSlugInput = () => {
    isSlugTouched.value = true;
    form.slug = slugify(form.slug);
};

const handleCountryChange = (countryCode: string | number) => {
    const defaults = findCountryDefaults(String(countryCode));
    if (defaults) {
        form.currency = defaults.currency;
        form.timezone = defaults.timezone;
        form.locale = defaults.locale;
    }
};

const submit = () => {
    form.post('/superadmin/tenants');
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
        title="Create Organization"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Organizations', href: '/superadmin/tenants' },
            { label: 'New Organization' },
        ]"
    >
        <!-- Page Header -->
        <div class="pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center gap-3">
                <Link
                    href="/superadmin/tenants"
                    class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Create New Organization
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Register a new client organization, establish its dedicated workspace, and configure regional defaults.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="mt-8 max-w-4xl">
            <form class="space-y-8" @submit.prevent="submit">
                <!-- Card 1: General Information -->
                <Card
                    title="Organization Information"
                    description="Core business identity, workspace name, and custom URL slug."
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Business Name -->
                        <div>
                            <TextInput
                                v-model="form.name"
                                label="Organization Name"
                                placeholder="e.g. Acme Corporation"
                                :error="form.errors.name"
                                required
                                autofocus
                                @input="handleNameInput"
                            />
                        </div>

                        <!-- Slug with Prefix Addon -->
                        <div>
                            <TextInput
                                v-model="form.slug"
                                label="Workspace Slug / URL"
                                placeholder="acme-corp"
                                prefix-addon="app.sathisaas.com/"
                                :error="form.errors.slug"
                                mono
                                required
                                @input="handleSlugInput"
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

                <!-- Card 2: Regional & Localization with Searchable Comboboxes -->
                <Card
                    title="Regional & Localization Defaults"
                    description="Timezone, currency and regional settings configured for this organization."
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Country Combobox with flags & search -->
                        <div>
                            <Combobox
                                v-model="form.country_code"
                                label="Country / Region"
                                placeholder="Search & select country..."
                                search-placeholder="Search all 240+ countries..."
                                :options="COUNTRY_OPTIONS"
                                :error="form.errors.country_code"
                                required
                                @change="handleCountryChange"
                            />
                            <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                Selecting country auto-populates currency, timezone, and locale.
                            </p>
                        </div>

                        <!-- Currency Combobox -->
                        <div>
                            <Combobox
                                v-model="form.currency"
                                label="Billing Currency"
                                placeholder="Search & select currency..."
                                search-placeholder="Search currencies..."
                                :options="CURRENCY_OPTIONS"
                                :error="form.errors.currency"
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
                                :error="form.errors.timezone"
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
                                :error="form.errors.locale"
                                required
                            />
                        </div>
                    </div>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button
                        href="/superadmin/tenants"
                        variant="outline"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="form.processing"
                    >
                        Create Organization
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>