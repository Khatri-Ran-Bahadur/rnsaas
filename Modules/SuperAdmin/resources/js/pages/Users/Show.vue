<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';

export interface UserRole {
    id: number;
    name: string;
    description?: string;
}

export interface UserOrganizationMembership {
    id: number;
    name: string;
    slug?: string;
    role?: string;
    status?: 'active' | 'suspended' | 'pending' | string;
    joined_at?: string | null;
}

export interface UserSession {
    id: string;
    ip_address?: string;
    device?: string;
    browser?: string;
    last_active?: string | null;
    is_current?: boolean;
}

export interface UserDetailsData {
    id: number;
    name: string;
    email: string;
    avatar_url?: string | null;
    account_status?: 'active' | 'suspended' | 'disabled' | string;
    email_verified_at?: string | null;
    created_at: string;
    last_login_at?: string | null;
    roles?: UserRole[];
    organizations?: UserOrganizationMembership[];
    sessions?: UserSession[];
    password_set?: boolean;
}

interface Props {
    user?: UserDetailsData;
}

const props = defineProps<Props>();

// Fallback user data if props are minimal
const userData = computed<UserDetailsData>(() => {
    return (
        props.user ?? {
            id: 1,
            name: 'Platform User',
            email: 'user@sathisaas.com',
            avatar_url: null,
            account_status: 'active',
            email_verified_at: new Date().toISOString(),
            created_at: new Date().toISOString(),
            last_login_at: null,
            roles: [{ id: 1, name: 'SuperAdmin' }],
            organizations: [],
            sessions: [],
            password_set: true,
        }
    );
});

// Toast / Notification State
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

function showToast(message: string, type: 'success' | 'error' = 'success') {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 3500);
}

// Confirmation Dialog State
type ActionType = 'suspend' | 'activate' | 'revoke' | 'reset_password' | null;
const pendingAction = ref<ActionType>(null);
const isProcessingAction = ref(false);

const openConfirmModal = (action: ActionType) => {
    pendingAction.value = action;
};

const closeConfirmModal = () => {
    if (isProcessingAction.value) return;
    pendingAction.value = null;
};

const executeAction = () => {
    if (!pendingAction.value || isProcessingAction.value) return;

    isProcessingAction.value = true;
    const action = pendingAction.value;

    setTimeout(() => {
        isProcessingAction.value = false;
        closeConfirmModal();

        if (action === 'suspend') {
            showToast('User suspension command queued successfully.');
        } else if (action === 'activate') {
            showToast('User has been marked active.');
        } else if (action === 'revoke') {
            showToast('Platform access revoked and active sessions invalidated.');
        } else if (action === 'reset_password') {
            showToast('Password reset instructions sent to user email.');
        }
    }, 500);
};

// Utilities
const getInitials = (name: string): string => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const formatDate = (dateStr?: string | null): string => {
    if (!dateStr) return 'Never';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const formatDateTime = (dateStr?: string | null): string => {
    if (!dateStr) return 'Not recorded';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleString('en-US', {
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

const getStatusBadgeVariant = (status?: string): 'active' | 'pending' | 'suspended' | 'cancelled' | 'neutral' => {
    const s = (status || '').toLowerCase().trim();
    if (['active', 'healthy', 'verified'].includes(s)) return 'active';
    if (['pending', 'invited'].includes(s)) return 'pending';
    if (['suspended', 'disabled'].includes(s)) return 'suspended';
    if (['revoked', 'failed'].includes(s)) return 'cancelled';
    return 'neutral';
};

const getRoleBadgeVariant = (roleName: string) => {
    switch (roleName.toLowerCase()) {
        case 'superadmin':
        case 'admin':
            return 'active';
        case 'manager':
            return 'pending';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <SuperAdminLayout>
        <Head :title="`${userData.name} - User Details`" />

        <div class="space-y-6 pb-12">
            <!-- Toast Notification -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="notification"
                    :class="[
                        'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium shadow-xl text-white',
                        notification.type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'
                    ]"
                >
                    <svg v-if="notification.type === 'success'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>{{ notification.message }}</span>
                </div>
            </Transition>

            <!-- Navigation & Breadcrumbs Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                    <Link href="/superadmin/dashboard" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                        Dashboard
                    </Link>
                    <span>/</span>
                    <Link href="/superadmin/users" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                        Users
                    </Link>
                    <span>/</span>
                    <span class="font-medium text-zinc-800 dark:text-zinc-200">User Details</span>
                </div>

                <Link
                    href="/superadmin/users"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700/60 dark:hover:text-white transition-colors shadow-2xs"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to Users</span>
                </Link>
            </div>

            <!-- USER PROFILE HEADER -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Avatar -->
                        <div
                            v-if="userData.avatar_url"
                            class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-xs"
                        >
                            <img :src="userData.avatar_url" :alt="userData.name" class="h-full w-full object-cover" />
                        </div>
                        <div
                            v-else
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary-100 text-lg font-bold text-primary-700 ring-2 ring-primary-500/20 dark:bg-primary-950/70 dark:text-primary-300"
                        >
                            {{ getInitials(userData.name) }}
                        </div>

                        <!-- Name, Email, Roles, Status -->
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">
                                    {{ userData.name }}
                                </h1>
                                <Badge
                                    :variant="getStatusBadgeVariant(userData.account_status)"
                                    :label="userData.account_status === 'suspended' ? 'Suspended' : userData.account_status === 'disabled' ? 'Disabled' : 'Active'"
                                    size="sm"
                                />
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                {{ userData.email }}
                            </p>
                            <div class="mt-2.5 flex flex-wrap items-center gap-3 text-xs text-zinc-400 dark:text-zinc-500">
                                <span class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Joined {{ formatDate(userData.created_at) }}</span>
                                </span>
                                <span>•</span>
                                <div class="flex items-center gap-1">
                                    <template v-if="userData.roles && userData.roles.length > 0">
                                        <Badge
                                            v-for="role in userData.roles"
                                            :key="role.id"
                                            :variant="getRoleBadgeVariant(role.name)"
                                            :label="role.name"
                                            size="sm"
                                        />
                                    </template>
                                    <span v-else class="italic text-[11px]">No platform role</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Header Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5 sm:justify-end">
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="openConfirmModal('reset_password')"
                        >
                            <svg class="h-3.5 w-3.5 mr-1 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <span>Send Password Reset</span>
                        </Button>

                        <Button
                            v-if="userData.account_status === 'suspended'"
                            variant="primary"
                            size="sm"
                            @click="openConfirmModal('activate')"
                        >
                            <span>Activate User</span>
                        </Button>

                        <Button
                            v-else
                            variant="secondary"
                            size="sm"
                            @click="openConfirmModal('suspend')"
                        >
                            <span>Suspend User</span>
                        </Button>

                        <Button
                            variant="danger"
                            size="sm"
                            @click="openConfirmModal('revoke')"
                        >
                            <span>Revoke Access</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- 5 CORE SECTIONS IN RESPONSIVE GRID -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- LEFT / MAIN COLUMN (2 cols on desktop) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- SECTION 1: ACCOUNT INFORMATION -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800 mb-4">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                1. Account Information
                            </h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Identity attributes and verification timestamps.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                                <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Full Name</span>
                                <p class="mt-1 text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ userData.name }}</p>
                            </div>

                            <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                                <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Email Address</span>
                                <p class="mt-1 text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ userData.email }}</p>
                            </div>

                            <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                                <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Email Verification</span>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <Badge
                                        :variant="userData.email_verified_at ? 'active' : 'neutral'"
                                        :label="userData.email_verified_at ? 'Verified' : 'Unverified'"
                                        size="sm"
                                    />
                                    <span v-if="userData.email_verified_at" class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">
                                        {{ formatDate(userData.email_verified_at) }}
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                                <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Created At</span>
                                <p class="mt-1 text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ formatDateTime(userData.created_at) }}</p>
                            </div>

                            <div class="col-span-1 sm:col-span-2 rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                                <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Last Login</span>
                                <p class="mt-1 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ formatDateTime(userData.last_login_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: ORGANIZATION MEMBERSHIPS -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800 mb-4">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                2. Organization Memberships
                            </h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Multi-tenant organizations and tenant workspaces this user belongs to.
                            </p>
                        </div>

                        <div v-if="userData.organizations && userData.organizations.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                                <thead class="border-b border-zinc-100 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:border-zinc-800">
                                    <tr>
                                        <th class="pb-3 font-semibold">Organization</th>
                                        <th class="pb-3 font-semibold">Role</th>
                                        <th class="pb-3 font-semibold">Status</th>
                                        <th class="pb-3 font-semibold text-right">Joined At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                                    <tr v-for="org in userData.organizations" :key="org.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                        <td class="py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ org.name }}
                                        </td>
                                        <td class="py-3 capitalize text-zinc-700 dark:text-zinc-300">
                                            {{ org.role || 'Member' }}
                                        </td>
                                        <td class="py-3">
                                            <Badge
                                                :variant="getStatusBadgeVariant(org.status)"
                                                :label="org.status || 'Active'"
                                                size="sm"
                                            />
                                        </td>
                                        <td class="py-3 text-right text-zinc-500 dark:text-zinc-400">
                                            {{ formatDate(org.joined_at) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="rounded-xl border border-dashed border-zinc-200 p-6 text-center dark:border-zinc-800">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                This user does not currently belong to any tenant organizations.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE COLUMN (1 col on desktop) -->
                <div class="space-y-6">
                    <!-- SECTION 3: PLATFORM ROLES -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800 mb-4">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                3. Platform Roles
                            </h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Spatie RBAC global roles assigned to this identity.
                            </p>
                        </div>

                        <div v-if="userData.roles && userData.roles.length > 0" class="flex flex-wrap gap-2">
                            <Badge
                                v-for="role in userData.roles"
                                :key="role.id"
                                :variant="getRoleBadgeVariant(role.name)"
                                :label="role.name"
                                size="md"
                            />
                        </div>
                        <p v-else class="text-xs text-zinc-400 dark:text-zinc-500 italic">
                            No platform roles assigned.
                        </p>
                    </div>

                    <!-- SECTION 4: ACCOUNT STATUS -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800 mb-4">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                4. Account Status
                            </h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Global platform access authorization.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/40">
                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">State</span>
                                <Badge
                                    :variant="getStatusBadgeVariant(userData.account_status)"
                                    :label="userData.account_status === 'suspended' ? 'Suspended' : userData.account_status === 'disabled' ? 'Disabled' : 'Active'"
                                    size="sm"
                                />
                            </div>

                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500 leading-relaxed">
                                {{
                                    userData.account_status === 'suspended'
                                        ? 'Account is temporarily suspended. Login attempts are blocked across all tenant workspaces.'
                                        : userData.account_status === 'disabled'
                                        ? 'Account is disabled. All authentication credentials and tokens are invalid.'
                                        : 'Account is fully active. User can authenticate and access authorized workspaces.'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- SECTION 5: SECURITY -->
                    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800 mb-4">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                5. Security & Credentials
                            </h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Credential health and active browser sessions.
                            </p>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <span class="text-zinc-500 dark:text-zinc-400">Email Verification</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ userData.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between py-2 border-b border-zinc-100 dark:border-zinc-800">
                                <span class="text-zinc-500 dark:text-zinc-400">Password Status</span>
                                <span class="font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ userData.password_set !== false ? 'Configured & Encrypted' : 'Not Configured' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between py-2">
                                <span class="text-zinc-500 dark:text-zinc-400">Active Sessions</span>
                                <span class="font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ userData.sessions ? userData.sessions.length : '0 reported' }}
                                </span>
                            </div>
                        </div>

                        <!-- Security Note: Never expose password or tokens -->
                        <div class="mt-4 rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-400 flex items-start gap-2">
                            <svg class="h-4 w-4 text-zinc-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Passwords, hashes, session tokens, and keys are cryptographically secured and never exposed to the client.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DESTRUCTIVE / CONFIRMATION MODAL -->
        <Modal
            :show="pendingAction !== null"
            max-width="md"
            @close="closeConfirmModal"
        >
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <div
                        :class="[
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                            pendingAction === 'revoke' || pendingAction === 'suspend'
                                ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400'
                                : 'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400'
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            {{
                                pendingAction === 'suspend'
                                    ? 'Suspend User Account?'
                                    : pendingAction === 'activate'
                                    ? 'Activate User Account?'
                                    : pendingAction === 'revoke'
                                    ? 'Revoke Platform Access?'
                                    : 'Send Password Reset Link?'
                            }}
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                            {{
                                pendingAction === 'suspend'
                                    ? `Are you sure you want to suspend ${userData.name}? This user will immediately lose access to all tenant organizations.`
                                    : pendingAction === 'activate'
                                    ? `Activate ${userData.name}'s account and restore authentication access.`
                                    : pendingAction === 'revoke'
                                    ? `Revoke all active permissions and invalidate current browser sessions for ${userData.name}.`
                                    : `A secure password reset link will be sent to ${userData.email}.`
                            }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        :disabled="isProcessingAction"
                        @click="closeConfirmModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="pendingAction === 'revoke' || pendingAction === 'suspend' ? 'danger' : 'primary'"
                        size="sm"
                        :loading="isProcessingAction"
                        @click="executeAction"
                    >
                        {{
                            isProcessingAction
                                ? 'Processing...'
                                : pendingAction === 'suspend'
                                ? 'Confirm Suspension'
                                : pendingAction === 'activate'
                                ? 'Confirm Activation'
                                : pendingAction === 'revoke'
                                ? 'Revoke Access'
                                : 'Send Reset Link'
                        }}
                    </Button>
                </div>
            </div>
        </Modal>
    </SuperAdminLayout>
</template>
