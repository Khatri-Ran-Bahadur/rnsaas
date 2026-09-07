<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import { usePermissions } from '@/composables/usePermissions';
import WorkScheduleStats from '../../components/work-schedules/WorkScheduleStats.vue';
import WorkScheduleFilters from '../../components/work-schedules/WorkScheduleFilters.vue';
import WorkScheduleTable from '../../components/work-schedules/WorkScheduleTable.vue';
import type { WorkScheduleIndexProps, WorkScheduleItem } from '../../types/work-schedules';

const props = defineProps<WorkScheduleIndexProps>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('work_schedules.manage'));

const search = ref(props.filters.search ?? '');
const selectedActive = ref(props.filters.is_active === null || props.filters.is_active === undefined ? '' : String(props.filters.is_active));
const perPage = ref(props.filters.per_page ?? 20);

const applyFilters = () => {
    router.get(
        '/admin/hrm/work-schedules',
        {
            search: search.value.trim() || undefined,
            is_active: selectedActive.value !== '' ? selectedActive.value : undefined,
            per_page: perPage.value !== 20 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const toggleStatus = (schedule: WorkScheduleItem) => {
    if (!canManage.value) return;
    router.patch(
        `/admin/hrm/work-schedules/${schedule.public_id}/toggle-status`,
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <OrganizationLayout
        title="Work Schedules"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Work Schedules' },
        ]"
    >
        <Head title="Work Schedules - HRM" />

        <div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Work Schedules
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Configure regular weekly office hours, working days, grace intervals, and break times.
                    </p>
                </div>

                <div v-if="canManage">
                    <Link href="/admin/hrm/work-schedules/create">
                        <Button variant="primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Work Schedule
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Metric Cards -->
            <WorkScheduleStats :stats="stats" />

            <!-- Filters -->
            <WorkScheduleFilters
                v-model:search="search"
                v-model:selected-active="selectedActive"
                v-model:per-page="perPage"
                :total-count="workSchedules.data.length"
                @filter="applyFilters"
            />

            <!-- Schedules Table -->
            <WorkScheduleTable
                :work-schedules="workSchedules"
                :can-manage="canManage"
                @toggle-status="toggleStatus"
            />
        </div>
    </OrganizationLayout>
</template>
