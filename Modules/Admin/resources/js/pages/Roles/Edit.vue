<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import RoleForm, { type PermissionGroup } from './Partials/RoleForm.vue';

export interface RoleData {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_system: boolean;
    is_active: boolean;
    permissions: string[];
}

defineProps<{
    role: RoleData;
    permission_groups: PermissionGroup[];
}>();
</script>

<template>
    <OrganizationLayout
        :title="`Edit Role: ${role.name}`"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Roles', href: '/admin/roles' },
            { label: `Edit: ${role.name}` },
        ]"
    >
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Edit Role: {{ role.name }}
                </h1>
                <span
                    v-if="role.is_system"
                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                >
                    System Role
                </span>
            </div>
            <Link
                href="/admin/roles"
                class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 transition-colors dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </Link>
        </div>

        <!-- Role Form Component -->
        <RoleForm
            :is-edit="true"
            :is-system="role.is_system"
            :role-id="role.id"
            :initial-name="role.name"
            :initial-description="role.description ?? ''"
            :initial-is-active="role.is_active"
            :initial-permissions="role.permissions"
            :permission-groups="permission_groups"
            :submit-url="`/admin/roles/${role.id}`"
            submit-method="put"
            cancel-url="/admin/roles"
        />
    </OrganizationLayout>
</template>
