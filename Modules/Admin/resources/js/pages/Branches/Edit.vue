<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';

interface Branch {
    id: number;
    public_id: string;
    tenant_id: number;
    name: string;
    code: string;
    status: 'active' | 'inactive';
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country_code: string | null;
}

const props = defineProps<{
    branch: Branch;
}>();

const form = useForm({
    name: props.branch.name,
    code: props.branch.code,
    status: props.branch.status,
    address_line_1: props.branch.address_line_1 ?? '',
    address_line_2: props.branch.address_line_2 ?? '',
    city: props.branch.city ?? '',
    state: props.branch.state ?? '',
    postal_code: props.branch.postal_code ?? '',
    country_code: props.branch.country_code ?? '',
});

const submit = () => {
    form.put(`/admin/branches/${props.branch.public_id}`);
};
</script>

<template>
    <OrganizationLayout
        :title="`Edit ${branch.name}`"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Branches', href: '/admin/branches' },
            { label: branch.name, href: `/admin/branches/${branch.public_id}` },
            { label: 'Edit' },
        ]"
    >
        <Head :title="`Edit ${branch.name} - Organization Admin`" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header with Back Button -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="`/admin/branches/${branch.public_id}`"
                            class="inline-flex items-center gap-1 text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Back to {{ branch.name }}</span>
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Edit Branch
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Update branch information, operational status, or address location.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        :href="`/admin/branches/${branch.public_id}`"
                        variant="outline"
                        size="sm"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>

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

            <!-- Form Cards -->
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Card 1: Branch Identification & Status -->
                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-500/15 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Branch Details & Status
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Configure core identity attributes and operational status.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Branch Name -->
                            <div class="sm:col-span-2">
                                <TextInput
                                    v-model="form.name"
                                    label="Branch Name"
                                    placeholder="e.g. Kathmandu Headquarters"
                                    :error="form.errors.name"
                                    :required="true"
                                >
                                    <template #prefix>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </template>
                                </TextInput>
                            </div>

                            <!-- Branch Code -->
                            <div class="sm:col-span-2">
                                <TextInput
                                    v-model="form.code"
                                    label="Branch Code"
                                    placeholder="e.g. KTM-01"
                                    hint="Unique alphanumeric code inside your organization."
                                    :error="form.errors.code"
                                    :required="true"
                                    :uppercase="true"
                                    :mono="true"
                                >
                                    <template #prefix>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                        </svg>
                                    </template>
                                </TextInput>
                            </div>
                        </div>

                        <!-- Operational Status Selector (Visual Radio Cards) -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-2">
                                Operational Status <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div
                                    :class="[
                                        'relative flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition-all',
                                        form.status === 'active'
                                            ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20 dark:border-emerald-500/60 dark:bg-emerald-950/20'
                                            : 'border-zinc-200 bg-white hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800/60',
                                    ]"
                                    @click="form.status = 'active'"
                                >
                                    <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-zinc-300 dark:border-zinc-600">
                                        <div v-if="form.status === 'active'" class="h-2 w-2 rounded-full bg-emerald-600" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-zinc-900 dark:text-white">Active</span>
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                        </div>
                                        <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                            Branch is open and active for all daily operations and staff assignments.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    :class="[
                                        'relative flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition-all',
                                        form.status === 'inactive'
                                            ? 'border-zinc-500 bg-zinc-100/70 ring-2 ring-zinc-500/20 dark:border-zinc-600 dark:bg-zinc-800/60'
                                            : 'border-zinc-200 bg-white hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800/60',
                                    ]"
                                    @click="form.status = 'inactive'"
                                >
                                    <div class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-zinc-300 dark:border-zinc-600">
                                        <div v-if="form.status === 'inactive'" class="h-2 w-2 rounded-full bg-zinc-700 dark:bg-zinc-300" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-zinc-900 dark:text-white">Inactive</span>
                                            <span class="h-1.5 w-1.5 rounded-full bg-zinc-400" />
                                        </div>
                                        <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                            Operations are suspended. Branch remains stored safely in the database.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.status" class="mt-1.5 text-xs text-rose-500 font-medium">
                                {{ form.errors.status }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Physical Address & Location -->
                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 font-bold text-emerald-700 ring-1 ring-emerald-500/15 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Location & Physical Address
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Physical address details for invoicing, tax jurisdiction, and maps.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <TextInput
                                    v-model="form.address_line_1"
                                    label="Address Line 1"
                                    placeholder="Street address, building, suite"
                                    :error="form.errors.address_line_1"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <TextInput
                                    v-model="form.address_line_2"
                                    label="Address Line 2"
                                    placeholder="Apartment, unit, suite, floor"
                                    :error="form.errors.address_line_2"
                                />
                            </div>

                            <div>
                                <TextInput
                                    v-model="form.city"
                                    label="City"
                                    placeholder="e.g. Kathmandu"
                                    :error="form.errors.city"
                                />
                            </div>

                            <div>
                                <TextInput
                                    v-model="form.state"
                                    label="State / Province"
                                    placeholder="e.g. Bagmati"
                                    :error="form.errors.state"
                                />
                            </div>

                            <div>
                                <TextInput
                                    v-model="form.postal_code"
                                    label="Postal / ZIP Code"
                                    placeholder="e.g. 44600"
                                    :error="form.errors.postal_code"
                                />
                            </div>

                            <div>
                                <TextInput
                                    v-model="form.country_code"
                                    label="Country Code"
                                    placeholder="e.g. NP"
                                    hint="2-letter ISO 3166-1 alpha-2 code"
                                    :error="form.errors.country_code"
                                    :uppercase="true"
                                    :mono="true"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Anchored Actions Footer -->
                <div class="flex items-center justify-end gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <Button
                        :href="`/admin/branches/${branch.public_id}`"
                        variant="outline"
                        size="md"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>

                    <Button
                        type="submit"
                        variant="primary"
                        size="md"
                        :loading="form.processing"
                        :disabled="form.processing"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        Save Changes
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
