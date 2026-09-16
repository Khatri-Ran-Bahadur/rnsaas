<script setup lang="ts">
import { ref, watch } from 'vue';
import type { CartLineItem } from './PosCart.vue';

const props = defineProps<{
    show: boolean;
    lineItem: CartLineItem | null;
    currencySymbol: string;
    mode: 'price' | 'notes' | 'all';
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saveLine', payload: { lineId: string; unitPrice: number; notes: string }): void;
}>();

const unitPrice = ref('');
const notes = ref('');

watch(() => props.show, (isOpen) => {
    if (isOpen && props.lineItem) {
        unitPrice.value = props.lineItem.unit_price.toFixed(2);
        notes.value = props.lineItem.notes || '';
    }
});

const handleSave = () => {
    if (!props.lineItem) return;
    const priceNum = parseFloat(unitPrice.value) || 0;
    emit('saveLine', {
        lineId: props.lineItem.id,
        unitPrice: priceNum > 0 ? priceNum : props.lineItem.unit_price,
        notes: notes.value.trim(),
    });
};
</script>

<template>
    <div
        v-if="show && lineItem"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-xs select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>{{ mode === 'price' ? 'Override Unit Price' : mode === 'notes' ? 'Edit Kitchen / Line Note' : 'Edit Cart Item' }}</span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">{{ lineItem.name }} ({{ lineItem.sku }})</p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="handleSave" class="p-6 space-y-4 text-xs">
                <!-- Unit Price Override Field -->
                <div v-if="mode === 'price' || mode === 'all'">
                    <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Unit Price ({{ currencySymbol }})
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400 font-mono font-bold">
                            {{ currencySymbol }}
                        </div>
                        <input
                            v-model="unitPrice"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full pl-10 pr-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-zinc-900 dark:text-white font-bold text-base focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- Cooking / Delivery Notes Field -->
                <div v-if="mode === 'notes' || mode === 'all'">
                    <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Kitchen / Preparation Note
                    </label>
                    <input
                        v-model="notes"
                        type="text"
                        placeholder="e.g. Less ice, extra spicy, no sauce..."
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <!-- Footer -->
                <div class="pt-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-end space-x-2">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-semibold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md shadow-emerald-900/20 cursor-pointer"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
