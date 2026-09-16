<script setup lang="ts">
import { computed, ref, watch } from 'vue';

export interface ShiftData {
    id: number;
    shift_number: string;
    cashier_name: string;
    cashier_role: string;
    opened_at: string;
    opening_cash: number;
    status: string;
    total_sales_count: number;
    total_sales_amount: number;
    cash_sales_amount: number;
    cash_in: number;
    cash_out: number;
    expected_cash: number;
}

const props = defineProps<{
    show: boolean;
    shift: ShiftData;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'recordCashMovement', payload: { type: 'cash_in' | 'cash_out'; amount: number; reason: string }): void;
    (e: 'closeShift', payload: { actualCash: number; variance: number; notes: string }): void;
}>();

const activeTab = ref<'summary' | 'movement' | 'close'>('summary');

const movementType = ref<'cash_in' | 'cash_out'>('cash_in');
const movementAmount = ref('');
const movementReason = ref('');

const countedActualCash = ref('');
const closeShiftNotes = ref('');

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        activeTab.value = 'summary';
        movementAmount.value = '';
        movementReason.value = '';
        countedActualCash.value = props.shift.expected_cash.toFixed(2);
        closeShiftNotes.value = '';
    }
});

const calculatedVariance = computed(() => {
    const counted = parseFloat(countedActualCash.value) || 0;
    return counted - props.shift.expected_cash;
});

const handleSaveMovement = () => {
    const amt = parseFloat(movementAmount.value) || 0;
    if (amt <= 0 || !movementReason.value.trim()) return;
    emit('recordCashMovement', {
        type: movementType.value,
        amount: amt,
        reason: movementReason.value.trim(),
    });
    activeTab.value = 'summary';
};

const handleConfirmCloseShift = () => {
    const counted = parseFloat(countedActualCash.value) || 0;
    emit('closeShift', {
        actualCash: counted,
        variance: calculatedVariance.value,
        notes: closeShiftNotes.value.trim(),
    });
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-xs select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>Cash Drawer & Shift Management</span>
                    </h3>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">
                        Shift: #{{ shift.shift_number }} • Cashier: {{ shift.cashier_name }}
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="px-6 pt-3 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex space-x-4">
                <button
                    type="button"
                    @click="activeTab = 'summary'"
                    :class="[
                        'pb-2.5 text-xs font-semibold border-b-2 transition flex items-center gap-1.5 cursor-pointer',
                        activeTab === 'summary' ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-neutral-200'
                    ]"
                >
                    Drawer Summary
                </button>
                <button
                    type="button"
                    @click="activeTab = 'movement'"
                    :class="[
                        'pb-2.5 text-xs font-semibold border-b-2 transition flex items-center gap-1.5 cursor-pointer',
                        activeTab === 'movement' ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-neutral-200'
                    ]"
                >
                    Cash In / Cash Out
                </button>
                <button
                    type="button"
                    @click="activeTab = 'close'"
                    :class="[
                        'pb-2.5 text-xs font-semibold border-b-2 transition flex items-center gap-1.5 cursor-pointer',
                        activeTab === 'close' ? 'border-rose-600 text-rose-700 dark:text-rose-400' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-neutral-200'
                    ]"
                >
                    Close Shift (Z-Report)
                </button>
            </div>

            <!-- Tab 1: Summary -->
            <div v-if="activeTab === 'summary'" class="p-6 overflow-y-auto space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50 dark:bg-zinc-950 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <div class="text-[10px] text-zinc-400 uppercase font-bold">Opening Float</div>
                        <div class="text-base font-bold font-mono text-zinc-800 dark:text-zinc-200 mt-0.5">
                            {{ currencySymbol }} {{ shift.opening_cash.toFixed(2) }}
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-zinc-950 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <div class="text-[10px] text-zinc-400 uppercase font-bold">Cash Sales</div>
                        <div class="text-base font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                            {{ currencySymbol }} {{ shift.cash_sales_amount.toFixed(2) }}
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-zinc-950 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <div class="text-[10px] text-zinc-400 uppercase font-bold">Cash In (Paid In)</div>
                        <div class="text-base font-bold font-mono text-blue-600 dark:text-blue-400 mt-0.5">
                            + {{ currencySymbol }} {{ shift.cash_in.toFixed(2) }}
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-zinc-950 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <div class="text-[10px] text-zinc-400 uppercase font-bold">Cash Out (Payouts)</div>
                        <div class="text-base font-bold font-mono text-rose-600 dark:text-rose-400 mt-0.5">
                            - {{ currencySymbol }} {{ shift.cash_out.toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- Expected Cash Highlight Card -->
                <div class="bg-emerald-50 dark:bg-zinc-950 p-4 rounded-xl border border-emerald-300 dark:border-emerald-500/40 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-emerald-900 dark:text-zinc-200 uppercase">Calculated Expected Cash in Drawer</div>
                        <div class="text-[11px] text-emerald-700 dark:text-zinc-400 mt-0.5">Opening Float + Cash Sales + In - Out</div>
                    </div>
                    <div class="text-xl font-black font-mono text-emerald-700 dark:text-emerald-400">
                        {{ currencySymbol }} {{ shift.expected_cash.toFixed(2) }}
                    </div>
                </div>

                <div class="flex justify-between items-center pt-2 text-xs">
                    <span class="text-zinc-500">Total Transactions Today:</span>
                    <span class="font-bold font-mono text-zinc-800 dark:text-zinc-200">{{ shift.total_sales_count }} Orders</span>
                </div>
            </div>

            <!-- Tab 2: Record Cash Movement -->
            <div v-else-if="activeTab === 'movement'" class="p-6 overflow-y-auto space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Movement Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="movementType = 'cash_in'"
                            :class="[
                                'py-2 rounded-xl text-xs font-semibold border transition cursor-pointer',
                                movementType === 'cash_in' ? 'bg-emerald-50 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-300 border-emerald-400' : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800'
                            ]"
                        >
                            Cash In (Add to Drawer)
                        </button>
                        <button
                            type="button"
                            @click="movementType = 'cash_out'"
                            :class="[
                                'py-2 rounded-xl text-xs font-semibold border transition cursor-pointer',
                                movementType === 'cash_out' ? 'bg-rose-50 dark:bg-rose-600/20 text-rose-800 dark:text-rose-300 border-rose-400' : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800'
                            ]"
                        >
                            Cash Out (Expense / Drop)
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Amount ({{ currencySymbol }})</label>
                    <input
                        v-model="movementAmount"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm font-mono text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Reason / Description</label>
                    <input
                        v-model="movementReason"
                        type="text"
                        placeholder="e.g. Bank change topup, emergency ice purchase..."
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-xs text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button
                        type="button"
                        @click="activeTab = 'summary'"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-medium cursor-pointer"
                    >
                        Back
                    </button>
                    <button
                        type="button"
                        @click="handleSaveMovement"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow transition cursor-pointer"
                    >
                        Record Movement
                    </button>
                </div>
            </div>

            <!-- Tab 3: Close Shift & Z-Report -->
            <div v-else-if="activeTab === 'close'" class="p-6 overflow-y-auto space-y-4">
                <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 p-3 rounded-xl text-xs text-amber-800 dark:text-amber-300">
                    Closing the shift will balance the register, compute cash variance, and print the end-of-shift Z-Report.
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Counted Physical Cash ({{ currencySymbol }})
                    </label>
                    <input
                        v-model="countedActualCash"
                        type="number"
                        step="0.01"
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-lg font-mono font-bold text-zinc-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <div class="bg-slate-50 dark:bg-zinc-950 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Cash Drawer Variance</div>
                        <div class="text-[10px] text-zinc-400">Actual Counted - Expected Cash</div>
                    </div>
                    <div
                        class="text-base font-black font-mono"
                        :class="calculatedVariance === 0 ? 'text-emerald-600 dark:text-emerald-400' : calculatedVariance > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-rose-600 dark:text-rose-400'"
                    >
                        {{ calculatedVariance >= 0 ? `+${currencySymbol}${calculatedVariance.toFixed(2)}` : `-${currencySymbol}${Math.abs(calculatedVariance).toFixed(2)}` }}
                        <span class="text-[11px] font-normal font-sans ml-1">
                            ({{ calculatedVariance === 0 ? 'Exact' : calculatedVariance > 0 ? 'Over' : 'Short' }})
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Shift Closing Notes</label>
                    <textarea
                        v-model="closeShiftNotes"
                        rows="2"
                        placeholder="Optional remarks regarding shift hand-over..."
                        class="w-full px-3.5 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl text-xs text-zinc-800 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    ></textarea>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button
                        type="button"
                        @click="activeTab = 'summary'"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-medium cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="handleConfirmCloseShift"
                        class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow transition cursor-pointer"
                    >
                        End Shift & Print Z-Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
