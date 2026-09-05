<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import RoleForm, { type PermissionGroup } from './Partials/RoleForm.vue';

export interface RoleData {
    id: number;
    name: string;
    guard_name: string;
    is_system: boolean;
    permissions: string[];
}

const props = defineProps<{
    role: RoleData;
    permission_groups: PermissionGroup[];
}>();
</script>

<template>
    <SuperAdminLayout
        :title="`Edit Role: ${role.name}`"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Role Management', href: '/superadmin/roles' },
            { label: `Edit: ${role.name}` },
        ]"
    >
        <!-- ─── Page Header (Matching Reference Screenshot) ────────────────── -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold text-zinc-900 dark:text-white">
                    Edit Role: {{ role.name }}
                </h1>
                <span
                    v-if="role.is_system"
                    class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                >
                    System Role
                </span>
            </div>
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
            :is-edit="true"
            :role-id="role.id"
            :initial-name="role.name"
            :initial-permissions="role.permissions"
            :permission-groups="permission_groups"
            :submit-url="`/superadmin/roles/${role.id}`"
            submit-method="put"
            cancel-url="/superadmin/roles"
        />
    </SuperAdminLayout>
</template>
