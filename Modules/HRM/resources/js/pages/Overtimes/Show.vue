<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import Modal from '@/components/Modal.vue';
import Switch from '@/components/Switch.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { OvertimeItem } from '../../types/overtimes';

const props = defineProps<{
    overtime: { data: OvertimeItem } | OvertimeItem;
    can?: {
        manage?: boolean;
    };
}>();

const { can } = usePermissions();
const canManage = computed(() => props.can?.manage ?? can('overtime.manage'));

const o = computed<OvertimeItem>(() => ('data' in props.overtime ? props.overtime.data : props.overtime));

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return 'success';
        case 'pending':
            return 'warning';
        case 'rejected':
            return 'danger';
        default:
            return 'neutral';
    }
};

const getTypeBadgeVariant = (type: string) => {
    switch (type) {
        case 'holiday':
            return 'danger';
        case 'weekend':
            return 'info';
        case 'regular':
        default:
            return 'neutral';
    }
};

// Toggle status
const toggleStatus = () => {
    if (!canManage.value) return;
    router.patch(`/admin/hrm/overtimes/${o.value.public_id}/toggle-status`, {}, {
        preserveScroll: true,
    });
};

// Quick Approve
const isApproving = ref(false);
const approveOvertime = () => {
    if (!confirm('Are you sure you want to approve this overtime entry?')) return;
    isApproving.value = true;
    router.patch(`/admin/hrm/overtimes/${o.value.public_id}/approve`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isApproving.value = false;
        },
    });
};

// Reject Modal
const showRejectModal = ref(false);
const rejectReason = ref('');
const rejectingLoading = ref(false);
const rejectError = ref('');

const submitReject = () => {
    if (!rejectReason.value.trim()) {
        rejectError.value = 'Please provide a valid rejection reason.';
        return;
    }

    rejectingLoading.value = true;
    router.patch(
        `/admin/hrm/overtimes/${o.value.public_id}/reject`,
        { rejection_reason: rejectReason.value.trim() },
        {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
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
        title="Overtime Details"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Overtime', href: '/admin/hrm/overtimes' },
            { label: o.staff?.name ?? 'Overtime Record' },
        ]"
    >
        <Head :title="`Overtime - ${o.staff?.name ?? 'Record'}`" />

        <div class="px-4 py-6 sm:px-8 max-w-4xl mx-auto space-y-6">
            <!-- Header section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ o.staff?.name ?? 'Employee Overtime' }}
                        </h1>
                        <span class="rounded bg-zinc-100 px-2 py-0.5 font-mono text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ o.staff?.employee_code }}
                        </span>
                        <Badge :variant="getStatusBadgeVariant(o.status)">
                            {{ o.status_label }}
                        </Badge>
                        <Badge :variant="getTypeBadgeVariant(o.type)">
                            {{ o.type_label }}
                        </Badge>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Overtime record logged on {{ o.date }} ({{ o.start_time }} — {{ o.end_time }})
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link href="/admin/hrm/overtimes">
                        <Button variant="secondary" size="sm">Back to List</Button>
                    </Link>

                    <!-- Approve / Reject actions if pending -->
                    <template v-if="canManage && o.status === 'pending'">
                        <Button
                            variant="success"
                            size="sm"
                            :disabled="isApproving"
                            @click="approveOvertime"
                        >
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ isApproving ? 'Approving...' : 'Approve' }}
                        </Button>
                        <Button
                            variant="danger"
                            size="sm"
                            @click="showRejectModal = true"
                        >
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Reject
                        </Button>
                    </template>

                    <Link v-if="canManage" :href="`/admin/hrm/overtimes/${o.public_id}/edit`">
                        <Button variant="secondary" size="sm">
                            <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Total Duration</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                        {{ o.total_hours }} Hours
                    </p>
                    <p class="text-[11px] text-zinc-400 font-mono">{{ o.total_minutes }} mins</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Timings</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                        {{ o.start_time }} — {{ o.end_time }}
                    </p>
                    <p class="text-[11px] text-zinc-400">{{ o.date }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Rate Multiplier</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                        {{ o.rate_multiplier }}x
                    </p>
                    <p class="text-[11px] text-zinc-400">Base wage factor</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-xs text-zinc-400">Status</span>
                    <p class="mt-1 text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ o.status_label }}
                    </p>
                    <p class="text-[11px] text-zinc-400">{{ o.is_active ? 'Active Record' : 'Inactive' }}</p>
                </div>
            </div>

            <!-- Reason Description Card -->
            <div v-if="o.reason" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Reason / Scope of Overtime</h3>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line">{{ o.reason }}</p>
            </div>

            <!-- Rejection notice if rejected -->
            <div v-if="o.status === 'rejected' && o.rejection_reason" class="rounded-xl border border-rose-200 bg-rose-50/50 p-5 shadow-xs dark:border-rose-900/40 dark:bg-rose-950/20 space-y-1">
                <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300 font-bold text-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Rejection Feedback
                </div>
                <p class="text-xs text-rose-700 dark:text-rose-400">{{ o.rejection_reason }}</p>
            </div>

            <!-- Audit Trail & Meta Card -->
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Review & Audit Timeline</h3>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                        <span class="text-zinc-400">Created At:</span>
                        <p class="font-medium text-zinc-800 dark:text-zinc-200 mt-0.5">{{ o.created_at ? new Date(o.created_at).toLocaleString() : '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                        <span class="text-zinc-400">Last Modified:</span>
                        <p class="font-medium text-zinc-800 dark:text-zinc-200 mt-0.5">{{ o.updated_at ? new Date(o.updated_at).toLocaleString() : '—' }}</p>
                    </div>
                    <div v-if="o.approved_at" class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/30">
                        <span class="text-emerald-700 dark:text-emerald-400">Approved:</span>
                        <p class="font-medium text-emerald-900 dark:text-emerald-200 mt-0.5">{{ new Date(o.approved_at).toLocaleString() }}</p>
                    </div>
                    <div v-if="o.rejected_at" class="rounded-lg bg-rose-50 p-3 dark:bg-rose-950/30">
                        <span class="text-rose-700 dark:text-rose-400">Rejected:</span>
                        <p class="font-medium text-rose-900 dark:text-rose-200 mt-0.5">{{ new Date(o.rejected_at).toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <!-- Active Switch Card -->
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Payroll Inclusion Status</h3>
                    <p class="text-[11px] text-zinc-500">Toggle active status to include or exclude this overtime entry from monthly payroll.</p>
                </div>
                <Switch :model-value="o.is_active" :disabled="!canManage" @update:model-value="toggleStatus" />
            </div>
        </div>

        <!-- Reject Modal -->
        <Modal :show="showRejectModal" @close="showRejectModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-rose-100 p-2 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Reject Overtime Request</h3>
                        <p class="text-xs text-zinc-500">
                            Provide a reason for rejecting this overtime request for {{ o.staff?.name }}.
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Rejection Reason <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        placeholder="e.g. Work was not pre-authorized or exceeded required shift hours."
                        class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-xs text-zinc-800 focus:border-zinc-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                    ></textarea>
                    <p v-if="rejectError" class="text-xs text-rose-500">{{ rejectError }}</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <Button variant="secondary" size="sm" :disabled="rejectingLoading" @click="showRejectModal = false">
                        Cancel
                    </Button>
                    <Button variant="danger" size="sm" :disabled="rejectingLoading" @click="submitReject">
                        {{ rejectingLoading ? 'Rejecting...' : 'Reject Request' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </OrganizationLayout>
</template>
