<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

interface TenantRolePermission {
    id: number;
    permission: string;
}

interface TenantRole {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
    permissions?: TenantRolePermission[];
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
    employee_code: string;
    employment_status: string;
    branch?: Branch;
    department?: Department;
    designation?: Designation;
}

interface MemberDetail {
    id: number;
    tenant_id: number;
    user_id: number;
    role_id: number | null;
    status: 'active' | 'invited' | 'suspended' | 'revoked';
    joined_at: string | null;
    invited_at: string | null;
    suspended_at: string | null;
    revoked_at: string | null;
    user: User;
    role?: TenantRole | null;
    invited_by?: User | null;
    suspended_by?: User | null;
    revoked_by?: User | null;
    staff?: TenantStaff | null;
}

const props = defineProps<{
    member: MemberDetail;
    roles: TenantRole[];
}>();

const isEditRoleModalOpen = ref(false);
const isSuspendModalOpen = ref(false);
const isReactivateModalOpen = ref(false);
const isRevokeModalOpen = ref(false);

const editRoleForm = useForm({
    role_id: props.member.role_id ? String(props.member.role_id) : '',
});

const submitEditRole = () => {
    editRoleForm.put(`/admin/members/${props.member.id}`, {
        onSuccess: () => {
            isEditRoleModalOpen.value = false;
        },
    });
};

const confirmSuspend = () => {
    router.patch(`/admin/members/${props.member.id}/suspend`, {}, {
        onSuccess: () => {
            isSuspendModalOpen.value = false;
        },
    });
};

const confirmReactivate = () => {
    router.patch(`/admin/members/${props.member.id}/reactivate`, {}, {
        onSuccess: () => {
            isReactivateModalOpen.value = false;
        },
    });
};

const confirmRevoke = () => {
    router.delete(`/admin/members/${props.member.id}/revoke`, {
        onSuccess: () => {
            isRevokeModalOpen.value = false;
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

const formatDate = (dateStr?: string | null): string => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <OrganizationLayout
        :title="`${member.user.name} - Member Details`"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Members', href: '/admin/members' },
            { label: member.user.name },
        ]"
    >
        <Head :title="`${member.user.name} - Member Profile`" />

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-xl font-bold text-white shadow-md shadow-indigo-500/20">
                            {{ member.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">
                                    {{ member.user.name }}
                                </h1>
                                <Badge :variant="getStatusBadgeVariant(member.status)">
                                    {{ member.status.charAt(0).toUpperCase() + member.status.slice(1) }}
                                </Badge>
                                <span
                                    v-if="member.role"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold"
                                    :class="member.role.is_system ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'"
                                >
                                    {{ member.role.name }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ member.user.email }} • Organization Member #{{ member.id }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" @click="isEditRoleModalOpen = true">
                            <svg class="h-4 w-4 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Role
                        </Button>

                        <Button
                            v-if="!member.staff && member.status === 'active'"
                            :href="`/admin/staff/create?member_id=${member.id}`"
                            variant="primary"
                            size="sm"
                        >
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Create Staff Profile
                        </Button>

                        <Button
                            v-if="member.status === 'active'"
                            variant="outline"
                            size="sm"
                            class="text-amber-600 dark:text-amber-400"
                            @click="isSuspendModalOpen = true"
                        >
                            Suspend
                        </Button>

                        <Button
                            v-if="member.status === 'suspended'"
                            variant="primary"
                            size="sm"
                            @click="isReactivateModalOpen = true"
                        >
                            Reactivate
                        </Button>

                        <Button
                            v-if="member.status !== 'revoked'"
                            variant="danger"
                            size="sm"
                            @click="isRevokeModalOpen = true"
                        >
                            Revoke
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Detail Grid: 3 Distinct Layers -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Layer 1: Account Information (Identity) -->
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                    <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-800">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Account Identity</h2>
                            <p class="text-[11px] text-zinc-400">Global user authentication layer</p>
                        </div>
                    </div>

                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">User ID</dt>
                            <dd class="mt-0.5 font-mono text-zinc-800 dark:text-zinc-200">#{{ member.user.id }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Full Name</dt>
                            <dd class="mt-0.5 font-semibold text-zinc-900 dark:text-white">{{ member.user.name }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Email Address</dt>
                            <dd class="mt-0.5 font-mono text-zinc-800 dark:text-zinc-200">{{ member.user.email }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Platform Registered</dt>
                            <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">{{ formatDate(member.user.created_at) }}</dd>
                        </div>
                    </dl>

                    <div class="rounded-xl bg-zinc-50 p-3 text-[11px] text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                        Single-User Architecture: Global user identity is preserved without duplicating credentials across organizations.
                    </div>
                </div>

                <!-- Layer 2: Organization Access & Role -->
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                    <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-800">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Organization Access</h2>
                            <p class="text-[11px] text-zinc-400">Role and permission entitlements</p>
                        </div>
                    </div>

                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Assigned Role</dt>
                            <dd class="mt-0.5 flex items-center gap-2">
                                <span class="font-semibold text-zinc-900 dark:text-white">
                                    {{ member.role?.name ?? 'None' }}
                                </span>
                                <span
                                    v-if="member.role?.is_system"
                                    class="rounded-sm bg-purple-50 px-1.5 py-0.5 text-[10px] font-bold text-purple-700 dark:bg-purple-950/70 dark:text-purple-300"
                                >
                                    SYSTEM ROLE
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Access Status</dt>
                            <dd class="mt-0.5">
                                <Badge :variant="getStatusBadgeVariant(member.status)">
                                    {{ member.status.toUpperCase() }}
                                </Badge>
                            </dd>
                        </div>
                        <div v-if="member.joined_at">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Joined Organization</dt>
                            <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">{{ formatDate(member.joined_at) }}</dd>
                        </div>
                        <div v-if="member.invited_at">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Invited Date</dt>
                            <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">{{ formatDate(member.invited_at) }}</dd>
                        </div>
                        <div v-if="member.invited_by">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Invited By</dt>
                            <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">{{ member.invited_by.name }} ({{ member.invited_by.email }})</dd>
                        </div>
                    </dl>

                    <div v-if="member.role?.permissions && member.role.permissions.length > 0" class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <p class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 mb-2">Granted Permissions ({{ member.role.permissions.length }})</p>
                        <div class="flex flex-wrap gap-1 max-h-32 overflow-y-auto">
                            <span
                                v-for="p in member.role.permissions"
                                :key="p.id"
                                class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                {{ p.permission }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Layer 3: Employment Profile (Staff) -->
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                    <div class="flex items-center gap-2 border-b border-zinc-100 pb-3 dark:border-zinc-800">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">HRM Employment Profile</h2>
                            <p class="text-[11px] text-zinc-400">Staff directory & organization assignment</p>
                        </div>
                    </div>

                    <!-- If Staff Record Exists -->
                    <div v-if="member.staff" class="space-y-3 text-xs">
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Employee Code</dt>
                            <dd class="mt-0.5 font-mono font-semibold text-zinc-900 dark:text-white">
                                {{ member.staff.employee_code }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Branch</dt>
                            <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                                {{ member.staff.branch?.name ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Department</dt>
                            <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                                {{ member.staff.department?.name ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Designation</dt>
                            <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">
                                {{ member.staff.designation?.name ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Employment Status</dt>
                            <dd class="mt-0.5">
                                <Badge variant="success">
                                    {{ member.staff.employment_status.toUpperCase() }}
                                </Badge>
                            </dd>
                        </div>

                        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <Link
                                :href="`/admin/staff/${member.staff.id}/edit`"
                                class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                            >
                                Edit Staff Profile in HRM &rarr;
                            </Link>
                        </div>
                    </div>

                    <!-- If NO Staff Record Exists -->
                    <div v-else class="space-y-4 text-center py-4">
                        <div class="flex h-12 w-12 mx-auto items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Not Registered as Staff
                            </p>
                            <p class="mt-1 text-[11px] text-zinc-400">
                                This user is an organization member but does not have an employee staff profile.
                            </p>
                        </div>

                        <Button
                            v-if="member.status === 'active'"
                            :href="`/admin/staff/create?member_id=${member.id}`"
                            variant="primary"
                            size="sm"
                            class="w-full"
                        >
                            Create Staff Profile
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Role Modal -->
        <Modal
            :show="isEditRoleModalOpen"
            title="Change Organization Role"
            max-width="md"
            @close="isEditRoleModalOpen = false"
        >
            <form @submit.prevent="submitEditRole" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Select New Role
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
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" type="button" @click="isEditRoleModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="primary" size="sm" type="submit" :disabled="editRoleForm.processing">
                        {{ editRoleForm.processing ? 'Saving...' : 'Save Role' }}
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
                    Are you sure you want to suspend access for <strong>{{ member.user.name }}</strong>?
                    They will immediately lose access to this organization.
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
                    Are you sure you want to reactivate access for <strong>{{ member.user.name }}</strong>?
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
                    Are you sure you want to revoke membership for <strong>{{ member.user.name }}</strong>?
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
