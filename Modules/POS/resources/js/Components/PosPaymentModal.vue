<script setup lang="ts">
import { computed, ref, watch } from 'vue';

export interface PaymentMethod {
    id: string;
    name: string;
    type: string;
    icon: string;
    enabled: boolean;
    requires_ref: boolean;
}

export interface SplitPaymentLine {
    method_id: string;
    method_name: string;
    amount: number;
    reference?: string;
}

const props = defineProps<{
    show: boolean;
    grandTotal: number;
    subtotal: number;
    discountTotal: number;
    taxTotal: number;
    currencySymbol: string;
    paymentMethods: PaymentMethod[];
    customerName: string;
    customerCreditLimit?: number;
    customerOutstandingBalance?: number;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'completePayment', payload: { payments: SplitPaymentLine[]; changeAmount: number; printReceipt: boolean }): void;
}>();

const selectedMethodId = ref('cash');
const activeTenderInput = ref('');
const paymentLines = ref<SplitPaymentLine[]>([]);
const printReceiptAfterSale = ref(true);

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        selectedMethodId.value = 'cash';
        activeTenderInput.value = props.grandTotal.toFixed(2);
        paymentLines.value = [];
    }
});

const totalTendered = computed(() => {
    const existing = paymentLines.value.reduce((sum, p) => sum + p.amount, 0);
    const activeAmount = parseFloat(activeTenderInput.value) || 0;
    return existing + activeAmount;
});

const remainingBalance = computed(() => {
    return Math.max(0, props.grandTotal - totalTendered.value);
});

const changeDue = computed(() => {
    return Math.max(0, totalTendered.value - props.grandTotal);
});

const isPaymentComplete = computed(() => {
    return totalTendered.value >= props.grandTotal - 0.001;
});

// Numpad Touch Helpers
const handleNumpadPress = (char: string) => {
    if (char === 'CLEAR') {
        activeTenderInput.value = '';
    } else if (char === 'BACKSPACE') {
        activeTenderInput.value = activeTenderInput.value.slice(0, -1);
    } else if (char === '.') {
        if (!activeTenderInput.value.includes('.')) {
            activeTenderInput.value += '.';
        }
    } else {
        if (activeTenderInput.value === '0') {
            activeTenderInput.value = char;
        } else {
            activeTenderInput.value += char;
        }
    }
};

const handleQuickBill = (amount: number) => {
    if (amount === 0) {
        activeTenderInput.value = remainingBalance.value > 0 ? remainingBalance.value.toFixed(2) : props.grandTotal.toFixed(2);
    } else {
        const current = parseFloat(activeTenderInput.value) || 0;
        activeTenderInput.value = (current + amount).toFixed(2);
    }
};

const handleAddSplitPayment = () => {
    const amount = parseFloat(activeTenderInput.value) || 0;
    if (amount <= 0) return;

    const method = props.paymentMethods.find((m) => m.id === selectedMethodId.value);
    if (!method) return;

    paymentLines.value.push({
        method_id: method.id,
        method_name: method.name,
        amount: amount,
    });

    const remaining = props.grandTotal - paymentLines.value.reduce((s, p) => s + p.amount, 0);
    activeTenderInput.value = remaining > 0 ? remaining.toFixed(2) : '0.00';
};

const handleRemoveSplitLine = (index: number) => {
    paymentLines.value.splice(index, 1);
};

const handleFinalize = () => {
    if (!isPaymentComplete.value) return;

    const finalPayments: SplitPaymentLine[] = [...paymentLines.value];
    const activeAmount = parseFloat(activeTenderInput.value) || 0;
    if (activeAmount > 0) {
        const method = props.paymentMethods.find((m) => m.id === selectedMethodId.value);
        finalPayments.push({
            method_id: method ? method.id : 'cash',
            method_name: method ? method.name : 'Cash',
            amount: activeAmount,
        });
    }

    emit('completePayment', {
        payments: finalPayments,
        changeAmount: changeDue.value,
        printReceipt: printReceiptAfterSale.value,
    });
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-xs select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-4xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>Payment & Checkout</span>
                        <span class="text-xs bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 px-2 py-0.5 rounded-md font-mono">
                            {{ customerName }}
                        </span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Select payment methods, input tendered amounts, and finalize receipt</p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body: Left Payment Methods & Numpad / Right Totals & Breakdown -->
            <div class="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-12 divide-y md:divide-y-0 md:divide-x divide-zinc-200 dark:divide-zinc-800">
                <!-- Left: Payment Methods Selector & Tender Keypad (7 cols) -->
                <div class="md:col-span-7 p-6 space-y-5">
                    <!-- Payment Methods Tabs -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-2">
                            Select Payment Method
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="method in paymentMethods"
                                :key="method.id"
                                type="button"
                                @click="selectedMethodId = method.id"
                                :class="[
                                    'p-3 rounded-xl border text-xs font-semibold flex flex-col items-center justify-center gap-1.5 transition cursor-pointer',
                                    selectedMethodId === method.id
                                        ? 'bg-emerald-50 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-300 border-emerald-400 dark:border-emerald-500 shadow-xs ring-1 ring-emerald-400'
                                        : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200'
                                ]"
                            >
                                <svg v-if="method.type === 'cash'" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <svg v-else-if="method.type === 'card'" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                <svg v-else-if="method.type === 'qr'" class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                <svg v-else class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>{{ method.name }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tender Input Display -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                Tendered Amount ({{ currencySymbol }})
                            </label>
                            <button
                                v-if="remainingBalance > 0 && paymentLines.length > 0"
                                type="button"
                                @click="handleAddSplitPayment"
                                class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-semibold flex items-center gap-1 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Split Payment
                            </button>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-400 font-mono text-lg font-bold">
                                {{ currencySymbol }}
                            </div>
                            <input
                                v-model="activeTenderInput"
                                type="text"
                                class="w-full pl-14 pr-4 py-3 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-2xl font-mono font-black text-zinc-900 dark:text-white text-right focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Fast Quick Bills Buttons -->
                    <div>
                        <div class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">
                            Quick Cash Denominations
                        </div>
                        <div class="grid grid-cols-5 gap-2">
                            <button
                                type="button"
                                @click="handleQuickBill(0)"
                                class="py-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-xs font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                            >
                                Exact
                            </button>
                            <button
                                type="button"
                                @click="handleQuickBill(10)"
                                class="py-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-xs font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                            >
                                +10
                            </button>
                            <button
                                type="button"
                                @click="handleQuickBill(20)"
                                class="py-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-xs font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                            >
                                +20
                            </button>
                            <button
                                type="button"
                                @click="handleQuickBill(50)"
                                class="py-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-xs font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                            >
                                +50
                            </button>
                            <button
                                type="button"
                                @click="handleQuickBill(100)"
                                class="py-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-xs font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                            >
                                +100
                            </button>
                        </div>
                    </div>

                    <!-- Touchscreen Numpad Grid -->
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="n in ['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', '00']"
                            :key="n"
                            type="button"
                            @click="handleNumpadPress(n)"
                            class="py-3 rounded-xl bg-white dark:bg-zinc-950 hover:bg-slate-100 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-100 text-lg font-mono font-bold border border-zinc-200 dark:border-zinc-800 shadow-xs transition active:scale-95 cursor-pointer"
                        >
                            {{ n }}
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="handleNumpadPress('CLEAR')"
                            class="py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-400 text-xs font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                        >
                            Clear
                        </button>
                        <button
                            type="button"
                            @click="handleNumpadPress('BACKSPACE')"
                            class="py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-400 text-xs font-bold border border-zinc-200 dark:border-zinc-700 transition flex items-center justify-center gap-1 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/></svg>
                            Backspace
                        </button>
                    </div>
                </div>

                <!-- Right: Breakdown, Split Payments List & Change Due (5 cols) -->
                <div class="md:col-span-5 p-6 bg-slate-50/50 dark:bg-zinc-950/70 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            Transaction Summary
                        </div>

                        <!-- Amounts Breakdown Box -->
                        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2 text-xs shadow-xs">
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Subtotal</span>
                                <span class="font-mono text-zinc-800 dark:text-zinc-200 font-medium">{{ currencySymbol }} {{ subtotal.toFixed(2) }}</span>
                            </div>
                            <div v-if="discountTotal > 0" class="flex justify-between text-emerald-600 dark:text-emerald-400 font-semibold">
                                <span>Discount</span>
                                <span class="font-mono">- {{ currencySymbol }} {{ discountTotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Tax / VAT / SST</span>
                                <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ currencySymbol }} {{ taxTotal.toFixed(2) }}</span>
                            </div>
                            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 flex justify-between items-baseline font-bold">
                                <span class="text-zinc-800 dark:text-zinc-200">Grand Total</span>
                                <span class="text-lg font-mono text-emerald-600 dark:text-emerald-400 font-extrabold">{{ currencySymbol }} {{ grandTotal.toFixed(2) }}</span>
                            </div>
                        </div>

                        <!-- Split Payments List -->
                        <div v-if="paymentLines.length > 0" class="space-y-2">
                            <div class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Applied Split Payments
                            </div>
                            <div class="space-y-1.5">
                                <div
                                    v-for="(line, idx) in paymentLines"
                                    :key="idx"
                                    class="flex items-center justify-between bg-white dark:bg-zinc-900 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs shadow-xs"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ line.method_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ currencySymbol }} {{ line.amount.toFixed(2) }}</span>
                                        <button
                                            type="button"
                                            @click="handleRemoveSplitLine(idx)"
                                            class="text-zinc-400 hover:text-rose-500 cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Tender Status Indicators -->
                        <div class="space-y-3 pt-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-zinc-600 dark:text-zinc-400">Total Tendered</span>
                                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 text-sm">
                                    {{ currencySymbol }} {{ totalTendered.toFixed(2) }}
                                </span>
                            </div>

                            <!-- Remaining Balance or Change Due Display -->
                            <div
                                v-if="remainingBalance > 0"
                                class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 flex justify-between items-center"
                            >
                                <span class="text-xs font-bold text-rose-700 dark:text-rose-300">Remaining Balance</span>
                                <span class="font-mono font-black text-rose-700 dark:text-rose-400 text-lg">
                                    {{ currencySymbol }} {{ remainingBalance.toFixed(2) }}
                                </span>
                            </div>

                            <div
                                v-else
                                class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-500/60 flex justify-between items-center shadow-xs"
                            >
                                <div>
                                    <div class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Change Due</div>
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400/80">Return cash to customer</div>
                                </div>
                                <span class="font-mono font-black text-emerald-700 dark:text-emerald-300 text-2xl animate-pulse">
                                    {{ currencySymbol }} {{ changeDue.toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Print Receipt Option & Complete Button -->
                    <div class="space-y-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <label class="flex items-center space-x-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input
                                v-model="printReceiptAfterSale"
                                type="checkbox"
                                class="rounded bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                            />
                            <span>Print Thermal Receipt (80mm) upon completion</span>
                        </label>

                        <button
                            type="button"
                            :disabled="!isPaymentComplete"
                            @click="handleFinalize"
                            class="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white font-bold text-sm shadow-md shadow-emerald-900/20 flex items-center justify-center gap-2 transition active:scale-98 cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>COMPLETE SALE & PRINT</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
