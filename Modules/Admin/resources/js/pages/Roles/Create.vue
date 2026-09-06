<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import RoleForm, { type PermissionGroup } from './Partials/RoleForm.vue';

defineProps<{
    permission_groups: PermissionGroup[];
}>();
</script>

<template>
    <OrganizationLayout
        title="Create New Role"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Roles', href: '/admin/roles' },
            { label: 'Create New Role' },
        ]"
    >
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Create New Role
                </h1>
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Configure a new organization role and grant granular permissions.
                </p>
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

        <!-- Dynamic Role Form Component -->
        <RoleForm
            :permission-groups="permission_groups"
            submit-url="/admin/roles"
            cancel-url="/admin/roles"
        />
    </OrganizationLayout>
</template>
