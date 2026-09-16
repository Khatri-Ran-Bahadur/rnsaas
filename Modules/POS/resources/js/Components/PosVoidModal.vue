<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    title?: string;
    description?: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirmVoid', payload: { pin: string; reason: string }): void;
}>();

const pin = ref('');
const reason = ref('wrong_item');
const errorMsg = ref('');

const reasons = [
    { id: 'wrong_item', label: 'Cashier Input / Wrong Item Selected' },
    { id: 'customer_change', label: 'Customer Changed Mind / Left Queue' },
    { id: 'price_dispute', label: 'Price Dispute / Discount Override' },
    { id: 'payment_failed', label: 'Payment Method Declined / Failed' },
    { id: 'item_damaged', label: 'Product Damaged / Spoiled' },
    { id: 'other', label: 'Other Operational Exception' },
];

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        pin.value = '';
        reason.value = 'wrong_item';
        errorMsg.value = '';
    }
});

const handleNumpad = (char: string) => {
    if (char === 'C') {
        pin.value = '';
    } else if (char === 'BS') {
        pin.value = pin.value.slice(0, -1);
    } else if (pin.value.length < 6) {
        pin.value += char;
    }
};

const handleConfirm = () => {
    if (pin.value.length < 4) {
        errorMsg.value = 'Please enter a valid 4 to 6-digit Supervisor PIN';
        return;
    }
    emit('confirmVoid', {
        pin: pin.value,
        reason: reason.value,
    });
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-xs select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 bg-rose-50 dark:bg-rose-950/40 border-b border-rose-200 dark:border-rose-900/60 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-rose-800 dark:text-rose-300 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>{{ title || 'Manager Void Authorization' }}</span>
                    </h3>
                    <p class="text-xs text-rose-700 dark:text-neutral-400 mt-0.5">{{ description || 'Supervisor approval required to void transaction or items' }}</p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4">
                <!-- Void Reason Select -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Select Reason Code</label>
                    <select
                        v-model="reason"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-xs text-zinc-800 dark:text-neutral-200 focus:ring-2 focus:ring-rose-500"
                    >
                        <option v-for="r in reasons" :key="r.id" :value="r.id">
                            {{ r.label }}
                        </option>
                    </select>
                </div>

                <!-- PIN Input Dots Display -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5 text-center">
                        Enter Supervisor PIN Code
                    </label>
                    <div class="flex items-center justify-center space-x-3 py-3 bg-slate-50 dark:bg-zinc-950 rounded-xl border border-zinc-300 dark:border-zinc-800">
                        <div
                            v-for="i in 6"
                            :key="i"
                            class="w-3.5 h-3.5 rounded-full border border-zinc-400 dark:border-zinc-600 transition"
                            :class="pin.length >= i ? 'bg-rose-500 border-rose-400 shadow-xs' : 'bg-white dark:bg-zinc-900'"
                        ></div>
                    </div>
                    <div v-if="errorMsg" class="text-[11px] text-rose-600 dark:text-rose-400 text-center mt-1.5 font-medium">
                        {{ errorMsg }}
                    </div>
                </div>

                <!-- Numpad Grid -->
                <div class="grid grid-cols-3 gap-2 pt-1">
                    <button
                        v-for="n in ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'C', '0', 'BS']"
                        :key="n"
                        type="button"
                        @click="handleNumpad(n)"
                        class="py-2.5 rounded-xl bg-white dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 text-zinc-800 dark:text-neutral-200 text-base font-mono font-bold border border-zinc-200 dark:border-zinc-800 transition active:scale-95 flex items-center justify-center cursor-pointer shadow-xs"
                    >
                        <span v-if="n === 'BS'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/></svg>
                        </span>
                        <span v-else>{{ n }}</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-end space-x-2">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-neutral-300 text-xs font-semibold cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="pin.length < 4"
                    @click="handleConfirm"
                    class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-40 text-white text-xs font-bold shadow-md transition cursor-pointer"
                >
                    Authorize & Void
                </button>
            </div>
        </div>
    </div>
</template>
