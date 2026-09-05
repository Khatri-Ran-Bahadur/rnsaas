<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';

// ─── Types ────────────────────────────────────────────────────────────────────

export interface PermissionItem {
    id: number;
    name: string;
    label: string;
}

export interface PermissionGroup {
    key: string;
    label: string;
    permissions: PermissionItem[];
}

export interface RoleItem {
    id: number;
    name: string;
    guard_name: string;
    users_count: number;
    permissions_count: number;
    is_system: boolean;
    permissions: string[];
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    roles: RoleItem[];
    permission_groups: PermissionGroup[];
}>();

// ─── Stats ────────────────────────────────────────────────────────────────────

const totalPermissions = computed(() =>
    props.permission_groups.reduce((acc, g) => acc + g.permissions.length, 0),
);

const systemRoles = computed(() => props.roles.filter((r) => r.is_system).length);

const customRoles = computed(() => props.roles.filter((r) => !r.is_system).length);

// ─── Delete Role ──────────────────────────────────────────────────────────────

const showDeleteModal = ref(false);
const deletingRole = ref<RoleItem | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (role: RoleItem) => {
    deletingRole.value = role;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingRole.value = null;
};

const confirmDelete = () => {
    if (!deletingRole.value) return;
    isDeleting.value = true;
    router.delete(`/superadmin/roles/${deletingRole.value.id}`, {
        onSuccess: () => closeDeleteModal(),
        onFinish: () => (isDeleting.value = false),
    });
};
</script>

<template>
    <SuperAdminLayout
        title="Role Management"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Role Management' },
        ]"
    >
        <!-- ─── Page Header ─────────────────────────────────────────────── -->
        <div class="mb-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Role Management
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Define platform roles and assign granular permissions to control administrative access.
                    </p>
                </div>
                <Link
                    id="create-role-btn"
                    href="/superadmin/roles/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs shadow-primary-500/20 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Role
                </Link>
            </div>
        </div>

        <!-- ─── Stats ──────────────────────────────────────────────────── -->
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Roles</p>
                <p class="mt-1.5 text-3xl font-bold text-zinc-900 dark:text-white">{{ roles.length }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Custom Roles</p>
                <p class="mt-1.5 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ customRoles }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">System Roles</p>
                <p class="mt-1.5 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ systemRoles }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Permissions</p>
                <p class="mt-1.5 text-3xl font-bold text-zinc-900 dark:text-white">{{ totalPermissions }}</p>
            </div>
        </div>

        <!-- ─── Roles Table ────────────────────────────────────────────── -->
        <div class="rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Platform Roles</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ roles.length }} role{{ roles.length !== 1 ? 's' : '' }} configured in system
                </p>
            </div>

            <!-- Empty State -->
            <div v-if="roles.length === 0" class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <h3 class="mt-3 text-sm font-medium text-zinc-900 dark:text-white">No roles found</h3>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Get started by creating your first platform role.</p>
                <Link
                    href="/superadmin/roles/create"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create First Role
                </Link>
            </div>

            <!-- Table -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-950/40">
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Role Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Users
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Permissions
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Type
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        <tr
                            v-for="role in roles"
                            :key="role.id"
                            class="group transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
                        >
                            <!-- Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold',
                                            role.is_system
                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                                : 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-400',
                                        ]"
                                    >
                                        {{ role.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                            {{ role.name }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            guard: {{ role.guard_name }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Users count -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                                    <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ role.users_count }}
                                </span>
                            </td>

                            <!-- Permissions count -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                                    <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    {{ role.permissions_count }}
                                </span>
                            </td>

                            <!-- Type badge -->
                            <td class="px-6 py-4">
                                <span
                                    v-if="role.is_system"
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                >
                                    System
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                >
                                    Custom
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit Link (or disabled span for system roles) -->
                                    <Link
                                        v-if="!role.is_system"
                                        :id="`edit-role-${role.id}`"
                                        :href="`/superadmin/roles/${role.id}/edit`"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 transition-colors dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </Link>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-300 dark:text-zinc-700 cursor-not-allowed"
                                        title="System roles cannot be edited"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </span>

                                    <!-- Delete Button -->
                                    <button
                                        :id="`delete-role-${role.id}`"
                                        type="button"
                                        :disabled="role.is_system"
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
                                            role.is_system
                                                ? 'cursor-not-allowed text-zinc-300 dark:text-zinc-700'
                                                : 'text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:text-rose-500 dark:hover:bg-rose-950/40 dark:hover:text-rose-400',
                                        ]"
                                        @click="!role.is_system && openDeleteModal(role)"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ─── Delete Confirmation Modal ─────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-zinc-900/60 backdrop-blur-sm" @click="closeDeleteModal" />
                <div class="relative w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900/30">
                            <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Delete Role</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Are you sure you want to delete
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ deletingRole?.name }}</span>?
                                This action cannot be undone and may affect users assigned to this role.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors"
                            @click="closeDeleteModal"
                        >
                            Cancel
                        </button>
                        <button
                            id="confirm-delete-role"
                            type="button"
                            :disabled="isDeleting"
                            class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60 transition-colors"
                            @click="confirmDelete"
                        >
                            <svg v-if="isDeleting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ isDeleting ? 'Deleting...' : 'Delete Role' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </SuperAdminLayout>
</template>
