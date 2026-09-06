<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';

interface User {
    id: number;
    name: string;
    email: string;
    avatar_url?: string | null;
}

interface TenantRole {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
}

interface TenantStaff {
    id: number;
    employee_code: string;
    employment_status: string;
}

interface MemberItem {
    id: number;
    tenant_id: number;
    user_id: number;
    role_id: number | null;
    status: 'active' | 'invited' | 'suspended' | 'revoked';
    joined_at: string | null;
    invited_at: string | null;
    expires_at: string | null;
    user: User;
    role?: TenantRole | null;
    staff?: TenantStaff | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedMembers {
    data: MemberItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

const props = defineProps<{
    members: PaginatedMembers;
    roles: TenantRole[];
    filters: {
        search?: string;
        status?: string;
        role_id?: number | null;
        is_staff?: string;
        per_page?: number;
    };
}>();

const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const selectedRoleId = ref<number | ''>(props.filters.role_id ?? '');
const selectedStaffFilter = ref(props.filters.is_staff ?? '');
const perPage = ref(props.filters.per_page ?? props.members.per_page ?? 15);
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        '/admin/members',
        {
            search: search.value.trim() || undefined,
            status: selectedStatus.value || undefined,
            role_id: selectedRoleId.value || undefined,
            is_staff: selectedStaffFilter.value || undefined,
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

const resetAllFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedRoleId.value = '';
    selectedStaffFilter.value = '';
    applyFilters();
};

// Modals state
const isAddModalOpen = ref(false);
const isEditRoleModalOpen = ref(false);
const isSuspendModalOpen = ref(false);
const isReactivateModalOpen = ref(false);
const isRevokeModalOpen = ref(false);

const activeMember = ref<MemberItem | null>(null);

const addForm = useForm({
    email: '',
    name: '',
    role_id: props.roles.length > 0 ? props.roles[0].id : '',
    status: 'active',
});

const editRoleForm = useForm({
    role_id: '',
});

const openAddModal = () => {
    addForm.reset();
    addForm.clearErrors();
    if (props.roles.length > 0) {
        addForm.role_id = props.roles[0].id;
    }
    isAddModalOpen.value = true;
};

const submitAddMember = () => {
    addForm.post('/admin/members', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        },
    });
};

const openEditRoleModal = (member: MemberItem) => {
    activeMember.value = member;
    editRoleForm.clearErrors();
    editRoleForm.role_id = member.role_id ? String(member.role_id) : (props.roles.length > 0 ? String(props.roles[0].id) : '');
    isEditRoleModalOpen.value = true;
};

const submitEditRole = () => {
    if (!activeMember.value) return;
    editRoleForm.put(`/admin/members/${activeMember.value.id}`, {
        onSuccess: () => {
            isEditRoleModalOpen.value = false;
            activeMember.value = null;
        },
    });
};

const openSuspendModal = (member: MemberItem) => {
    activeMember.value = member;
    isSuspendModalOpen.value = true;
};

const confirmSuspend = () => {
    if (!activeMember.value) return;
    router.patch(`/admin/members/${activeMember.value.id}/suspend`, {}, {
        onSuccess: () => {
            isSuspendModalOpen.value = false;
            activeMember.value = null;
        },
    });
};

const openReactivateModal = (member: MemberItem) => {
    activeMember.value = member;
    isReactivateModalOpen.value = true;
};

const confirmReactivate = () => {
    if (!activeMember.value) return;
    router.patch(`/admin/members/${activeMember.value.id}/reactivate`, {}, {
        onSuccess: () => {
            isReactivateModalOpen.value = false;
            activeMember.value = null;
        },
    });
};

const openRevokeModal = (member: MemberItem) => {
    activeMember.value = member;
    isRevokeModalOpen.value = true;
};

const confirmRevoke = () => {
    if (!activeMember.value) return;
    router.delete(`/admin/members/${activeMember.value.id}/revoke`, {
        onSuccess: () => {
            isRevokeModalOpen.value = false;
            activeMember.value = null;
        },
    });
};

const getStatusBadgeVariant = (status: string): 'success' | 'warning' | 'danger' | 'info' | 'default' => {
    switch (status) {
        case 'active':
            return 'success';
        case 'invited':
            return 'warning';
        case 'suspended':
            return 'danger';
        case 'revoked':
            return 'default';
        default:
            return 'default';
    }
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatDate = (dateStr?: string | null): string => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <OrganizationLayout
        title="Organization Members"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Users & Access' },
            { label: 'Members' },
        ]"
    >
        <Head title="Organization Members" />

        <div class="space-y-6">
            <!-- Header with Title & Summary -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Members
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        View and manage active accounts, organization roles, and employment profiles.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        Total Members: {{ members.total }}
                    </span>

                    <Button variant="primary" size="sm" @click="openAddModal">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Member
                    </Button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <!-- Search Input -->
                    <div class="relative lg:col-span-2">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name or email..."
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 pl-10 pr-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100 dark:focus:border-indigo-500"
                            @input="onSearchInput"
                        />
                    </div>

                    <!-- Role Filter -->
                    <div>
                        <select
                            v-model="selectedRoleId"
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                            @change="applyFilters"
                        >
                            <option value="">All Roles</option>
                            <option v-for="r in roles" :key="r.id" :value="r.id">
                                {{ r.name }} {{ r.is_system ? '(System)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select
                            v-model="selectedStatus"
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                            @change="applyFilters"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="invited">Invited</option>
                            <option value="suspended">Suspended</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>

                    <!-- Staff Filter -->
                    <div>
                        <select
                            v-model="selectedStaffFilter"
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                            @change="applyFilters"
                        >
                            <option value="">All Staff Status</option>
                            <option value="yes">Staff Members</option>
                            <option value="no">Non-Staff Members</option>
                        </select>
                    </div>
                </div>

                <!-- Reset Filters Row -->
                <div v-if="search || selectedStatus || selectedRoleId || selectedStaffFilter" class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800/80">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        Filters active
                    </span>
                    <button
                        type="button"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 cursor-pointer"
                        @click="resetAllFilters"
                    >
                        Clear all filters
                    </button>
                </div>
            </div>

            <!-- Members Data Table -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-left text-xs dark:divide-zinc-800">
                        <thead class="bg-zinc-50/75 dark:bg-zinc-950/60">
                            <tr>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Member</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Email</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Role</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Staff Profile</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Joined / Invited</th>
                                <th class="px-6 py-3.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="member in members.data"
                                :key="member.id"
                                class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300">
                                            {{ getInitials(member.user.name) }}
                                        </div>
                                        <div>
                                            <Link
                                                :href="`/admin/members/${member.id}`"
                                                class="font-semibold text-zinc-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400"
                                            >
                                                {{ member.user.name }}
                                            </Link>
                                            <p class="text-[11px] text-zinc-400">User #{{ member.user.id }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 font-mono text-zinc-600 dark:text-zinc-300">
                                    {{ member.user.email }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        v-if="member.role"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-medium"
                                        :class="member.role.is_system ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                                    >
                                        {{ member.role.name }}
                                        <span v-if="member.role.is_system" class="text-[9px] uppercase font-bold text-purple-600 dark:text-purple-400">Sys</span>
                                    </span>
                                    <span v-else class="text-zinc-400">—</span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <Badge :variant="getStatusBadgeVariant(member.status)">
                                        {{ member.status.charAt(0).toUpperCase() + member.status.slice(1) }}
                                    </Badge>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        v-if="member.staff"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                                    >
                                        <svg class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Staff ({{ member.staff.employee_code }})
                                    </span>
                                    <span v-else class="inline-flex items-center text-[11px] text-zinc-400 dark:text-zinc-500">
                                        Not Staff
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                    {{ formatDate(member.joined_at ?? member.invited_at) }}
                                </td>

                                <!-- Actions Dropdown -->
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <Dropdown align="right" width="w-52">
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
                                                    <!-- View Details -->
                                                    <Link
                                                        :href="`/admin/members/${member.id}`"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left"
                                                        @click="close"
                                                    >
                                                        <svg class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        <span>View Details</span>
                                                    </Link>

                                                    <!-- Edit Role -->
                                                    <button
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openEditRoleModal(member); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <span>Edit Role</span>
                                                    </button>

                                                    <!-- Create Staff Profile if not staff -->
                                                    <Link
                                                        v-if="!member.staff && member.status === 'active'"
                                                        :href="`/admin/staff/create?member_id=${member.id}`"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-950/40 transition-colors rounded-md text-left"
                                                        @click="close"
                                                    >
                                                        <svg class="h-4 w-4 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                                        </svg>
                                                        <span>Create Staff Profile</span>
                                                    </Link>

                                                    <!-- Suspend Member -->
                                                    <button
                                                        v-if="member.status === 'active'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openSuspendModal(member); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                        <span>Suspend Member</span>
                                                    </button>

                                                    <!-- Reactivate Member -->
                                                    <button
                                                        v-if="member.status === 'suspended'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openReactivateModal(member); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span>Reactivate Member</span>
                                                    </button>

                                                    <!-- Revoke Member -->
                                                    <button
                                                        v-if="member.status !== 'revoked'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openRevokeModal(member); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        <span>Revoke Access</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="members.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">No members found</p>
                                        <p class="mt-1 text-xs text-zinc-400">
                                            {{ search || selectedStatus || selectedRoleId || selectedStaffFilter ? 'Try adjusting your filters.' : 'Add your first organization member to get started.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="flex flex-col items-center justify-between gap-4 border-t border-zinc-200 px-6 py-4 sm:flex-row dark:border-zinc-800">
                    <PerPageSelector
                        v-model="perPage"
                        @change="applyFilters"
                    />

                    <div v-if="members.links && members.links.length > 3">
                        <Pagination :links="members.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Member Modal -->
        <Modal
            :show="isAddModalOpen"
            title="Add Organization Member"
            max-width="lg"
            @close="isAddModalOpen = false"
        >
            <form @submit.prevent="submitAddMember" class="space-y-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    If this user already exists in SathiSaaS, their global profile will be linked without duplicating accounts.
                </p>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Email Address <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="addForm.email"
                        type="email"
                        required
                        placeholder="colleague@example.com"
                        class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                    />
                    <p v-if="addForm.errors.email" class="mt-1 text-xs text-rose-500">
                        {{ addForm.errors.email }}
                    </p>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Full Name <span class="text-zinc-400 font-normal">(Required if new user)</span>
                    </label>
                    <input
                        v-model="addForm.name"
                        type="text"
                        placeholder="John Doe"
                        class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                    />
                    <p v-if="addForm.errors.name" class="mt-1 text-xs text-rose-500">
                        {{ addForm.errors.name }}
                    </p>
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Organization Role <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="addForm.role_id"
                        required
                        class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                    >
                        <option v-for="r in roles" :key="r.id" :value="r.id">
                            {{ r.name }} {{ r.is_system ? '(System)' : '' }}
                        </option>
                    </select>
                    <p v-if="addForm.errors.role_id" class="mt-1 text-xs text-rose-500">
                        {{ addForm.errors.role_id }}
                    </p>
                </div>

                <!-- Status Mode -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Access Mode
                    </label>
                    <div class="mt-2 flex gap-4">
                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                v-model="addForm.status"
                                type="radio"
                                value="active"
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>Add Directly (Active)</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                v-model="addForm.status"
                                type="radio"
                                value="invited"
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                            <span>Send Invitation (Invited)</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" type="button" @click="isAddModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="primary" size="sm" type="submit" :disabled="addForm.processing">
                        {{ addForm.processing ? 'Saving...' : 'Add Member' }}
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Edit Role Modal -->
        <Modal
            :show="isEditRoleModalOpen"
            title="Edit Member Role"
            max-width="md"
            @close="isEditRoleModalOpen = false"
        >
            <form @submit.prevent="submitEditRole" class="space-y-4">
                <p v-if="activeMember" class="text-xs text-zinc-600 dark:text-zinc-300">
                    Update the assigned organization role for <strong>{{ activeMember.user.name }}</strong>.
                </p>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Select Role
                    </label>
                    <select
                        v-model="editRoleForm.role_id"
                        required
                        class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                    >
                        <option v-for="r in roles" :key="r.id" :value="r.id">
                            {{ r.name }} {{ r.is_system ? '(System Role)' : '' }}
                        </option>
                    </select>
                    <p v-if="editRoleForm.errors.role_id" class="mt-1 text-xs text-rose-500">
                        {{ editRoleForm.errors.role_id }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" type="button" @click="isEditRoleModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="primary" size="sm" type="submit" :disabled="editRoleForm.processing">
                        {{ editRoleForm.processing ? 'Updating...' : 'Update Role' }}
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Suspend Modal -->
        <Modal
            :show="isSuspendModalOpen"
            title="Suspend Member Access"
            max-width="md"
            @close="isSuspendModalOpen = false"
        >
            <div class="space-y-4">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    Are you sure you want to suspend access for <strong v-if="activeMember">{{ activeMember.user.name }}</strong>?
                    They will immediately lose access to this organization until reactivated.
                </p>
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" @click="isSuspendModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="danger" size="sm" @click="confirmSuspend">
                        Suspend Access
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Reactivate Modal -->
        <Modal
            :show="isReactivateModalOpen"
            title="Reactivate Member Access"
            max-width="md"
            @close="isReactivateModalOpen = false"
        >
            <div class="space-y-4">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    Are you sure you want to reactivate access for <strong v-if="activeMember">{{ activeMember.user.name }}</strong>?
                    They will regain access according to their assigned role.
                </p>
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" @click="isReactivateModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="primary" size="sm" @click="confirmReactivate">
                        Reactivate Member
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Revoke Modal -->
        <Modal
            :show="isRevokeModalOpen"
            title="Revoke Member Access"
            max-width="md"
            @close="isRevokeModalOpen = false"
        >
            <div class="space-y-4">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    Are you sure you want to revoke membership for <strong v-if="activeMember">{{ activeMember.user.name }}</strong>?
                    Their organization membership will be terminated.
                </p>
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" @click="isRevokeModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="danger" size="sm" @click="confirmRevoke">
                        Revoke Membership
                    </Button>
                </div>
            </div>
        </Modal>
    </OrganizationLayout>
</template>
