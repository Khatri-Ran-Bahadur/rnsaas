<script setup lang="ts">
import { ref } from 'vue';
import HRMComparisonCard from '../common/HRMComparisonCard.vue';
import HRMStatusBadge from '../common/HRMStatusBadge.vue';
import HRMEmptyState from '../common/HRMEmptyState.vue';
import Modal from '@/components/Modal.vue';
import type { AttendanceCorrectionItem } from '../../types/attendances';

// TODO BACKEND CONTRACT: Dedicated corrections controller endpoints.
// Currently supported via Attendance model updates.
const mockCorrections: AttendanceCorrectionItem[] = [
    {
        id: 'COR-101',
        employee_name: 'Aarav Sharma',
        employee_code: 'EMP-0012',
        department: 'Engineering',
        attendance_date: '2026-09-06',
        original_check_in: '09:42',
        requested_check_in: '09:00',
        original_check_out: '17:20',
        requested_check_out: '17:30',
        original_worked_hours: '7.6',
        requested_worked_hours: '8.5',
        reason: 'Biometric fingerprint scanner malfunctioned at reception.',
        requested_by: 'Aarav Sharma',
        status: 'pending',
        created_at: '2026-09-06 18:10',
    },
    {
        id: 'COR-102',
        employee_name: 'Pooja Thapa',
        employee_code: 'EMP-0034',
        department: 'Marketing',
        attendance_date: '2026-09-05',
        original_check_in: null,
        requested_check_in: '09:15',
        original_check_out: '17:00',
        requested_check_out: '17:00',
        original_worked_hours: '0.0',
        requested_worked_hours: '7.8',
        reason: 'Forgot mobile punch while attending offsite client conference.',
        requested_by: 'Pooja Thapa',
        status: 'pending',
        created_at: '2026-09-05 19:40',
    },
];

const selectedFilter = ref<'all' | 'pending' | 'approved' | 'rejected'>('pending');
const selectedCorrection = ref<AttendanceCorrectionItem | null>(null);
const showRejectModal = ref(false);
const rejectionReason = ref('');

const openRejectModal = (item: AttendanceCorrectionItem) => {
    selectedCorrection.value = item;
    rejectionReason.value = '';
    showRejectModal.value = true;
};

const handleApprove = (item: AttendanceCorrectionItem) => {
    alert(`Approved correction ${item.id} for ${item.employee_name}`);
};

const handleRejectSubmit = () => {
    if (!rejectionReason.value.trim()) {
        alert('Please provide a rejection reason.');
        return;
    }
    alert(`Rejected correction ${selectedCorrection.value?.id}: ${rejectionReason.value}`);
    showRejectModal.value = false;
};
</script>

<template>
    <div class="space-y-5">
        <!-- Filter Pills Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button
                    v-for="st in ['pending', 'approved', 'rejected', 'all'] as const"
                    :key="st"
                    type="button"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-xs font-semibold capitalize transition-colors',
                        selectedFilter === st
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'bg-white text-slate-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700',
                    ]"
                    @click="selectedFilter = st"
                >
                    {{ st }}
                </button>
            </div>
        </div>

        <!-- List of Correction Cards -->
        <div class="space-y-4">
            <div
                v-for="item in mockCorrections"
                :key="item.id"
                class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 transition-all hover:border-zinc-300 dark:hover:border-zinc-700"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-zinc-200/80 pb-4 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            {{ item.employee_name.charAt(0) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ item.employee_name }}
                                </h4>
                                <span class="text-xs text-slate-400 font-mono">#{{ item.employee_code }}</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">
                                Date: <strong class="text-slate-800 dark:text-zinc-200">{{ item.attendance_date }}</strong> • Requested by {{ item.requested_by }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <HRMStatusBadge :status="item.status" />

                        <div v-if="item.status === 'pending'" class="flex items-center gap-2 ml-2">
                            <button
                                type="button"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 transition-colors"
                                @click="handleApprove(item)"
                            >
                                Approve
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300 transition-colors"
                                @click="openRejectModal(item)"
                            >
                                Reject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Side by side comparison -->
                <div class="mt-4">
                    <HRMComparisonCard
                        :current="{
                            checkIn: item.original_check_in,
                            checkOut: item.original_check_out,
                            workedHours: item.original_worked_hours,
                        }"
                        :requested="{
                            checkIn: item.requested_check_in,
                            checkOut: item.requested_check_out,
                            workedHours: item.requested_worked_hours,
                        }"
                    />
                </div>

                <!-- Reason Footer -->
                <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-xs text-slate-600 dark:bg-zinc-950/40 dark:text-zinc-300 border border-zinc-200/60 dark:border-zinc-800/60">
                    <span class="font-semibold text-slate-700 dark:text-zinc-200">Reason:</span> {{ item.reason }}
                </div>
            </div>

            <HRMEmptyState
                v-if="mockCorrections.length === 0"
                title="No correction requests"
                description="There are currently no attendance correction requests matching this status."
            />
        </div>

        <!-- Reject Modal -->
        <Modal :show="showRejectModal" max-width="md" @close="showRejectModal = false">
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    Reject Correction Request
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                    Please provide a clear reason for rejecting this correction request.
                </p>

                <div class="mt-4">
                    <textarea
                        v-model="rejectionReason"
                        rows="3"
                        required
                        placeholder="e.g. CCTV shows arrival was 09:35, not 09:00."
                        class="w-full rounded-lg border border-zinc-300 p-2.5 text-xs text-slate-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    />
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-zinc-300 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        @click="showRejectModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-rose-500"
                        @click="handleRejectSubmit"
                    >
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
