<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import Switch from '@/components/Switch.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { HolidayItem } from '../../types/holidays';

const props = defineProps<{
    holiday: { data: HolidayItem } | HolidayItem;
    can?: {
        manage?: boolean;
    };
}>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('holidays.manage'));

const h = computed<HolidayItem>(() => ('data' in props.holiday ? props.holiday.data : props.holiday));

const toggleStatus = () => {
    if (!canManage.value) return;
    router.patch(`/admin/hrm/holidays/${h.value.public_id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Holiday Details"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Holidays', href: '/admin/hrm/holidays' },
            { label: h.name },
        ]"
    >
        <Head :title="`${h.name} - HRM`" />

        <div class="w-full space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ h.name }}
                        </h1>
                        <Badge :variant="h.is_active ? 'success' : 'neutral'">
                            {{ h.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                        <Badge v-if="h.is_recurring" variant="info">
                            Recurring Annual
                        </Badge>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        {{ h.description || 'Public or company recognized holiday calendar entry.' }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/hrm/holidays">
                        <Button variant="secondary" size="sm">Back to List</Button>
                    </Link>
                    <Link v-if="canManage" :href="`/admin/hrm/holidays/${h.public_id}/edit`">
                        <Button variant="primary" size="sm">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Holiday
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Date / Duration</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                        {{ h.start_date }}
                        <span v-if="h.end_date && h.end_date !== h.start_date">
                            → {{ h.end_date }}
                        </span>
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Type</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ h.type_label || h.type }}
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Span</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ h.is_multi_day ? 'Multi-Day Event' : 'Single Day' }}
                    </p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Recurs Annually</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ h.is_recurring ? 'Yes (Repeats yearly)' : 'No (One-time)' }}
                    </p>
                </div>
            </div>

            <!-- Description Card -->
            <div v-if="h.description" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Holiday Notes & Instructions</h3>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line">{{ h.description }}</p>
            </div>

            <!-- Toggle Status Card -->
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Active Holiday Status</h3>
                    <p class="text-[11px] text-zinc-500">Enable or disable this holiday from company attendance calculations.</p>
                </div>
                <Switch :model-value="h.is_active" :disabled="!canManage" @update:model-value="toggleStatus" />
            </div>
        </div>
    </OrganizationLayout>
</template>
