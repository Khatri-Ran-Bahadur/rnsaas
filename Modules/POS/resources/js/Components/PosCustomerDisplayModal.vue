<script setup lang="ts">
import type { CartLineItem, Customer } from './PosCart.vue';

const props = defineProps<{
    show: boolean;
    companyName: string;
    branchName: string;
    customer: Customer;
    cartItems: CartLineItem[];
    subtotal: number;
    discountTotal: number;
    taxTotal: number;
    grandTotal: number;
    currencySymbol: string;
    lastPaymentChange?: number | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/70 dark:bg-black/90 backdrop-blur-md select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl w-full max-w-4xl overflow-hidden shadow-2xl flex flex-col h-[85vh]">
            <!-- Top Customer Display Header -->
            <div class="px-8 py-5 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-extrabold text-xl shadow-md">
                        S
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-zinc-900 dark:text-white tracking-tight">{{ companyName }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ branchName }} • Customer Facing Display</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Serving Guest</div>
                        <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ customer.name }}</div>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="p-2 rounded-xl text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Body: Items List (Left 60%) and Live Total (Right 40%) -->
            <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-12 divide-y md:divide-y-0 md:divide-x divide-zinc-200 dark:divide-zinc-800">
                <!-- Left: Scanned Items Feed -->
                <div class="md:col-span-7 flex flex-col h-full bg-slate-50/50 dark:bg-zinc-950/50">
                    <div class="p-4 bg-white dark:bg-zinc-900/60 border-b border-zinc-200 dark:border-zinc-800 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Order Items ({{ cartItems.reduce((acc, i) => acc + i.quantity, 0) }})
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 divide-y divide-zinc-100 dark:divide-zinc-800/80">
                        <div
                            v-if="cartItems.length === 0"
                            class="h-full flex flex-col items-center justify-center text-center p-8 text-zinc-400"
                        >
                            <div class="text-base font-semibold text-zinc-700 dark:text-zinc-300">Welcome!</div>
                            <div class="text-xs text-zinc-500 mt-1">Your cashier is ready to assist you.</div>
                        </div>

                        <div
                            v-for="item in cartItems"
                            :key="item.id"
                            class="py-3 flex items-center justify-between"
                        >
                            <div>
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ item.quantity }}x {{ item.name }}</div>
                                <div v-if="item.variant_name" class="text-xs text-blue-600 dark:text-blue-400 font-mono">{{ item.variant_name }}</div>
                                <div v-if="item.modifiers && item.modifiers.length > 0" class="text-xs text-zinc-500 dark:text-zinc-400">
                                    + {{ item.modifiers.map(m => m.name).join(', ') }}
                                </div>
                            </div>
                            <div class="text-right font-mono text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                {{ currencySymbol }} {{ (((item.unit_price + item.modifiers.reduce((s, m) => s + m.price, 0)) * item.quantity) - item.discount_amount).toFixed(2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Large Customer Total -->
                <div class="md:col-span-5 p-8 bg-white dark:bg-zinc-900 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Billing Breakdown
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Subtotal</span>
                                <span class="font-mono text-zinc-800 dark:text-zinc-200 font-medium">{{ currencySymbol }} {{ subtotal.toFixed(2) }}</span>
                            </div>
                            <div v-if="discountTotal > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400 font-semibold">
                                <span>Discount</span>
                                <span class="font-mono">- {{ currencySymbol }} {{ discountTotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Tax (SST 6%)</span>
                                <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ currencySymbol }} {{ taxTotal.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-zinc-950 border border-emerald-300 dark:border-emerald-500/50 shadow-xs">
                            <div class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Payable</div>
                            <div class="text-4xl font-extrabold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                                {{ currencySymbol }} {{ grandTotal.toFixed(2) }}
                            </div>
                        </div>

                        <div
                            v-if="lastPaymentChange !== undefined && lastPaymentChange !== null"
                            class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/40 text-center"
                        >
                            <div class="text-xs font-bold uppercase text-emerald-800 dark:text-emerald-300">Change Returned</div>
                            <div class="text-2xl font-extrabold font-mono text-emerald-700 dark:text-emerald-300">
                                {{ currencySymbol }} {{ lastPaymentChange.toFixed(2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
