<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import { usePermissions } from '@/composables/usePermissions';
import ShiftStats from '../../components/shifts/ShiftStats.vue';
import ShiftFilters from '../../components/shifts/ShiftFilters.vue';
import ShiftTable from '../../components/shifts/ShiftTable.vue';
import type { ShiftIndexProps, ShiftItem } from '../../types/shifts';

const props = defineProps<ShiftIndexProps>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('shifts.manage'));

const search = ref(props.filters.search ?? '');
const selectedActive = ref(props.filters.is_active === null || props.filters.is_active === undefined ? '' : String(props.filters.is_active));
const perPage = ref(props.filters.per_page ?? 20);

const applyFilters = () => {
    router.get(
        '/admin/hrm/shifts',
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

const toggleStatus = (shift: ShiftItem) => {
    if (!canManage.value) return;
    router.patch(
        `/admin/hrm/shifts/${shift.public_id}/toggle-status`,
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <OrganizationLayout
        title="Organization Shifts"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Shifts' },
        ]"
    >
        <Head title="Shifts - HRM" />

        <div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Shift Schedules
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Define morning, evening, rotational, and overnight work shifts for your staff.
                    </p>
                </div>

                <div v-if="canManage">
                    <Link href="/admin/hrm/shifts/create">
                        <Button variant="primary">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Shift
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <ShiftStats :stats="stats" />

            <!-- Filters -->
            <ShiftFilters
                v-model:search="search"
                v-model:selected-active="selectedActive"
                v-model:per-page="perPage"
                :total-count="shifts.data.length"
                @filter="applyFilters"
            />

            <!-- Table -->
            <ShiftTable
                :shifts="shifts"
                :can-manage="canManage"
                @toggle-status="toggleStatus"
            />
        </div>
    </OrganizationLayout>
</template>
