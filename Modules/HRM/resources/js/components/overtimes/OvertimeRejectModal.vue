<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';
import type { OvertimeItem } from '../../types/overtimes';

defineProps<{
    show: boolean;
    item: OvertimeItem | null;
    reason: string;
    loading: boolean;
    error: string;
}>();

const emit = defineEmits<{
    (e: 'update:reason', val: string): void;
    (e: 'close'): void;
    (e: 'submit'): void;
}>();
</script>

<template>
    <Modal :show="show" @close="emit('close')">
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
                        Provide a reason for rejecting this overtime request for {{ item?.staff?.name }}.
                    </p>
                </div>
            </div>

            <div class="mt-4 space-y-2">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    Rejection Reason <span class="text-rose-500">*</span>
                </label>
                <textarea
                    :value="reason"
                    rows="3"
                    placeholder="e.g. Prior authorization was not obtained or exceeds department limit."
                    class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-xs text-zinc-800 focus:border-zinc-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                    @input="emit('update:reason', ($event.target as HTMLTextAreaElement).value)"
                ></textarea>
                <p v-if="error" class="text-xs text-rose-500">{{ error }}</p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Button variant="secondary" size="sm" :disabled="loading" @click="emit('close')">
                    Cancel
                </Button>
                <Button variant="danger" size="sm" :disabled="loading" @click="emit('submit')">
                    {{ loading ? 'Rejecting...' : 'Reject Request' }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
