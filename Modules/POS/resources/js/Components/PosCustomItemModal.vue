<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    currencySymbol: string;
    defaultTaxRate: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'addCustomItem', payload: {
        name: string;
        price: number;
        quantity: number;
        tax_rate: number;
        notes: string;
    }): void;
}>();

const name = ref('');
const price = ref('');
const quantity = ref(1);
const taxRate = ref(props.defaultTaxRate);
const notes = ref('');

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        name.value = '';
        price.value = '';
        quantity.value = 1;
        taxRate.value = props.defaultTaxRate;
        notes.value = '';
    }
});

const handleSave = () => {
    const numPrice = parseFloat(price.value) || 0;
    if (!name.value.trim() || numPrice <= 0) return;

    emit('addCustomItem', {
        name: name.value.trim(),
        price: numPrice,
        quantity: quantity.value || 1,
        tax_rate: Number(taxRate.value) || 0,
        notes: notes.value.trim(),
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
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Custom / Open Price Item</span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Quickly add manual service charges or non-catalog items</p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="handleSave" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Item / Service Description <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="name"
                        required
                        type="text"
                        placeholder="e.g. Alteration Service, Custom Consultation"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                            Unit Price ({{ currencySymbol }}) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="price"
                            required
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-zinc-800 dark:text-white font-bold text-sm focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Quantity</label>
                        <input
                            v-model.number="quantity"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Notes / Special Instructions</label>
                    <input
                        v-model="notes"
                        type="text"
                        placeholder="Optional remarks"
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
                        :disabled="!name.trim() || !price"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white font-bold shadow-md shadow-emerald-900/20 cursor-pointer"
                    >
                        Add to Cart
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
