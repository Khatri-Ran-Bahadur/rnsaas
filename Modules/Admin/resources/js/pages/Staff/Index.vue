<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Dropdown from '@/components/Dropdown.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';

interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
}

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

interface TenantStaff {
    id: number;
    public_id: string;
    tenant_id: number;
    user_id: number;
    branch_id: number;
    department_id: number;
    designation_id: number;
    employee_code: string;
    joining_date: string | null;
    employment_status: 'active' | 'suspended' | 'terminated';
    created_at: string;
    updated_at: string;
    user?: User;
    branch?: Branch;
    department?: Department;
    designation?: Designation;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedStaff {
    data: TenantStaff[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

const props = defineProps<{
    staff: PaginatedStaff;
    branches: Branch[];
    departments: Department[];
    designations: Designation[];
    filters: {
        search?: string;
        status?: string;
        branch_id?: number | null;
        department_id?: number | null;
        designation_id?: number | null;
        per_page?: number;
    };
}>();

const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const selectedBranch = ref<number | ''>(props.filters.branch_id ?? '');
const selectedDepartment = ref<number | ''>(props.filters.department_id ?? '');
const selectedDesignation = ref<number | ''>(props.filters.designation_id ?? '');
const perPage = ref(props.filters.per_page ?? props.staff.per_page ?? 15);
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        '/admin/staff',
        {
            search: search.value.trim() || undefined,
            status: selectedStatus.value || undefined,
            branch_id: selectedBranch.value || undefined,
            department_id: selectedDepartment.value || undefined,
            designation_id: selectedDesignation.value || undefined,
            per_page: perPage.value !== 15 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
            },
        }
    );
};

const onSearchInput = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

const clearSearch = () => {
    search.value = '';
    applyFilters();
};

const resetAllFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedBranch.value = '';
    selectedDepartment.value = '';
    selectedDesignation.value = '';
    applyFilters();
};

const onPerPageChange = (val: number) => {
    perPage.value = val;
    applyFilters();
};

// Suspend Modal State
const suspendingStaff = ref<TenantStaff | null>(null);
const isSuspending = ref(false);

const openSuspendModal = (staffMember: TenantStaff) => {
    suspendingStaff.value = staffMember;
};

const closeSuspendModal = () => {
    suspendingStaff.value = null;
};

const confirmSuspend = () => {
    if (!suspendingStaff.value) return;

    isSuspending.value = true;
    router.patch(
        `/admin/staff/${suspendingStaff.value.id}/suspend`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isSuspending.value = false;
                closeSuspendModal();
            },
        }
    );
};

// Activate Modal State
const activatingStaff = ref<TenantStaff | null>(null);
const isActivating = ref(false);

const openActivateModal = (staffMember: TenantStaff) => {
    activatingStaff.value = staffMember;
};

const closeActivateModal = () => {
    activatingStaff.value = null;
};

const confirmActivate = () => {
    if (!activatingStaff.value) return;

    isActivating.value = true;
    router.patch(
        `/admin/staff/${activatingStaff.value.id}/activate`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isActivating.value = false;
                closeActivateModal();
            },
        }
    );
};

const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '—';
    try {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'success';
        case 'suspended':
            return 'warning';
        case 'terminated':
            return 'danger';
        default:
            return 'neutral';
    }
};

const cleanLabel = (label: string) => {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', '')
        .replace('Next', '')
        .trim();
};

const isPrevious = (label: string) => label.includes('Previous') || label.includes('&laquo;');
const isNext = (label: string) => label.includes('Next') || label.includes('&raquo;');
</script>

<template>
    <OrganizationLayout
        title="Staff Management"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'HRM' },
            { label: 'Staff' },
        ]"
    >
        <Head title="Staff Directory - Organization Admin" />

        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Staff Directory
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Manage company employees, branch assignments, job roles, and employment records.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200/90 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" />
                        Total Staff: {{ staff.total }}
                    </span>

                    <Button
                        href="/admin/staff/create"
                        variant="primary"
                        size="sm"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </template>
                        Add Staff Member
                    </Button>
                </div>
            </div>

            <!-- Unified DataTable Card Wrapper -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs transition-colors dark:border-zinc-800 dark:bg-zinc-900">
                <!-- Integrated Toolbar (Search & Filter Row) -->
                <div class="flex flex-col gap-3 border-b border-zinc-200/70 p-4 dark:border-zinc-800/80">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <!-- Left: Search Box -->
                        <div class="relative flex-1 max-w-lg">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by name, email, phone, or employee code..."
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 pl-10 pr-9 py-2 text-xs text-zinc-900 placeholder-zinc-400 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100 dark:focus:border-indigo-500"
                                @input="onSearchInput"
                            />
                            <button
                                v-if="search"
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                                @click="clearSearch"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            </button>
                        </div>

                        <!-- Right: Per Page & Reset -->
                        <div class="flex items-center gap-3 self-end lg:self-auto">
                            <Button
                                v-if="search || selectedStatus || selectedBranch || selectedDepartment || selectedDesignation"
                                variant="ghost"
                                size="xs"
                                class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400"
                                @click="resetAllFilters"
                            >
                                Clear All Filters
                            </Button>

                            <PerPageSelector
                                :model-value="perPage"
                                :options="[10, 15, 25, 50, 100]"
                                @update:model-value="onPerPageChange"
                            />
                        </div>
                    </div>

                    <!-- Multi-Filter Row -->
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 pt-1">
                        <!-- Branch Filter -->
                        <div>
                            <select
                                v-model="selectedBranch"
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-1.5 text-xs text-zinc-700 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-300 dark:focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Branches</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">
                                    {{ b.name }} ({{ b.code }})
                                </option>
                            </select>
                        </div>

                        <!-- Department Filter -->
                        <div>
                            <select
                                v-model="selectedDepartment"
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-1.5 text-xs text-zinc-700 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-300 dark:focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Departments</option>
                                <option v-for="d in departments" :key="d.id" :value="d.id">
                                    {{ d.name }} ({{ d.code }})
                                </option>
                            </select>
                        </div>

                        <!-- Designation Filter -->
                        <div>
                            <select
                                v-model="selectedDesignation"
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-1.5 text-xs text-zinc-700 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-300 dark:focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Designations</option>
                                <option v-for="des in designations" :key="des.id" :value="des.id">
                                    {{ des.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <select
                                v-model="selectedStatus"
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-1.5 text-xs text-zinc-700 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-300 dark:focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="relative overflow-x-auto">
                    <!-- Loading overlay -->
                    <div
                        v-if="isLoading"
                        class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-2xs dark:bg-zinc-900/60"
                    >
                        <div class="flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-xs font-medium text-zinc-700 shadow-md dark:bg-zinc-800 dark:text-zinc-200">
                            <svg class="h-4 w-4 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Loading staff directory...
                        </div>
                    </div>

                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-zinc-200/70 bg-zinc-50/50 text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-950/30 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 font-semibold sm:pl-6">Employee</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Code</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Branch</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Department</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Designation</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Joining Date</th>
                                <th scope="col" class="px-3 py-3.5 font-semibold">Status</th>
                                <th scope="col" class="py-3.5 pl-3 pr-4 text-right font-semibold sm:pr-6">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800/80">
                            <!-- Empty State -->
                            <tr v-if="staff.data.length === 0">
                                <td colspan="8" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-white">
                                            No staff members found
                                        </h3>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ search || selectedStatus || selectedBranch || selectedDepartment || selectedDesignation ? 'No staff matched your active filters.' : 'Get started by onboarding your first staff member.' }}
                                        </p>
                                        <div class="mt-4">
                                            <Button
                                                v-if="!search && !selectedStatus && !selectedBranch && !selectedDepartment && !selectedDesignation"
                                                href="/admin/staff/create"
                                                variant="primary"
                                                size="sm"
                                            >
                                                Add Staff Member
                                            </Button>
                                            <Button
                                                v-else
                                                variant="outline"
                                                size="sm"
                                                @click="resetAllFilters"
                                            >
                                                Reset Filters
                                            </Button>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row Items -->
                            <tr
                                v-for="member in staff.data"
                                :key="member.id"
                                class="group transition-colors hover:bg-zinc-50/75 dark:hover:bg-zinc-800/40"
                            >
                                <!-- Employee Name & Info -->
                                <td class="py-3.5 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-xs font-bold text-white shadow-xs">
                                            {{ getInitials(member.user?.name ?? 'Staff') }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-zinc-900 dark:text-white">
                                                {{ member.user?.name ?? '—' }}
                                            </div>
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                {{ member.user?.email ?? '—' }}
                                                <span v-if="member.user?.phone" class="ml-1 text-zinc-400 dark:text-zinc-500">
                                                    • {{ member.user.phone }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Employee Code -->
                                <td class="px-3 py-3.5 font-mono text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                                    <span class="rounded-md bg-zinc-100 px-2 py-0.5 dark:bg-zinc-800">
                                        {{ member.employee_code }}
                                    </span>
                                </td>

                                <!-- Branch -->
                                <td class="px-3 py-3.5">
                                    <span class="inline-flex items-center rounded-lg bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                        {{ member.branch?.name ?? '—' }}
                                    </span>
                                </td>

                                <!-- Department -->
                                <td class="px-3 py-3.5">
                                    <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                        {{ member.department?.name ?? '—' }}
                                    </span>
                                </td>

                                <!-- Designation -->
                                <td class="px-3 py-3.5">
                                    <span class="inline-flex items-center rounded-lg bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        {{ member.designation?.name ?? '—' }}
                                    </span>
                                </td>

                                <!-- Joining Date -->
                                <td class="px-3 py-3.5 text-zinc-500 dark:text-zinc-400">
                                    {{ formatDate(member.joining_date) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="px-3 py-3.5">
                                    <Badge :variant="getStatusBadgeVariant(member.employment_status)">
                                        {{ member.employment_status.charAt(0).toUpperCase() + member.employment_status.slice(1) }}
                                    </Badge>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 pl-3 pr-4 text-right sm:pr-6 whitespace-nowrap">
                                    <div class="flex items-center justify-end">
                                        <Dropdown align="right" width="w-48">
                                            <template #trigger>
                                                <button
                                                    type="button"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors cursor-pointer"
                                                    title="Actions"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                    </svg>
                                                </button>
                                            </template>
                                            <template #default="{ close }">
                                                <div class="py-1 text-xs">
                                                    <Link
                                                        :href="`/admin/staff/${member.id}/edit`"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left"
                                                        @click="close"
                                                    >
                                                        <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <span>Edit Staff</span>
                                                    </Link>

                                                    <button
                                                        v-if="member.employment_status === 'active'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openSuspendModal(member); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                        <span>Suspend Staff</span>
                                                    </button>

                                                    <button
                                                        v-else
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openActivateModal(member); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>Reactivate Staff</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="staff.total > 0"
                    class="flex flex-col items-center justify-between gap-4 border-t border-zinc-200/70 p-4 dark:border-zinc-800/80 sm:flex-row"
                >
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Showing <span class="font-medium text-zinc-900 dark:text-white">{{ staff.from ?? 0 }}</span> to
                        <span class="font-medium text-zinc-900 dark:text-white">{{ staff.to ?? 0 }}</span> of
                        <span class="font-medium text-zinc-900 dark:text-white">{{ staff.total }}</span> staff members
                    </p>

                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in staff.links" :key="index">
                            <!-- Prev Link -->
                            <template v-if="isPrevious(link.label)">
                                <span
                                    v-if="!link.url"
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200/80 bg-zinc-50/50 text-xs text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-600 cursor-not-allowed select-none"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <span class="hidden sm:inline">Previous</span>
                                </span>
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <span class="hidden sm:inline">Previous</span>
                                </Link>
                            </template>

                            <!-- Next Link -->
                            <template v-else-if="isNext(link.label)">
                                <span
                                    v-if="!link.url"
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200/80 bg-zinc-50/50 text-xs text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-600 cursor-not-allowed select-none"
                                >
                                    <span class="hidden sm:inline">Next</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                                >
                                    <span class="hidden sm:inline">Next</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </template>

                            <!-- Numeric Pages -->
                            <template v-else>
                                <span
                                    v-if="!link.url && !link.active"
                                    class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-xs text-zinc-400 dark:text-zinc-600 select-none"
                                >
                                    ...
                                </span>
                                <span
                                    v-else-if="link.active"
                                    class="inline-flex items-center justify-center h-8 min-w-8 px-2.5 rounded-md bg-indigo-600 font-semibold text-xs text-white shadow-xs shadow-indigo-500/25 select-none"
                                >
                                    {{ cleanLabel(link.label) }}
                                </span>
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    class="inline-flex items-center justify-center h-8 min-w-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                                >
                                    {{ cleanLabel(link.label) }}
                                </Link>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suspend Confirmation Modal -->
        <Modal
            :show="!!suspendingStaff"
            title="Suspend Staff Member"
            description="Are you sure you want to suspend this staff member?"
            @close="closeSuspendModal"
        >
            <div class="space-y-3">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    You are suspending
                    <strong class="font-semibold text-zinc-900 dark:text-white">{{ suspendingStaff?.user?.name }}</strong>
                    <span class="font-mono text-zinc-500">({{ suspendingStaff?.employee_code }})</span>.
                </p>
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-300">
                    <p class="font-medium">Notice:</p>
                    <p class="mt-1">
                        A suspended staff member cannot access organization services or perform duties until reactivated.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isSuspending"
                    @click="closeSuspendModal"
                >
                    Cancel
                </Button>
                <Button
                    variant="danger"
                    size="sm"
                    :loading="isSuspending"
                    @click="confirmSuspend"
                >
                    Suspend Staff
                </Button>
            </template>
        </Modal>

        <!-- Reactivate Confirmation Modal -->
        <Modal
            :show="!!activatingStaff"
            title="Reactivate Staff Member"
            description="Restore this staff member to active employment."
            @close="closeActivateModal"
        >
            <p class="text-xs text-zinc-600 dark:text-zinc-300">
                Are you sure you want to reactivate
                <strong class="font-semibold text-zinc-900 dark:text-white">{{ activatingStaff?.user?.name }}</strong>
                <span class="font-mono text-zinc-500">({{ activatingStaff?.employee_code }})</span>?
                Their status will be returned to active immediately.
            </p>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isActivating"
                    @click="closeActivateModal"
                >
                    Cancel
                </Button>
                <Button
                    variant="success"
                    size="sm"
                    :loading="isActivating"
                    @click="confirmActivate"
                >
                    Reactivate Staff
                </Button>
            </template>
        </Modal>
    </OrganizationLayout>
</template>
