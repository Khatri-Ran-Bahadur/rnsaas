<script setup lang="ts">
import { ref, watch } from 'vue';
import type { Customer } from './PosCart.vue';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'customerCreated', customer: Customer): void;
}>();

const name = ref('');
const phone = ref('');
const email = ref('');
const customerGroup = ref('Retail Walk-in');
const priceTier = ref('retail');
const discountRate = ref(0);
const creditLimit = ref(0);
const taxId = ref('');
const notes = ref('');

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        name.value = '';
        phone.value = '';
        email.value = '';
        customerGroup.value = 'Retail Regular';
        priceTier.value = 'retail';
        discountRate.value = 0;
        creditLimit.value = 0;
        taxId.value = '';
        notes.value = '';
    }
});

const handleSave = () => {
    if (!name.value.trim()) return;

    const newCustomer: Customer = {
        id: Date.now(),
        name: name.value.trim(),
        phone: phone.value.trim() || 'N/A',
        customer_group: customerGroup.value,
        price_tier: priceTier.value,
        discount_rate: Number(discountRate.value) || 0,
        outstanding_balance: 0,
        credit_limit: Number(creditLimit.value) || 0,
    };

    emit('customerCreated', newCustomer);
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-xs select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <span>Quick Add Customer</span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Register a new client or VIP and immediately assign to cart</p>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                            Customer / Company Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="name"
                            required
                            type="text"
                            placeholder="e.g. John Doe / Apex Engineering"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-zinc-950"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Phone Number</label>
                        <input
                            v-model="phone"
                            type="tel"
                            placeholder="+60 12-345 6789"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Email</label>
                        <input
                            v-model="email"
                            type="email"
                            placeholder="client@example.com"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Customer Group</label>
                        <select
                            v-model="customerGroup"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="Retail Regular">Retail Regular</option>
                            <option value="VIP Member">VIP Member</option>
                            <option value="Corporate / Account">Corporate / Account</option>
                            <option value="Wholesale Dealer">Wholesale Dealer</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Special Discount Rate (%)</label>
                        <input
                            v-model.number="discountRate"
                            type="number"
                            min="0"
                            max="100"
                            placeholder="0"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Credit Limit</label>
                        <input
                            v-model.number="creditLimit"
                            type="number"
                            min="0"
                            placeholder="0.00"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Tax ID / TIN / SST</label>
                        <input
                            v-model="taxId"
                            type="text"
                            placeholder="Optional tax identification"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-end space-x-2">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-semibold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="!name.trim()"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white font-bold shadow-md shadow-emerald-900/20 cursor-pointer"
                    >
                        Save & Select
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
