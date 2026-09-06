<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Badge from '@/components/Badge.vue';
import Select from '@/components/Select.vue';
import Combobox from '@/components/Combobox.vue';
import {
    COUNTRY_OPTIONS,
    CURRENCY_OPTIONS,
    TIMEZONE_OPTIONS,
    LOCALE_OPTIONS,
    findCountryDefaults,
} from '@/constants/referenceData';

interface TenantSettings {
    email?: string | null;
    phone?: string | null;
    website?: string | null;
    tax_id?: string | null;
    registration_number?: string | null;
    address_line_1?: string | null;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    description?: string | null;
}

interface Tenant {
    id: number;
    public_id: string;
    name: string;
    slug: string;
    industry: string | null;
    status: string;
    country_code: string | null;
    timezone: string;
    locale: string;
    currency: string;
    settings?: TenantSettings | null;
    created_at: string;
    branches_count?: number;
    departments_count?: number;
    designations_count?: number;
    staff_count?: number;
    users_count?: number;
}

const props = defineProps<{
    tenant: Tenant;
}>();

const copied = ref(false);
const copyPublicId = () => {
    navigator.clipboard.writeText(props.tenant.public_id);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const form = useForm({
    name: props.tenant.name ?? '',
    slug: props.tenant.slug ?? '',
    industry: props.tenant.industry ?? '',
    country_code: props.tenant.country_code ?? 'NP',
    timezone: props.tenant.timezone ?? 'Asia/Kathmandu',
    currency: props.tenant.currency ?? 'NPR',
    locale: props.tenant.locale ?? 'en',
    email: props.tenant.settings?.email ?? '',
    phone: props.tenant.settings?.phone ?? '',
    website: props.tenant.settings?.website ?? '',
    tax_id: props.tenant.settings?.tax_id ?? '',
    registration_number: props.tenant.settings?.registration_number ?? '',
    address_line_1: props.tenant.settings?.address_line_1 ?? '',
    address_line_2: props.tenant.settings?.address_line_2 ?? '',
    city: props.tenant.settings?.city ?? '',
    state: props.tenant.settings?.state ?? '',
    postal_code: props.tenant.settings?.postal_code ?? '',
    description: props.tenant.settings?.description ?? '',
});

const submit = () => {
    form.put('/admin/company-profile', {
        preserveScroll: true,
    });
};

const handleCountryChange = (countryCode: string | number) => {
    const defaults = findCountryDefaults(String(countryCode));
    if (defaults) {
        form.currency = defaults.currency;
        form.timezone = defaults.timezone;
        form.locale = defaults.locale;
    }
};

const industries = [
    'Technology & Software',
    'Financial Services & Banking',
    'Healthcare & Pharmaceuticals',
    'Retail & E-commerce',
    'Manufacturing & Industrial',
    'Education & EdTech',
    'Hospitality & Tourism',
    'Consulting & Professional Services',
    'Real Estate & Construction',
    'Logistics & Supply Chain',
    'Media & Entertainment',
    'Telecommunications',
    'Non-Profit & NGO',
    'Other',
];
</script>

<template>
    <OrganizationLayout
        title="Company Profile"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Organization' },
            { label: 'Company Profile' },
        ]"
    >
        <Head title="Company Profile - Organization Admin" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header Banner Card -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-2xl font-bold text-white shadow-md shadow-indigo-500/25">
                            {{ tenant.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                    {{ tenant.name }}
                                </h1>
                                <Badge variant="success">
                                    {{ tenant.status.toUpperCase() }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Organization Workspace • Subdomain: <span class="font-mono font-medium text-zinc-700 dark:text-zinc-300">{{ tenant.slug }}</span>
                            </p>

                            <div class="mt-2 flex items-center gap-2">
                                <span class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500">
                                    ID: {{ tenant.public_id }}
                                </span>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors"
                                    @click="copyPublicId"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ copied ? 'Copied!' : 'Copy' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 self-start lg:self-center">
                        <Button
                            variant="primary"
                            size="sm"
                            :loading="form.processing"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            <template #prefix>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            Save Changes
                        </Button>
                    </div>
                </div>

                <!-- Stats Overview Bar -->
                <div class="mt-6 grid grid-cols-2 gap-3 border-t border-zinc-100 pt-5 sm:grid-cols-4 dark:border-zinc-800/80">
                    <div class="rounded-xl bg-zinc-50/70 p-3 dark:bg-zinc-950/40">
                        <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Branches</span>
                        <p class="mt-0.5 text-lg font-bold text-zinc-900 dark:text-white">
                            {{ tenant.branches_count ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-zinc-50/70 p-3 dark:bg-zinc-950/40">
                        <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Departments</span>
                        <p class="mt-0.5 text-lg font-bold text-zinc-900 dark:text-white">
                            {{ tenant.departments_count ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-zinc-50/70 p-3 dark:bg-zinc-950/40">
                        <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Designations</span>
                        <p class="mt-0.5 text-lg font-bold text-zinc-900 dark:text-white">
                            {{ tenant.designations_count ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-zinc-50/70 p-3 dark:bg-zinc-950/40">
                        <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Total Staff</span>
                        <p class="mt-0.5 text-lg font-bold text-zinc-900 dark:text-white">
                            {{ tenant.staff_count ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Section 1: Basic Identity -->
                <div class="relative z-30 rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="rounded-t-2xl border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-500/15 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Company Identity
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Primary legal business name, tenant slug, and industry classification.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Company Legal Name -->
                            <div>
                                <TextInput
                                    v-model="form.name"
                                    label="Legal Company / Organization Name"
                                    placeholder="e.g. Acme Corporation Pvt. Ltd."
                                    required
                                    :error="form.errors.name"
                                />
                            </div>

                            <!-- Workspace Slug -->
                            <div>
                                <TextInput
                                    v-model="form.slug"
                                    label="Workspace Slug / Subdomain"
                                    placeholder="e.g. acme"
                                    required
                                    :error="form.errors.slug"
                                    helper="Alphanumeric identifier used in workspace URLs."
                                />
                            </div>
                        </div>

                        <!-- Industry Sector -->
                        <div class="max-w-md">
                            <Select
                                v-model="form.industry"
                                label="Industry Sector"
                                placeholder="Select Industry"
                                :options="industries"
                                :error="form.errors.industry"
                            />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Contact Information -->
                <div class="relative z-25 rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="rounded-t-2xl border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 font-bold text-emerald-700 ring-1 ring-emerald-500/15 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Contact & Communication
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Official email, telephone numbers, and public website.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <!-- Official Email -->
                            <div>
                                <TextInput
                                    v-model="form.email"
                                    type="email"
                                    label="Official Contact Email"
                                    placeholder="contact@company.com"
                                    :error="form.errors.email"
                                />
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <TextInput
                                    v-model="form.phone"
                                    type="tel"
                                    label="Phone Number"
                                    placeholder="+977 1 4400000"
                                    :error="form.errors.phone"
                                />
                            </div>

                            <!-- Website -->
                            <div>
                                <TextInput
                                    v-model="form.website"
                                    type="url"
                                    label="Website URL"
                                    placeholder="https://company.com"
                                    :error="form.errors.website"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Registered Headquarters Address -->
                <div class="relative z-20 focus-within:z-30 rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="rounded-t-2xl border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 font-bold text-blue-700 ring-1 ring-blue-500/15 dark:bg-blue-950/60 dark:text-blue-300 dark:ring-blue-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Registered Headquarters Address
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Primary administrative location for official billing and tax invoices.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Address Line 1 -->
                            <div>
                                <TextInput
                                    v-model="form.address_line_1"
                                    label="Street Address / Line 1"
                                    placeholder="e.g. Putalisadak, Ward 28"
                                    :error="form.errors.address_line_1"
                                />
                            </div>

                            <!-- Address Line 2 -->
                            <div>
                                <TextInput
                                    v-model="form.address_line_2"
                                    label="Suite, Floor, Building (Line 2)"
                                    placeholder="e.g. Level 4, Star Plaza"
                                    :error="form.errors.address_line_2"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <!-- City -->
                            <div>
                                <TextInput
                                    v-model="form.city"
                                    label="City"
                                    placeholder="Kathmandu"
                                    :error="form.errors.city"
                                />
                            </div>

                            <!-- State -->
                            <div>
                                <TextInput
                                    v-model="form.state"
                                    label="State / Province"
                                    placeholder="Bagmati"
                                    :error="form.errors.state"
                                />
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <TextInput
                                    v-model="form.postal_code"
                                    label="Postal Code"
                                    placeholder="44600"
                                    :error="form.errors.postal_code"
                                />
                            </div>

                            <!-- Country -->
                            <div>
                                <Combobox
                                    v-model="form.country_code"
                                    label="Country / Region"
                                    placeholder="Search & select country..."
                                    search-placeholder="Search all 240+ countries..."
                                    :options="COUNTRY_OPTIONS"
                                    :error="form.errors.country_code"
                                    @change="handleCountryChange"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Regional & Localization Standards -->
                <div class="relative z-10 focus-within:z-30 rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="rounded-t-2xl border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-50 font-bold text-amber-700 ring-1 ring-amber-500/15 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Regional & Localization Standards
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Timezone, primary currency, and system language formatting.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <!-- Timezone -->
                            <div>
                                <Combobox
                                    v-model="form.timezone"
                                    label="Organization Timezone"
                                    placeholder="Search & select timezone..."
                                    search-placeholder="Search timezones..."
                                    :options="TIMEZONE_OPTIONS"
                                    :error="form.errors.timezone"
                                    required
                                />
                            </div>

                            <!-- Currency -->
                            <div>
                                <Combobox
                                    v-model="form.currency"
                                    label="Operating Currency"
                                    placeholder="Search & select currency..."
                                    search-placeholder="Search currencies..."
                                    :options="CURRENCY_OPTIONS"
                                    :error="form.errors.currency"
                                    required
                                />
                            </div>

                            <!-- Locale -->
                            <div>
                                <Combobox
                                    v-model="form.locale"
                                    label="Default Language / Locale"
                                    placeholder="Search & select locale..."
                                    search-placeholder="Search languages..."
                                    :options="LOCALE_OPTIONS"
                                    :error="form.errors.locale"
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Legal & Tax Identifiers -->
                <div class="relative z-0 rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="rounded-t-2xl border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-purple-50 font-bold text-purple-700 ring-1 ring-purple-500/15 dark:bg-purple-950/60 dark:text-purple-300 dark:ring-purple-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Legal & Tax Identifiers
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Registration and tax numbers printed on invoices and payroll documents.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Tax ID / VAT / PAN -->
                            <div>
                                <TextInput
                                    v-model="form.tax_id"
                                    label="Tax ID / VAT / PAN Number"
                                    placeholder="e.g. 601234567"
                                    :error="form.errors.tax_id"
                                    helper="Appears on generated client invoices and purchase orders."
                                />
                            </div>

                            <!-- Registration Number -->
                            <div>
                                <TextInput
                                    v-model="form.registration_number"
                                    label="Company Registration / Incorporation No."
                                    placeholder="e.g. REG-987654"
                                    :error="form.errors.registration_number"
                                />
                            </div>
                        </div>

                        <!-- Description / Bio -->
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Company Bio & Overview
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Brief summary of company operations, mission, or public notes..."
                                class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-xs text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button
                        variant="primary"
                        size="md"
                        :loading="form.processing"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Save Company Profile
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
