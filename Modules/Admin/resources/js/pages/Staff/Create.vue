<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Badge from '@/components/Badge.vue';

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

interface Member {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
}

const props = defineProps<{
    branches: Branch[];
    departments: Department[];
    designations: Designation[];
    availableMembers: Member[];
    preselectedUserId?: number | null;
}>();

const mode = ref<'existing_member' | 'new_person'>(
    props.preselectedUserId || props.availableMembers.length > 0 ? 'existing_member' : 'new_person'
);

const form = useForm({
    user_id: '' as number | '',
    name: '',
    email: '',
    phone: '',
    employee_code: '',
    branch_id: props.branches.length === 1 ? props.branches[0].id : '',
    department_id: props.departments.length === 1 ? props.departments[0].id : '',
    designation_id: props.designations.length === 1 ? props.designations[0].id : '',
    joining_date: new Date().toISOString().split('T')[0],
    employment_status: 'active',
});

const selectedMember = computed(() => {
    if (!form.user_id) return null;
    return props.availableMembers.find((m) => m.id === Number(form.user_id)) ?? null;
});

const handleMemberChange = () => {
    if (selectedMember.value) {
        form.name = selectedMember.value.name;
        form.email = selectedMember.value.email;
        form.phone = selectedMember.value.phone ?? '';
    }
};

const switchMode = (targetMode: 'existing_member' | 'new_person') => {
    mode.value = targetMode;
    if (targetMode === 'new_person') {
        form.user_id = '';
        form.name = '';
        form.email = '';
        form.phone = '';
    } else {
        if (props.availableMembers.length > 0 && !form.user_id) {
            form.user_id = props.availableMembers[0].id;
            handleMemberChange();
        }
    }
};

onMounted(() => {
    if (props.preselectedUserId) {
        mode.value = 'existing_member';
        form.user_id = props.preselectedUserId;
        handleMemberChange();
    } else if (mode.value === 'existing_member' && props.availableMembers.length > 0 && !form.user_id) {
        form.user_id = props.availableMembers[0].id;
        handleMemberChange();
    }
});

const submit = () => {
    if (mode.value === 'existing_member') {
        form.post('/admin/staff', {
            preserveScroll: true,
        });
    } else {
        form.user_id = '';
        form.post('/admin/staff', {
            preserveScroll: true,
        });
    }
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
                        Assign an organization member to a staff role, or invite a new employee.
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

            <!-- Mode Selector Tabs (Segmented Control) -->
            <div class="flex p-1 space-x-1 rounded-2xl border border-zinc-200 bg-zinc-100/70 dark:border-zinc-800 dark:bg-zinc-900/80 max-w-md">
                <button
                    type="button"
                    :class="[
                        'flex-1 flex items-center justify-center gap-2 rounded-xl py-2 px-3 text-xs font-semibold transition-all',
                        mode === 'existing_member'
                            ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-800 dark:text-white'
                            : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                    ]"
                    @click="switchMode('existing_member')"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Select Member</span>
                    <span
                        v-if="availableMembers.length > 0"
                        class="ml-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                    >
                        {{ availableMembers.length }}
                    </span>
                </button>

                <button
                    type="button"
                    :class="[
                        'flex-1 flex items-center justify-center gap-2 rounded-xl py-2 px-3 text-xs font-semibold transition-all',
                        mode === 'new_person'
                            ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-800 dark:text-white'
                            : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                    ]"
                    @click="switchMode('new_person')"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Invite New Person</span>
                </button>
            </div>

            <!-- Main Form -->
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Mode 1: Existing Member Selection Card -->
                <div
                    v-if="mode === 'existing_member'"
                    class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-500/15 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Select Organization Member
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Active members of this organization who do not yet have a staff record.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div v-if="availableMembers.length > 0">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Select Member <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.user_id"
                                class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-xs text-zinc-900 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                required
                                @change="handleMemberChange"
                            >
                                <option value="" disabled>Choose an organization member...</option>
                                <option v-for="m in availableMembers" :key="m.id" :value="m.id">
                                    {{ m.name }} • ({{ m.email }})
                                </option>
                            </select>
                            <p v-if="form.errors.user_id" class="mt-1 text-xs text-rose-500 font-medium">
                                {{ form.errors.user_id }}
                            </p>

                            <!-- Selected Member Summary Card -->
                            <div
                                v-if="selectedMember"
                                class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-xs">
                                            {{ selectedMember.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                                {{ selectedMember.name }}
                                            </p>
                                            <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">
                                                {{ selectedMember.email }}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge variant="success">Active Member</Badge>
                                </div>
                                <p class="mt-2.5 text-[11px] text-zinc-500 dark:text-zinc-400 border-t border-indigo-100/80 pt-2 dark:border-indigo-900/30">
                                    ✓ Reusing existing user account & organization membership. No duplicate user account will be created.
                                </p>
                            </div>
                        </div>

                        <!-- Empty State if no members are available -->
                        <div
                            v-else
                            class="rounded-xl border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-700"
                        >
                            <svg class="mx-auto h-8 w-8 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <h3 class="mt-2 text-xs font-semibold text-zinc-900 dark:text-white">
                                No Unassigned Members Available
                            </h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                All active members of this organization already have staff profiles.
                            </p>
                            <div class="mt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="switchMode('new_person')"
                                >
                                    Invite New Person Instead
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mode 2: Invite New Person Card -->
                <div
                    v-else
                    class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-500/15 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-500/30">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    New Person Details
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Onboard someone who is not yet a member of this organization.
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
                                    helper="Used for portal login and notifications."
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

                <!-- Card 2: Organization & Employment Placement (Always Shown) -->
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
                                    Employment Information & Role Placement
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Employee ID, branch, department, designation, and employment status.
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

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 pt-2">
                            <!-- Employee Code -->
                            <div>
                                <TextInput
                                    v-model="form.employee_code"
                                    label="Employee Code"
                                    placeholder="e.g. EMP-001"
                                    required
                                    :error="form.errors.employee_code"
                                    helper="Unique staff employee ID."
                                />
                            </div>

                            <!-- Joining Date -->
                            <div>
                                <TextInput
                                    v-model="form.joining_date"
                                    type="date"
                                    label="Joining Date"
                                    :error="form.errors.joining_date"
                                    helper="Effective official start date."
                                />
                            </div>

                            <!-- Employment Status -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                    Employment Status <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="form.employment_status"
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                    required
                                >
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="terminated">Terminated</option>
                                </select>
                                <p v-if="form.errors.employment_status" class="mt-1 text-xs text-rose-500">
                                    {{ form.errors.employment_status }}
                                </p>
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
