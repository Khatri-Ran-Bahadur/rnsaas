<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Dropdown from '@/components/Dropdown.vue';

interface RolePermission {
    id: number;
    permission: string;
}

interface TenantRole {
    id: number;
    tenant_id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    is_active: boolean;
    members_count: number;
    permissions: RolePermission[];
}

const props = defineProps<{
    roles: TenantRole[];
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search ?? '');

const filteredRoles = computed(() => {
    if (!search.value.trim()) return props.roles;
    const q = search.value.toLowerCase();
    return props.roles.filter(
        (r) => r.name.toLowerCase().includes(q) || (r.description && r.description.toLowerCase().includes(q)) || r.slug.toLowerCase().includes(q)
    );
});

const isDeleteModalOpen = ref(false);
const activeRole = ref<TenantRole | null>(null);

const openDeleteModal = (role: TenantRole) => {
    activeRole.value = role;
    isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
    if (!activeRole.value) return;
    router.delete(`/admin/roles/${activeRole.value.id}`, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            activeRole.value = null;
        },
    });
};
</script>

<template>
    <OrganizationLayout
        title="Roles & Permissions"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Users & Access' },
            { label: 'Roles & Permissions' },
        ]"
    >
        <Head title="Organization Roles & Permissions" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Roles & Permissions
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Define organization access levels, roles, and granular permission sets.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        Total Roles: {{ roles.length }}
                    </span>

                    <Button href="/admin/roles/create" variant="primary" size="sm">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Role
                    </Button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="relative flex-1 max-w-md">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search roles by name, slug or description..."
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 pl-10 pr-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100 dark:focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Roles Table -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-left text-xs dark:divide-zinc-800">
                        <thead class="bg-zinc-50/75 dark:bg-zinc-950/60">
                            <tr>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Role Name</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Slug</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Type</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Permissions</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Members</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                                <th class="px-6 py-3.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="role in filteredRoles"
                                :key="role.id"
                                class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-zinc-900 dark:text-white">
                                                {{ role.name }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-[11px] text-zinc-400">
                                            {{ role.description || 'No description provided.' }}
                                        </p>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 font-mono text-zinc-500 dark:text-zinc-400">
                                    {{ role.slug }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        v-if="role.is_system"
                                        class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-0.5 text-[11px] font-semibold text-purple-700 dark:bg-purple-950/60 dark:text-purple-300"
                                    >
                                        System Role
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                    >
                                        Custom
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-[11px] font-medium text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                        {{ role.slug === 'admin' ? 'All Permissions' : `${role.permissions.length} permissions` }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                    <span class="font-semibold">{{ role.members_count }}</span> members
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <Badge :variant="role.is_active ? 'success' : 'danger'">
                                        {{ role.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>

                                <!-- Actions Dropdown -->
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <Dropdown align="right" width="w-44">
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
                                                        :href="`/admin/roles/${role.id}/edit`"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left"
                                                        @click="close"
                                                    >
                                                        <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        <span>Edit Role</span>
                                                    </Link>

                                                    <button
                                                        v-if="!role.is_system && role.members_count === 0"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openDeleteModal(role); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        <span>Delete Role</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredRoles.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">No roles found</p>
                                        <p class="mt-1 text-xs text-zinc-400">Try adjusting your search query.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Role Modal -->
        <Modal
            :show="isDeleteModalOpen"
            title="Delete Role"
            max-width="md"
            @close="isDeleteModalOpen = false"
        >
            <div class="space-y-4">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    Are you sure you want to delete the role <strong v-if="activeRole">{{ activeRole.name }}</strong>?
                    This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" @click="isDeleteModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="danger" size="sm" @click="confirmDelete">
                        Delete Role
                    </Button>
                </div>
            </div>
        </Modal>
    </OrganizationLayout>
</template>
