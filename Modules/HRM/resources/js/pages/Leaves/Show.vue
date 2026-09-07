<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import HRMStatusBadge from '../../components/common/HRMStatusBadge.vue';
import LeaveApproveModal from '../../components/leaves/LeaveApproveModal.vue';
import LeaveRejectModal from '../../components/leaves/LeaveRejectModal.vue';
import type { LeaveItem } from '../../types/leaves';

const props = defineProps<{
    leave: {
        data: LeaveItem;
    };
}>();

const record = computed(() => props.leave.data);

const showApproveModal = ref(false);
const isApproveProcessing = ref(false);

const showRejectModal = ref(false);
const isRejectProcessing = ref(false);

const confirmApprove = () => {
    isApproveProcessing.value = true;
    router.patch(
        `/admin/hrm/leaves/${record.value.public_id}/approve`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isApproveProcessing.value = false;
                showApproveModal.value = false;
            },
        },
    );
};

const confirmReject = (reason: string) => {
    isRejectProcessing.value = true;
    router.patch(
        `/admin/hrm/leaves/${record.value.public_id}/reject`,
        { rejection_reason: reason },
        {
            preserveScroll: true,
            onFinish: () => {
                isRejectProcessing.value = false;
                showRejectModal.value = false;
            },
        },
    );
};

const toggleStatus = () => {
    router.patch(`/admin/hrm/leaves/${record.value.public_id}/toggle-status`);
};
</script>

<template>
    <OrganizationLayout
        :title="`Leave Request - ${record.staff?.name || 'Staff'}`"
        :breadcrumbs="[
            { label: 'HRM' },
            { label: 'Leave', href: '/admin/hrm/leaves' },
            { label: record.public_id },
        ]"
    >
        <Head :title="`Leave Request - ${record.staff?.name || 'Staff'}`" />

        <div class="w-full space-y-6">
            <HRMPageHeader
                :title="`${record.staff?.name || 'Staff'} - Leave Request`"
                :subtitle="`${record.start_date} to ${record.end_date} (${record.total_days} days)`"
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Leave', href: '/admin/hrm/leaves' },
                    { label: record.public_id },
                ]"
            >
                <template #actions>
                    <Link
                        v-if="record.status?.value === 'pending'"
                        :href="`/admin/hrm/leaves/${record.public_id}/edit`"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                    >
                        Edit Request
                    </Link>

                    <template v-if="record.status?.value === 'pending'">
                        <button
                            type="button"
                            class="rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 transition-colors"
                            @click="showApproveModal = true"
                        >
                            Approve
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-rose-500 transition-colors"
                            @click="showRejectModal = true"
                        >
                            Reject
                        </button>
                    </template>

                    <button
                        type="button"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                        @click="toggleStatus"
                    >
                        {{ record.is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </template>
            </HRMPageHeader>

            <div class="space-y-6">
                <!-- Employee Info Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 text-lg">
                            {{ (record.staff?.name || 'S').charAt(0) }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ record.staff?.name }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 font-mono">
                                Employee Code: #{{ record.staff?.employee_code || '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <HRMStatusBadge :status="record.status?.value" :label="record.status?.label" size="md" />
                    </div>
                </div>

                <!-- Leave Summary Metrics -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Leave Type</span>
                        <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">
                            {{ record.leave_type?.label }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Start Date</span>
                        <p class="mt-1 text-sm font-mono font-bold text-slate-900 dark:text-white">
                            {{ record.start_date }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">End Date</span>
                        <p class="mt-1 text-sm font-mono font-bold text-slate-900 dark:text-white">
                            {{ record.end_date }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Total Duration</span>
                        <p class="mt-1 text-lg font-bold text-indigo-600 dark:text-indigo-400">
                            {{ record.total_days }} {{ record.total_days === 1 ? 'day' : 'days' }}
                        </p>
                    </div>
                </div>

                <!-- Reason Card -->
                <div v-if="record.reason" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-2">
                        Employee Reason
                    </h4>
                    <p class="text-xs text-slate-700 dark:text-zinc-300 whitespace-pre-line">
                        {{ record.reason }}
                    </p>
                </div>

                <!-- Approval Audit Timeline -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-4">
                        Approval Audit Trail
                    </h4>

                    <div class="space-y-4 text-xs">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 font-bold text-[10px]">
                                1
                            </span>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">Leave Submitted</p>
                                <p class="text-slate-500 text-[11px]">{{ record.created_at || 'Just now' }}</p>
                            </div>
                        </div>

                        <div v-if="record.status?.value === 'approved'" class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white font-bold text-[10px]">
                                ✓
                            </span>
                            <div>
                                <p class="font-semibold text-emerald-700 dark:text-emerald-300">Approved by Manager</p>
                                <p class="text-slate-500 text-[11px]">{{ record.approved_at || 'Approved' }}</p>
                            </div>
                        </div>

                        <div v-else-if="record.status?.value === 'rejected'" class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white font-bold text-[10px]">
                                ✕
                            </span>
                            <div>
                                <p class="font-semibold text-rose-700 dark:text-rose-300">Rejected</p>
                                <p class="text-slate-500 text-[11px]">{{ record.rejected_at || 'Rejected' }}</p>
                                <p v-if="record.rejection_reason" class="mt-1 rounded bg-rose-50 p-2 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300">
                                    {{ record.rejection_reason }}
                                </p>
                            </div>
                        </div>

                        <div v-else class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 font-bold text-[10px]">
                                2
                            </span>
                            <div>
                                <p class="font-semibold text-amber-700 dark:text-amber-400">Awaiting Manager Review</p>
                                <p class="text-slate-500 text-[11px]">Request is in pending queue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <LeaveApproveModal
            :show="showApproveModal"
            :leave="record"
            :processing="isApproveProcessing"
            @close="showApproveModal = false"
            @confirm="confirmApprove"
        />

        <LeaveRejectModal
            :show="showRejectModal"
            :leave="record"
            :processing="isRejectProcessing"
            @close="showRejectModal = false"
            @confirm="confirmReject"
        />
    </OrganizationLayout>
</template>
