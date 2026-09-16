<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Modal, Button } from '@/components';

export interface ShiftRecord {
    id: number;
    shift_number: string;
    terminal_name: string;
    cashier_name: string;
    opened_at: string;
    closed_at?: string | null;
    opening_cash: number;
    closing_cash?: number | null;
    expected_cash: number;
    variance: number;
    status: 'open' | 'closed';
    total_sales_count: number;
    total_sales_amount: number;
}

export interface CashMovement {
    id: number;
    time: string;
    type: string;
    amount: number;
    reason: string;
    authorized_by: string;
}

export interface ActiveShift {
    id: number;
    shift_number: string;
    terminal_name: string;
    cashier_name: string;
    opened_at: string;
    opening_float: number;
    cash_sales: number;
    card_sales: number;
    qr_sales: number;
    total_sales: number;
    cash_in: number;
    cash_out: number;
    expected_cash: number;
    status: string;
    transaction_count: number;
}

const props = withDefaults(defineProps<{
    shifts?: ShiftRecord[];
    stats?: {
        active_shifts_count: number;
        today_total_sales: number;
        today_cash_collected: number;
        total_variance: number;
    };
    activeShift?: ActiveShift | null;
    movements?: CashMovement[];
    pastShifts?: any[];
}>(), {
    shifts: () => [],
    stats: () => ({
        active_shifts_count: 0,
        today_total_sales: 0,
        today_cash_collected: 0,
        total_variance: 0,
    }),
    activeShift: null,
    movements: () => [],
    pastShifts: () => [],
});

const showCashMovementModal = ref(false);
const showCloseShiftModal = ref(false);
const showOpenShiftModal = ref(false);

const movementForm = useForm({
    type: 'cash_in',
    amount: 50,
    reason: '',
});

const closeShiftForm = useForm({
    actual_cash: props.activeShift?.expected_cash || 0,
    notes: '',
});

const openShiftForm = useForm({
    terminal_name: 'Counter 01 - Main POS',
    opening_float: 200,
});

const submitMovement = () => {
    movementForm.post('/admin/pos/shifts/movement', {
        onSuccess: () => {
            showCashMovementModal.value = false;
            movementForm.reset();
        }
    });
};

const submitCloseShift = () => {
    closeShiftForm.post('/admin/pos/shifts/close', {
        onSuccess: () => {
            showCloseShiftModal.value = false;
        }
    });
};

const submitOpenShift = () => {
    openShiftForm.post('/admin/pos/shifts/open', {
        onSuccess: () => {
            showOpenShiftModal.value = false;
        }
    });
};

import { useCurrency } from '@/composables/useCurrency';

const { currencyCode, currencySymbol, formatMoney: formatTenantMoney } = useCurrency();

const formatMoney = (val?: number) => {
    return currencySymbol.value + ' ' + Number(val || 0).toFixed(2);
};
</script>

<template>
    <Head title="POS Shifts & Cash Drawer - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/pos" class="hover:text-emerald-600 transition">POS Register</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Shift Management</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Cashier Shifts & Cash Drawer Register</span>
                    </h1>
                </div>

                <div class="flex items-center space-x-2.5">
                    <button
                        v-if="!activeShift"
                        @click="showOpenShiftModal = true"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2"
                    >
                        <span>+ Open New Shift</span>
                    </button>
                    <Link
                        href="/admin/pos"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-white shadow-sm transition flex items-center gap-2"
                    >
                        <span>Open POS Register</span>
                    </Link>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Active Shifts</div>
                    <div class="text-2xl font-extrabold text-zinc-900 dark:text-white font-mono">
                        {{ stats.active_shifts_count }}
                    </div>
                    <div class="text-[11px] text-emerald-600 font-medium">Currently logged-in registers</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Today's POS Sales</div>
                    <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ formatMoney(stats.today_total_sales) }}
                    </div>
                    <div class="text-[11px] text-zinc-400">All payment methods combined</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Cash in Drawer</div>
                    <div class="text-2xl font-extrabold text-zinc-900 dark:text-white font-mono">
                        {{ formatMoney(stats.today_cash_collected) }}
                    </div>
                    <div class="text-[11px] text-zinc-400">Physical drawer cash balance</div>
                </Card>

                <Card class="p-4 space-y-1">
                    <div class="text-xs font-medium text-zinc-500">Drawer Variance</div>
                    <div
                        class="text-2xl font-extrabold font-mono"
                        :class="stats.total_variance === 0 ? 'text-zinc-900 dark:text-white' : stats.total_variance > 0 ? 'text-blue-600' : 'text-rose-600'"
                    >
                        {{ stats.total_variance >= 0 ? `+${formatMoney(stats.total_variance)}` : `-${formatMoney(Math.abs(stats.total_variance))}` }}
                    </div>
                    <div class="text-[11px] text-zinc-400">Over / Short reconciliation total</div>
                </Card>
            </div>

            <!-- Active Shift Detail Card -->
            <div v-if="activeShift" class="bg-white dark:bg-zinc-900 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                ACTIVE SESSION
                            </span>
                            <span class="font-mono font-bold text-sm text-zinc-900 dark:text-white">#{{ activeShift.shift_number }}</span>
                        </div>
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white mt-1">
                            {{ activeShift.terminal_name }} — Cashier: {{ activeShift.cashier_name }}
                        </h3>
                        <p class="text-xs text-zinc-500">Opened at {{ activeShift.opened_at }}</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button
                            @click="showCashMovementModal = true"
                            class="px-3.5 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-xs font-bold text-zinc-800 dark:text-zinc-200 transition"
                        >
                            Cash In / Out
                        </button>
                        <button
                            @click="showCloseShiftModal = true"
                            class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition shadow-sm"
                        >
                            Close Shift & Reconcile
                        </button>
                    </div>
                </div>

                <!-- Financial Breakdown Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl text-center">
                        <span class="text-[10px] uppercase font-bold text-zinc-400">Opening Float</span>
                        <p class="text-sm font-mono font-black text-zinc-800 dark:text-zinc-100 mt-0.5">{{ formatMoney(activeShift.opening_float) }}</p>
                    </div>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl text-center">
                        <span class="text-[10px] uppercase font-bold text-zinc-400">Cash Sales</span>
                        <p class="text-sm font-mono font-black text-emerald-600 mt-0.5">+{{ formatMoney(activeShift.cash_sales) }}</p>
                    </div>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl text-center">
                        <span class="text-[10px] uppercase font-bold text-zinc-400">Card / QR Sales</span>
                        <p class="text-sm font-mono font-black text-indigo-600 dark:text-indigo-400 mt-0.5">{{ formatMoney(activeShift.card_sales + activeShift.qr_sales) }}</p>
                    </div>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl text-center">
                        <span class="text-[10px] uppercase font-bold text-zinc-400">Cash In (Float Up)</span>
                        <p class="text-sm font-mono font-black text-blue-600 mt-0.5">+{{ formatMoney(activeShift.cash_in) }}</p>
                    </div>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl text-center">
                        <span class="text-[10px] uppercase font-bold text-zinc-400">Petty Cash Out</span>
                        <p class="text-sm font-mono font-black text-rose-500 mt-0.5">-{{ formatMoney(activeShift.cash_out) }}</p>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-center">
                        <span class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-400">Expected Drawer Cash</span>
                        <p class="text-sm font-mono font-black text-emerald-700 dark:text-emerald-300 mt-0.5">{{ formatMoney(activeShift.expected_cash) }}</p>
                    </div>
                </div>

                <!-- Cash Movements Log -->
                <div v-if="movements && movements.length" class="space-y-2 pt-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Cash Drawer Log Today</h4>
                    <div class="space-y-1.5">
                        <div
                            v-for="m in movements"
                            :key="m.id"
                            class="p-2.5 rounded-xl border border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30 flex items-center justify-between text-xs"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="font-mono text-zinc-400">{{ m.time.split(' ')[1] }}</span>
                                <Badge :variant="m.amount >= 0 ? 'success' : 'danger'" size="sm">{{ m.type.replace('_', ' ').toUpperCase() }}</Badge>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ m.reason }}</span>
                            </div>
                            <span class="font-mono font-bold" :class="m.amount >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                                {{ m.amount >= 0 ? `+${formatMoney(m.amount)}` : `-${formatMoney(Math.abs(m.amount))}` }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shifts History Table -->
            <Card class="overflow-hidden">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Shift Audit Trail</h3>
                    <span class="text-xs text-zinc-500">Total: {{ shifts.length }} Shifts Logged</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Shift #</th>
                                <th class="px-4 py-3">Terminal & Cashier</th>
                                <th class="px-4 py-3">Opened At</th>
                                <th class="px-4 py-3">Closed At</th>
                                <th class="px-4 py-3 text-right">Opening Cash</th>
                                <th class="px-4 py-3 text-right">Sales Amount</th>
                                <th class="px-4 py-3 text-right">Variance</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="shift in shifts"
                                :key="shift.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3 font-mono font-bold text-emerald-600">
                                    #{{ shift.shift_number }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-zinc-800 dark:text-zinc-200">{{ shift.cashier_name }}</div>
                                    <div class="text-[10px] text-zinc-500 font-mono">{{ shift.terminal_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 font-mono">
                                    {{ shift.opened_at }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 font-mono">
                                    {{ shift.closed_at || '— Still Open —' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ formatMoney(shift.opening_cash) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600">
                                    {{ formatMoney(shift.total_sales_amount) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold">
                                    <span :class="shift.variance === 0 ? 'text-zinc-500' : shift.variance > 0 ? 'text-blue-600' : 'text-rose-600'">
                                        {{ shift.variance >= 0 ? `+${formatMoney(shift.variance)}` : `-${formatMoney(Math.abs(shift.variance))}` }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="shift.status === 'open' ? 'success' : 'secondary'" class="uppercase text-[9px]">
                                        {{ shift.status }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="shifts.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-zinc-500 text-xs">
                                    No shift records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Cash Movement Modal -->
            <Modal :show="showCashMovementModal" @close="showCashMovementModal = false" max-width="md">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Record Cash In / Out</h3>
                        <button @click="showCashMovementModal = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                    </div>

                    <form @submit.prevent="submitMovement" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Movement Type</label>
                            <select
                                v-model="movementForm.type"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                            >
                                <option value="cash_in">Cash In (Drawer Top-up / Extra Change)</option>
                                <option value="cash_out">Cash Out (Petty Cash Payout / Expense)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Amount (RM)</label>
                            <input
                                v-model.number="movementForm.amount"
                                type="number"
                                step="0.5"
                                min="1"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Reason / Description</label>
                            <input
                                v-model="movementForm.reason"
                                type="text"
                                placeholder="e.g. Replenish RM1 and RM5 notes"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                                required
                            />
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                            <Button variant="secondary" @click="showCashMovementModal = false" type="button">Cancel</Button>
                            <Button variant="primary" type="submit" :loading="movementForm.processing">Save Movement</Button>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Close Shift & Reconcile Modal -->
            <Modal :show="showCloseShiftModal" @close="showCloseShiftModal = false" max-width="md">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Close Shift & Reconcile</h3>
                        <button @click="showCloseShiftModal = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                    </div>

                    <form @submit.prevent="submitCloseShift" class="space-y-4">
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-xl space-y-1 text-xs">
                            <div class="flex justify-between text-zinc-500">
                                <span>Expected Drawer Cash:</span>
                                <strong class="text-zinc-800 dark:text-zinc-100 font-mono">{{ formatMoney(activeShift?.expected_cash) }}</strong>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Physical Cash Counted (RM)</label>
                            <input
                                v-model.number="closeShiftForm.actual_cash"
                                type="number"
                                step="0.1"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                                required
                            />
                            <div class="mt-1 text-xs" :class="(closeShiftForm.actual_cash - (activeShift?.expected_cash || 0)) === 0 ? 'text-emerald-600' : 'text-rose-500'">
                                Discrepancy: {{ formatMoney(closeShiftForm.actual_cash - (activeShift?.expected_cash || 0)) }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Handover / Shift Notes</label>
                            <textarea
                                v-model="closeShiftForm.notes"
                                rows="2"
                                placeholder="Any drawer discrepancies, damaged notes, or register notes..."
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                            <Button variant="secondary" @click="showCloseShiftModal = false" type="button">Cancel</Button>
                            <Button variant="primary" type="submit" :loading="closeShiftForm.processing">Generate Z-Report & Close</Button>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Open Shift Modal -->
            <Modal :show="showOpenShiftModal" @close="showOpenShiftModal = false" max-width="md">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Open Cashier Shift</h3>
                        <button @click="showOpenShiftModal = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                    </div>

                    <form @submit.prevent="submitOpenShift" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">POS Terminal</label>
                            <input
                                v-model="openShiftForm.terminal_name"
                                type="text"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Opening Cash Float (RM)</label>
                            <input
                                v-model.number="openShiftForm.opening_float"
                                type="number"
                                step="1"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                                required
                            />
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                            <Button variant="secondary" @click="showOpenShiftModal = false" type="button">Cancel</Button>
                            <Button variant="primary" type="submit" :loading="openShiftForm.processing">Start Shift</Button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </OrganizationLayout>
</template>
