<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import HRMDetailDrawer from '../../components/common/HRMDetailDrawer.vue';
import HRMStatusBadge from '../../components/common/HRMStatusBadge.vue';
import LeaveStats from '../../components/leaves/LeaveStats.vue';
import LeaveFilters from '../../components/leaves/LeaveFilters.vue';
import LeaveTable from '../../components/leaves/LeaveTable.vue';
import LeaveApproveModal from '../../components/leaves/LeaveApproveModal.vue';
import LeaveRejectModal from '../../components/leaves/LeaveRejectModal.vue';
import LeaveCalendarView from '../../components/leaves/LeaveCalendarView.vue';
import LeaveExceptionsView from '../../components/leaves/LeaveExceptionsView.vue';
import type { LeaveIndexProps, LeaveItem } from '../../types/leaves';

const props = defineProps<LeaveIndexProps>();

type TabKey = 'requests' | 'calendar' | 'exceptions';
const activeTab = ref<TabKey>('requests');

const tabs: Array<{ key: TabKey; label: string }> = [
    { key: 'requests', label: 'Leave Requests' },
    { key: 'calendar', label: 'Leave Calendar' },
    { key: 'exceptions', label: 'Exceptions & Warnings' },
];

// Modal States
const approvingLeave = ref<LeaveItem | null>(null);
const showApproveModal = ref(false);
const isApproveProcessing = ref(false);

const rejectingLeave = ref<LeaveItem | null>(null);
const showRejectModal = ref(false);
const isRejectProcessing = ref(false);

// Inspection drawer state
const inspectedLeave = ref<LeaveItem | null>(null);
const showDrawer = ref(false);

const openInspect = (item: LeaveItem) => {
    inspectedLeave.value = item;
    showDrawer.value = true;
};

// Filter handling
const filters = ref({
    search: props.filters?.search || '',
    tenant_staff_id: props.filters?.tenant_staff_id || null,
    leave_type: props.filters?.leave_type || null,
    status: props.filters?.status || null,
    from_date: props.filters?.from_date || null,
    to_date: props.filters?.to_date || null,
    per_page: props.filters?.per_page || 20,
});

const applyFilters = () => {
    router.get(
        '/admin/hrm/leaves',
        {
            search: filters.value.search || undefined,
            tenant_staff_id: filters.value.tenant_staff_id || undefined,
            leave_type: filters.value.leave_type || undefined,
            status: filters.value.status || undefined,
            from_date: filters.value.from_date || undefined,
            to_date: filters.value.to_date || undefined,
            per_page: filters.value.per_page,
        },
        { preserveState: true, replace: true },
    );
};

const handleResetFilters = () => {
    filters.value.search = '';
    filters.value.tenant_staff_id = null;
    filters.value.leave_type = null;
    filters.value.status = null;
    filters.value.from_date = null;
    filters.value.to_date = null;
    applyFilters();
};

const triggerApprove = (item: LeaveItem) => {
    approvingLeave.value = item;
    showApproveModal.value = true;
};

const confirmApprove = () => {
    if (!approvingLeave.value) return;
    isApproveProcessing.value = true;
    router.patch(
        `/admin/hrm/leaves/${approvingLeave.value.public_id}/approve`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isApproveProcessing.value = false;
                showApproveModal.value = false;
                approvingLeave.value = null;
            },
        },
    );
};

const triggerReject = (item: LeaveItem) => {
    rejectingLeave.value = item;
    showRejectModal.value = true;
};

const confirmReject = (reason: string) => {
    if (!rejectingLeave.value) return;
    isRejectProcessing.value = true;
    router.patch(
        `/admin/hrm/leaves/${rejectingLeave.value.public_id}/reject`,
        { rejection_reason: reason },
        {
            preserveScroll: true,
            onFinish: () => {
                isRejectProcessing.value = false;
                showRejectModal.value = false;
                rejectingLeave.value = null;
            },
        },
    );
};

const handleToggleStatus = (item: LeaveItem) => {
    router.patch(
        `/admin/hrm/leaves/${item.public_id}/toggle-status`,
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <OrganizationLayout
        title="Leave Management"
        :breadcrumbs="[
            { label: 'HRM' },
            { label: 'Leave' },
        ]"
    >
        <Head title="Leave Management" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <HRMPageHeader
                title="Leave Requests"
                subtitle="Review, approve, and track employee leave requests and balances."
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Leave' },
                ]"
            >
                <template #actions>
                    <Link
                        href="/admin/hrm/leaves/create"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Leave Request
                    </Link>
                </template>
            </HRMPageHeader>

            <!-- Navigation Tabs -->
            <div class="mb-6 flex border-b border-zinc-200 dark:border-zinc-800 gap-6 overflow-x-auto">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    type="button"
                    :class="[
                        'pb-3 text-xs font-semibold whitespace-nowrap transition-colors border-b-2',
                        activeTab === t.key
                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                            : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = t.key"
                >
                    {{ t.label }}
                </button>
            </div>

            <!-- TAB 1: REQUESTS LIST -->
            <div v-if="activeTab === 'requests'">
                <LeaveStats :stats="stats" />

                <LeaveFilters
                    v-model:search="filters.search"
                    v-model:staff-id="filters.tenant_staff_id"
                    v-model:leave-type="filters.leave_type"
                    v-model:status="filters.status"
                    v-model:from-date="filters.from_date"
                    v-model:to-date="filters.to_date"
                    :staff-members="staffMembers"
                    @update:search="applyFilters"
                    @update:staff-id="applyFilters"
                    @update:leave-type="applyFilters"
                    @update:status="applyFilters"
                    @update:from-date="applyFilters"
                    @update:to-date="applyFilters"
                    @reset="handleResetFilters"
                />

                <LeaveTable
                    :leaves="leaves"
                    :per-page="filters.per_page"
                    @update:per-page="filters.per_page = $event; applyFilters()"
                    @approve="triggerApprove"
                    @reject="triggerReject"
                    @toggle-status="handleToggleStatus"
                    @inspect="openInspect"
                />
            </div>

            <!-- TAB 2: CALENDAR -->
            <div v-else-if="activeTab === 'calendar'">
                <LeaveCalendarView
                    :leaves="leaves.data"
                    @inspect="openInspect"
                />
            </div>

            <!-- TAB 3: EXCEPTIONS -->
            <div v-else-if="activeTab === 'exceptions'">
                <LeaveExceptionsView />
            </div>
        </div>

        <!-- Approve Modal -->
        <LeaveApproveModal
            :show="showApproveModal"
            :leave="approvingLeave"
            :processing="isApproveProcessing"
            @close="showApproveModal = false"
            @confirm="confirmApprove"
        />

        <!-- Reject Modal -->
        <LeaveRejectModal
            :show="showRejectModal"
            :leave="rejectingLeave"
            :processing="isRejectProcessing"
            @close="showRejectModal = false"
            @confirm="confirmReject"
        />

        <!-- Slide-out Detail Drawer -->
        <HRMDetailDrawer
            :show="showDrawer"
            :title="inspectedLeave ? `${inspectedLeave.staff?.name || 'Staff'} - Leave Detail` : 'Leave Detail'"
            :subtitle="inspectedLeave ? `${inspectedLeave.start_date} to ${inspectedLeave.end_date}` : ''"
            @close="showDrawer = false"
        >
            <div v-if="inspectedLeave" class="space-y-5 text-xs">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">
                            {{ (inspectedLeave.staff?.name || 'S').charAt(0) }}
                        </div>
                        <div>
                            <p class="font-bold text-sm text-slate-900 dark:text-white">
                                {{ inspectedLeave.staff?.name }}
                            </p>
                            <p class="text-slate-500 dark:text-zinc-400 font-mono">
                                #{{ inspectedLeave.staff?.employee_code }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Leave Type</span>
                        <span class="font-semibold text-slate-900 dark:text-white">{{ inspectedLeave.leave_type?.label }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Start Date</span>
                        <span class="font-mono text-slate-900 dark:text-white">{{ inspectedLeave.start_date }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">End Date</span>
                        <span class="font-mono text-slate-900 dark:text-white">{{ inspectedLeave.end_date }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Total Duration</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ inspectedLeave.total_days }} days</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Status</span>
                        <HRMStatusBadge :status="inspectedLeave.status?.value" :label="inspectedLeave.status?.label" />
                    </div>
                    <div v-if="inspectedLeave.rejection_reason" class="py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-rose-600 font-semibold block mb-1">Rejection Reason:</span>
                        <p class="text-slate-700 dark:text-zinc-300">{{ inspectedLeave.rejection_reason }}</p>
                    </div>
                </div>

                <div v-if="inspectedLeave.reason" class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                    <span class="font-semibold text-slate-700 dark:text-zinc-200 block mb-1">Employee Reason:</span>
                    <p class="text-slate-600 dark:text-zinc-400">{{ inspectedLeave.reason }}</p>
                </div>
            </div>

            <template #footer>
                <div v-if="inspectedLeave && inspectedLeave.status?.value === 'pending'" class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500"
                        @click="triggerApprove(inspectedLeave); showDrawer = false"
                    >
                        Approve
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-rose-500"
                        @click="triggerReject(inspectedLeave); showDrawer = false"
                    >
                        Reject
                    </button>
                </div>
            </template>
        </HRMDetailDrawer>
    </OrganizationLayout>
</template>
