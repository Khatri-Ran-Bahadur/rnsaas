<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import { usePermissions } from '@/composables/usePermissions';
import OvertimeStats from '../../components/overtimes/OvertimeStats.vue';
import OvertimeFilters from '../../components/overtimes/OvertimeFilters.vue';
import OvertimeTable from '../../components/overtimes/OvertimeTable.vue';
import OvertimeRejectModal from '../../components/overtimes/OvertimeRejectModal.vue';
import type { OvertimeIndexProps, OvertimeItem } from '../../types/overtimes';

const props = defineProps<OvertimeIndexProps>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('overtime.manage'));

// Filters state
const search = ref(props.filters.search ?? '');
const staffId = ref(props.filters.tenant_staff_id ? Number(props.filters.tenant_staff_id) : '');
const typeFilter = ref(props.filters.type ?? '');
const statusFilter = ref(props.filters.status ?? '');
const fromDate = ref(props.filters.from_date ?? '');
const toDate = ref(props.filters.to_date ?? '');
const perPage = ref(props.filters.per_page ?? 20);

const applyFilters = () => {
    router.get(
        '/admin/hrm/overtimes',
        {
            search: search.value || undefined,
            tenant_staff_id: staffId.value !== '' ? staffId.value : undefined,
            type: typeFilter.value || undefined,
            status: statusFilter.value || undefined,
            from_date: fromDate.value || undefined,
            to_date: toDate.value || undefined,
            per_page: perPage.value !== 20 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const resetFilters = () => {
    search.value = '';
    staffId.value = '';
    typeFilter.value = '';
    statusFilter.value = '';
    fromDate.value = '';
    toDate.value = '';
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return !!(search.value || staffId.value !== '' || typeFilter.value || statusFilter.value || fromDate.value || toDate.value);
});

// Quick Approve
const approvingId = ref<string | null>(null);
const approveOvertime = (item: OvertimeItem) => {
    if (!confirm(`Are you sure you want to approve this overtime request for ${item.staff?.name ?? 'staff'}?`)) {
        return;
    }
    approvingId.value = item.public_id;
    router.patch(`/admin/hrm/overtimes/${item.public_id}/approve`, {}, {
        preserveScroll: true,
        onFinish: () => {
            approvingId.value = null;
        },
    });
};

// Rejection Modal
const showRejectModal = ref(false);
const rejectingItem = ref<OvertimeItem | null>(null);
const rejectReason = ref('');
const rejectingLoading = ref(false);
const rejectError = ref('');

const openRejectModal = (item: OvertimeItem) => {
    rejectingItem.value = item;
    rejectReason.value = '';
    rejectError.value = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!rejectReason.value.trim()) {
        rejectError.value = 'Please provide a valid rejection reason.';
        return;
    }
    if (!rejectingItem.value) return;

    rejectingLoading.value = true;
    router.patch(
        `/admin/hrm/overtimes/${rejectingItem.value.public_id}/reject`,
        { rejection_reason: rejectReason.value.trim() },
        {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
                rejectingItem.value = null;
            },
            onError: (errors) => {
                rejectError.value = errors.rejection_reason || 'Failed to reject overtime request.';
            },
            onFinish: () => {
                rejectingLoading.value = false;
            },
        }
    );
};
</script>

<template>
    <OrganizationLayout
        title="Overtime Management"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Overtime' },
        ]"
    >
        <Head title="Overtime Management - HRM" />

        <div class="w-full space-y-6">
            <!-- Header section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Overtime Records
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Track, review, and authorize extra work hours, weekend duties, and holiday shifts.
                    </p>
                </div>

                <div v-if="canManage" class="flex items-center gap-3">
                    <Link href="/admin/hrm/overtimes/create">
                        <Button variant="primary" size="md">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Log Overtime
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <OvertimeStats :stats="stats" />

            <!-- Filter Controls Toolbar -->
            <OvertimeFilters
                v-model:search="search"
                v-model:staff-id="staffId"
                v-model:type-filter="typeFilter"
                v-model:status-filter="statusFilter"
                v-model:from-date="fromDate"
                v-model:to-date="toDate"
                v-model:per-page="perPage"
                :staff_members="staff_members"
                :types="types"
                :statuses="statuses"
                :total-count="overtimes.data.length"
                :has-active-filters="hasActiveFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- Table of Records -->
            <OvertimeTable
                :overtimes="overtimes"
                :can-manage="canManage"
                :approving-id="approvingId"
                :has-active-filters="hasActiveFilters"
                @approve="approveOvertime"
                @reject="openRejectModal"
            />
        </div>

        <!-- Reject Reason Modal -->
        <OvertimeRejectModal
            :show="showRejectModal"
            :item="rejectingItem"
            v-model:reason="rejectReason"
            :loading="rejectingLoading"
            :error="rejectError"
            @close="showRejectModal = false"
            @submit="submitReject"
        />
    </OrganizationLayout>
</template>
