<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import Switch from '@/components/Switch.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { ShiftItem } from '../../types/shifts';

const props = defineProps<{
    shift: { data: ShiftItem };
    can?: {
        manage?: boolean;
    };
}>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('shifts.manage'));

const s = computed(() => props.shift.data);

const toggleStatus = () => {
    if (!canManage.value) return;
    router.patch(`/admin/hrm/shifts/${s.value.public_id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Shift Details"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Shifts', href: '/admin/hrm/shifts' },
            { label: s.name },
        ]"
    >
        <Head :title="`${s.name} - HRM`" />

        <div class="w-full space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ s.name }}
                        </h1>
                        <span class="rounded bg-zinc-100 px-2 py-0.5 font-mono text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ s.code }}
                        </span>
                        <Badge :variant="s.is_active ? 'success' : 'neutral'">
                            {{ s.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        {{ s.description || 'Standard work shift for organization staff members.' }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/hrm/shifts">
                        <Button variant="secondary" size="sm">Back to List</Button>
                    </Link>
                    <Link v-if="canManage" :href="`/admin/hrm/shifts/${s.public_id}/edit`">
                        <Button variant="primary" size="sm">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Shift
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Shift Timings</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                        {{ s.start_time }} — {{ s.end_time }}
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Break Duration</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ s.break_minutes }} Minutes
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Late Grace</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ s.late_grace_minutes }} Minutes
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Shift Type</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ s.is_overnight ? '🌙 Overnight Shift' : '☀️ Day Shift' }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Active Shift Allocation</h3>
                    <p class="text-[11px] text-zinc-500">Toggle whether staff can be rostered onto this shift.</p>
                </div>
                <Switch :model-value="s.is_active" :disabled="!canManage" @update:model-value="toggleStatus" />
            </div>
        </div>
    </OrganizationLayout>
</template>
