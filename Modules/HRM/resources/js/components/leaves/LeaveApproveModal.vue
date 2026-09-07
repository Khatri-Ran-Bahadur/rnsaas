<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import type { LeaveItem } from '../../types/leaves';

defineProps<{
    show: boolean;
    leave: LeaveItem | null;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        Approve Leave Request
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        Confirm approval for {{ leave?.staff?.name || 'this employee' }}.
                    </p>
                </div>
            </div>

            <div v-if="leave" class="mt-4 rounded-lg bg-zinc-50 p-3.5 text-xs dark:bg-zinc-800/40 border border-zinc-200/80 dark:border-zinc-800 space-y-1.5">
                <p class="text-slate-700 dark:text-zinc-300">
                    <span class="font-medium text-slate-500">Leave Type:</span> {{ leave.leave_type?.label }}
                </p>
                <p class="text-slate-700 dark:text-zinc-300">
                    <span class="font-medium text-slate-500">Dates:</span> {{ leave.start_date }} → {{ leave.end_date }} ({{ leave.total_days }} days)
                </p>
                <p v-if="leave.reason" class="text-slate-700 dark:text-zinc-300">
                    <span class="font-medium text-slate-500">Reason:</span> {{ leave.reason }}
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-2.5">
                <button
                    type="button"
                    class="rounded-lg border border-zinc-300 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="processing"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500 disabled:opacity-50 transition-colors"
                    @click="emit('confirm')"
                >
                    {{ processing ? 'Approving...' : 'Confirm Approval' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
