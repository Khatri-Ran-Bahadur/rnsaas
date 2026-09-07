<script setup lang="ts">
import { ref, watch } from 'vue';
import Modal from '@/components/Modal.vue';
import type { LeaveItem } from '../../types/leaves';

const props = defineProps<{
    show: boolean;
    leave: LeaveItem | null;
    processing?: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm', reason: string): void;
}>();

const reason = ref('');

watch(
    () => props.show,
    (val) => {
        if (val) {
            reason.value = '';
        }
    },
);

const handleConfirm = () => {
    if (!reason.value.trim()) {
        alert('Please specify a rejection reason.');
        return;
    }
    emit('confirm', reason.value);
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        Reject Leave Request
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        Provide a reason for rejecting {{ leave?.staff?.name || 'this request' }}.
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                    Rejection Reason <span class="text-rose-500">*</span>
                </label>
                <textarea
                    v-model="reason"
                    rows="3"
                    required
                    placeholder="e.g. Critical project deadline, insufficient staffing."
                    class="w-full rounded-lg border border-zinc-300 p-2.5 text-xs text-slate-900 placeholder-zinc-400 focus:border-rose-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                />
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
                    :disabled="processing || !reason.trim()"
                    class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-rose-500 disabled:opacity-50 transition-colors"
                    @click="handleConfirm"
                >
                    {{ processing ? 'Rejecting...' : 'Reject Request' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
