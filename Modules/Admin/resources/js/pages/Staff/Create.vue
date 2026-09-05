<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';

interface Branch {
    id: number;
    name: string;
    code: string;
}

interface Department {
    id: number;
    name: string;
    code: string;
}

interface Designation {
    id: number;
    name: string;
    code: string;
}

const props = defineProps<{
    branches: Branch[];
    departments: Department[];
    designations: Designation[];
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    employee_code: '',
    branch_id: props.branches.length === 1 ? props.branches[0].id : '',
    department_id: props.departments.length === 1 ? props.departments[0].id : '',
    designation_id: props.designations.length === 1 ? props.designations[0].id : '',
    joining_date: new Date().toISOString().split('T')[0],
});

const submit = () => {
    form.post('/admin/staff');
};
</script>

<template>
    <OrganizationLayout
        title="Add Staff Member"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'HRM' },
            { label: 'Staff', href: '/admin/staff' },
            { label: 'New Staff Member' },
        ]"
    >
        <Head title="Onboard Staff Member - Organization Admin" />

        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header with Back Button -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/staff"
                            class="inline-flex items-center gap-1 text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Back to Staff Directory</span>
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Onboard Staff Member
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Create a staff profile and assign organizational placement, branch, and role.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        href="/admin/staff"
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
                        Save Staff Member
                    </Button>
                </div>
            </div>

            <!-- Main Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Card 1: Personal & Account Details -->
                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-500/15 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Personal Information
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Name, contact details, and user account identification.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Full Name -->
                            <div>
                                <TextInput
                                    v-model="form.name"
                                    label="Full Name"
                                    placeholder="e.g. Jane Doe"
                                    required
                                    :error="form.errors.name"
                                />
                            </div>

                            <!-- Email Address -->
                            <div>
                                <TextInput
                                    v-model="form.email"
                                    type="email"
                                    label="Email Address"
                                    placeholder="e.g. jane.doe@company.com"
                                    required
                                    :error="form.errors.email"
                                    helper="Used for notifications and portal login credentials."
                                />
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="max-w-md">
                            <TextInput
                                v-model="form.phone"
                                type="tel"
                                label="Phone Number"
                                placeholder="e.g. +977 9800000000"
                                :error="form.errors.phone"
                            />
                        </div>
                    </div>
                </div>

                <!-- Card 2: Organization & Employment Placement -->
                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 font-bold text-emerald-700 ring-1 ring-emerald-500/15 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Organization & Role Assignment
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Allocate the employee to a branch, department, and job position.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <!-- Branch Selection -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                    Assigned Branch <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.branch_id"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                    required
                                >
                                    <option value="" disabled>Select Branch</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">
                                        {{ b.name }} ({{ b.code }})
                                    </option>
                                </select>
                                <p v-if="branches.length === 0" class="mt-1 text-[11px] text-amber-600">
                                    No active branches found. Please create a branch first.
                                </p>
                                <p v-if="form.errors.branch_id" class="mt-1 text-xs text-rose-500">
                                    {{ form.errors.branch_id }}
                                </p>
                            </div>

                            <!-- Department Selection -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                    Department <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.department_id"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                    required
                                >
                                    <option value="" disabled>Select Department</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">
                                        {{ d.name }} ({{ d.code }})
                                    </option>
                                </select>
                                <p v-if="departments.length === 0" class="mt-1 text-[11px] text-amber-600">
                                    No active departments found. Please create a department first.
                                </p>
                                <p v-if="form.errors.department_id" class="mt-1 text-xs text-rose-500">
                                    {{ form.errors.department_id }}
                                </p>
                            </div>

                            <!-- Designation Selection -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                    Designation <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.designation_id"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                    required
                                >
                                    <option value="" disabled>Select Designation</option>
                                    <option v-for="des in designations" :key="des.id" :value="des.id">
                                        {{ des.name }}
                                    </option>
                                </select>
                                <p v-if="designations.length === 0" class="mt-1 text-[11px] text-amber-600">
                                    No active designations found. Please create a designation first.
                                </p>
                                <p v-if="form.errors.designation_id" class="mt-1 text-xs text-rose-500">
                                    {{ form.errors.designation_id }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 pt-2">
                            <!-- Employee Code -->
                            <div>
                                <TextInput
                                    v-model="form.employee_code"
                                    label="Employee Code"
                                    placeholder="e.g. EMP-001"
                                    required
                                    :error="form.errors.employee_code"
                                    helper="Unique employee number or staff payroll ID."
                                />
                            </div>

                            <!-- Joining Date -->
                            <div>
                                <TextInput
                                    v-model="form.joining_date"
                                    type="date"
                                    label="Joining Date"
                                    :error="form.errors.joining_date"
                                    helper="Effective official date of employment."
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button
                        href="/admin/staff"
                        variant="outline"
                        size="md"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        size="md"
                        :loading="form.processing"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Save Staff Member
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
