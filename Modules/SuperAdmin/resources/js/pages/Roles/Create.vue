<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import RoleForm, { type PermissionGroup } from './Partials/RoleForm.vue';

defineProps<{
    permission_groups: PermissionGroup[];
}>();
</script>

<template>
    <SuperAdminLayout
        title="Create New Role"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Role Management', href: '/superadmin/roles' },
            { label: 'Create New Role' },
        ]"
    >
        <!-- ─── Page Header (Matching Reference Screenshot) ────────────────── -->
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-lg font-bold text-zinc-900 dark:text-white">
                Create New Role
            </h1>
            <Link
                href="/superadmin/roles"
                class="inline-flex items-center gap-1.5 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 transition-colors dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </Link>
        </div>

        <!-- ─── Form ────────────────────────────────────────────────────── -->
        <RoleForm
            :permission-groups="permission_groups"
            submit-url="/superadmin/roles"
            cancel-url="/superadmin/roles"
        />
    </SuperAdminLayout>
</template>
