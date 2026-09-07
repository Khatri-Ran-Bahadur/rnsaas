<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import { usePermissions } from '@/composables/usePermissions';
import HolidayStats from '../../components/holidays/HolidayStats.vue';
import HolidayFilters from '../../components/holidays/HolidayFilters.vue';
import HolidayTable from '../../components/holidays/HolidayTable.vue';
import type { HolidayIndexProps, HolidayItem } from '../../types/holidays';

const props = defineProps<HolidayIndexProps>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('holidays.manage'));

const search = ref(props.filters.search ?? '');
const selectedType = ref(props.filters.type ?? '');
const selectedActive = ref(props.filters.active === null || props.filters.active === undefined ? '' : String(props.filters.active));
const perPage = ref(props.filters.per_page ?? 20);

const applyFilters = () => {
    router.get(
        '/admin/hrm/holidays',
        {
            search: search.value.trim() || undefined,
            type: selectedType.value || undefined,
            active: selectedActive.value !== '' ? selectedActive.value : undefined,
            per_page: perPage.value !== 20 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const toggleStatus = (holiday: HolidayItem) => {
    if (!canManage.value) return;
    router.patch(`/admin/hrm/holidays/${holiday.public_id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Organization Holidays"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Holidays' },
        ]"
    >
        <Head title="Holidays - HRM" />

        <div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Organization Holidays
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Manage public, company-wide, and regional holidays affecting attendance and payroll.
                    </p>
                </div>

                <div v-if="canManage">
                    <Link href="/admin/hrm/holidays/create">
                        <Button variant="primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Holiday
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <HolidayStats :stats="stats" />

            <!-- Filter Controls -->
            <HolidayFilters
                v-model:search="search"
                v-model:selected-type="selectedType"
                v-model:selected-active="selectedActive"
                v-model:per-page="perPage"
                :types="types"
                :total-count="holidays.data.length"
                @filter="applyFilters"
            />

            <!-- Table -->
            <HolidayTable
                :holidays="holidays"
                :can-manage="canManage"
                @toggle-status="toggleStatus"
            />
        </div>
    </OrganizationLayout>
</template>
