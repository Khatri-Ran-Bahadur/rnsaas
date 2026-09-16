<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface CartLineItem {
    id: string;
    product_id: number;
    name: string;
    sku: string;
    variant_id?: string;
    variant_name?: string;
    unit_price: number;
    quantity: number;
    discount_amount: number;
    discount_percent: number;
    tax_rate: number;
    modifiers: Array<{ id: string; name: string; price: number }>;
    notes?: string;
}

export interface Customer {
    id: number;
    name: string;
    phone: string;
    customer_group: string;
    price_tier: string;
    discount_rate: number;
    outstanding_balance: number;
    credit_limit: number;
}

const props = defineProps<{
    cartItems: CartLineItem[];
    customers: Customer[];
    selectedCustomer: Customer;
    orderType: 'dine_in' | 'takeaway' | 'delivery';
    currencySymbol: string;
    orderDiscount: { type: 'percent' | 'fixed'; value: number };
    taxInclusive: boolean;
    defaultTaxRate: number;
    selectedTable?: string | null;
}>();

const emit = defineEmits<{
    (e: 'update:selectedCustomer', customer: Customer): void;
    (e: 'update:orderType', type: 'dine_in' | 'takeaway' | 'delivery'): void;
    (e: 'updateQuantity', { lineId, delta }: { lineId: string; delta: number }): void;
    (e: 'setQuantity', { lineId, quantity }: { lineId: string; quantity: number }): void;
    (e: 'removeLine', lineId: string): void;
    (e: 'editLineNotes', lineId: string): void;
    (e: 'editLinePrice', lineId: string): void;
    (e: 'openOrderDiscountModal'): void;
    (e: 'holdOrder'): void;
    (e: 'clearCart'): void;
    (e: 'proceedPayment'): void;
    (e: 'quickCashPayment', amount: number): void;
    (e: 'openCustomerSelectModal'): void;
    (e: 'openNewCustomerModal'): void;
}>();

// Calculations
const subtotal = computed(() => {
    return props.cartItems.reduce((sum, item) => {
        const modifierAddons = item.modifiers.reduce((mSum, m) => mSum + m.price, 0);
        const lineItemPrice = (item.unit_price + modifierAddons);
        const lineTotal = (lineItemPrice * item.quantity) - item.discount_amount;
        return sum + Math.max(0, lineTotal);
    }, 0);
});

const discountTotal = computed(() => {
    let orderDisc = 0;
    if (props.orderDiscount.type === 'percent') {
        orderDisc = (subtotal.value * props.orderDiscount.value) / 100;
    } else {
        orderDisc = props.orderDiscount.value;
    }
    if (props.selectedCustomer.discount_rate > 0 && props.orderDiscount.value === 0) {
        orderDisc = (subtotal.value * props.selectedCustomer.discount_rate) / 100;
    }
    return Math.min(subtotal.value, orderDisc);
});

const taxableAmount = computed(() => {
    return Math.max(0, subtotal.value - discountTotal.value);
});

const taxTotal = computed(() => {
    if (props.taxInclusive) {
        return (taxableAmount.value * props.defaultTaxRate) / (100 + props.defaultTaxRate);
    } else {
        return (taxableAmount.value * props.defaultTaxRate) / 100;
    }
});

const page = usePage();
const isHospitality = computed(() => {
    const tenant = (page.props as any).current_tenant || (page.props as any).currentTenant;
    if (!tenant) return true;
    if (typeof tenant.is_hospitality !== 'undefined') return Boolean(tenant.is_hospitality);
    const ind = String(tenant.industry || '').toLowerCase();
    return ['restaurant', 'food_beverage', 'hospitality', 'hotel'].includes(ind);
});

const serviceChargeTotal = computed(() => {
    if (!isHospitality.value) return 0;
    if (props.orderType === 'dine_in') {
        return taxableAmount.value * 0.10;
    }
    return 0;
});

const grandTotal = computed(() => {
    if (props.taxInclusive) {
        return taxableAmount.value + serviceChargeTotal.value;
    } else {
        return taxableAmount.value + taxTotal.value + serviceChargeTotal.value;
    }
});
</script>

<template>
    <div class="w-full lg:w-[420px] xl:w-[460px] bg-white dark:bg-zinc-900 flex flex-col h-full shrink-0 select-none border-l border-zinc-200 dark:border-zinc-800 transition-colors shadow-xs">
        <!-- Cart Header: Customer & Order Type Selector -->
        <div class="p-3 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 space-y-2.5 shrink-0">
            <!-- Customer Selector Bar -->
            <div class="flex items-center justify-between bg-slate-50 dark:bg-zinc-950 p-2 rounded-xl border border-zinc-200 dark:border-zinc-800">
                <button
                    type="button"
                    @click="emit('openCustomerSelectModal')"
                    class="flex items-center space-x-2.5 text-left flex-1 min-w-0 group cursor-pointer"
                >
                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 flex items-center justify-center text-xs shrink-0 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div class="truncate">
                        <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 truncate">
                            {{ selectedCustomer.name }}
                        </div>
                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400 font-mono">
                            {{ selectedCustomer.customer_group }}
                            <span v-if="selectedCustomer.discount_rate > 0" class="text-emerald-600 dark:text-emerald-400 font-bold ml-1">
                                ({{ selectedCustomer.discount_rate }}% Off)
                            </span>
                        </div>
                    </div>
                </button>

                <div class="flex items-center space-x-1 shrink-0">
                    <button
                        type="button"
                        @click="emit('openNewCustomerModal')"
                        class="p-1 bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded-lg border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                        title="Add New Customer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </button>
                    <button
                        type="button"
                        @click="emit('openCustomerSelectModal')"
                        class="px-2 py-1 bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-[11px] rounded-lg font-medium border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                    >
                        Change
                    </button>
                </div>
            </div>

            <!-- Order Type & Table Identifier -->
            <div :class="['grid gap-1.5 bg-slate-50 dark:bg-zinc-950 p-1 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs', isHospitality ? 'grid-cols-3' : 'grid-cols-2']">
                <button
                    v-if="isHospitality"
                    type="button"
                    @click="emit('update:orderType', 'dine_in')"
                    :class="[
                        'py-1 rounded-lg font-medium text-center transition flex items-center justify-center gap-1 cursor-pointer',
                        orderType === 'dine_in' ? 'bg-amber-600 text-white font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                    ]"
                >
                    <span>Dine-In</span>
                    <span v-if="selectedTable" class="text-[10px] font-mono opacity-90">({{ selectedTable }})</span>
                </button>
                <button
                    type="button"
                    @click="emit('update:orderType', 'takeaway')"
                    :class="[
                        'py-1 rounded-lg font-medium text-center transition cursor-pointer',
                        orderType === 'takeaway' ? 'bg-emerald-600 text-white font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                    ]"
                >
                    Takeaway
                </button>
                <button
                    type="button"
                    @click="emit('update:orderType', 'delivery')"
                    :class="[
                        'py-1 rounded-lg font-medium text-center transition cursor-pointer',
                        orderType === 'delivery' ? 'bg-blue-600 text-white font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'
                    ]"
                >
                    Delivery
                </button>
            </div>
        </div>

        <!-- Cart Items List Container -->
        <div class="flex-1 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/80 p-2">
            <!-- Empty Cart State -->
            <div
                v-if="cartItems.length === 0"
                class="h-full flex flex-col items-center justify-center text-center p-6 text-zinc-400"
            >
                <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center mb-2.5 text-zinc-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Cart is empty</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Scan a barcode or touch an item to add</div>
            </div>

            <!-- Active Cart Lines -->
            <div
                v-for="item in cartItems"
                :key="item.id"
                class="py-2.5 px-2 hover:bg-slate-50/80 dark:hover:bg-zinc-950/60 rounded-xl transition group"
            >
                <div class="flex items-start justify-between gap-2">
                    <!-- Item Title & Variant -->
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-zinc-800 dark:text-zinc-100 leading-snug">
                            {{ item.name }}
                        </div>
                        <div v-if="item.variant_name" class="text-[11px] font-mono text-blue-600 dark:text-blue-400 font-medium mt-0.5">
                            {{ item.variant_name }}
                        </div>

                        <!-- Modifiers Chips -->
                        <div v-if="item.modifiers && item.modifiers.length > 0" class="flex flex-wrap gap-1 mt-1">
                            <span
                                v-for="m in item.modifiers"
                                :key="m.id"
                                class="text-[10px] bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 px-1.5 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/60"
                            >
                                + {{ m.name }}
                                <span v-if="m.price > 0" class="text-emerald-600 dark:text-emerald-400 font-mono">(+{{ currencySymbol }}{{ m.price.toFixed(2) }})</span>
                            </span>
                        </div>

                        <!-- Item Cooking Notes -->
                        <div v-if="item.notes" class="text-[10px] text-amber-600 dark:text-amber-400 italic mt-0.5 flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            <span>{{ item.notes }}</span>
                        </div>
                    </div>

                    <!-- Line Price Calculation -->
                    <div class="text-right shrink-0">
                        <div class="text-xs font-mono font-bold text-zinc-900 dark:text-zinc-100">
                            {{ currencySymbol }} {{ (((item.unit_price + item.modifiers.reduce((s, m) => s + m.price, 0)) * item.quantity) - item.discount_amount).toFixed(2) }}
                        </div>
                        <div class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500">
                            @ {{ currencySymbol }} {{ (item.unit_price + item.modifiers.reduce((s, m) => s + m.price, 0)).toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- Quantity Stepper & Line Item Actions -->
                <div class="flex items-center justify-between mt-2 pt-1.5">
                    <div class="flex items-center space-x-1 bg-slate-50 dark:bg-zinc-950 p-0.5 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <button
                            type="button"
                            @click="emit('updateQuantity', { lineId: item.id, delta: -1 })"
                            class="w-7 h-7 rounded-lg bg-white dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 flex items-center justify-center font-bold text-sm transition cursor-pointer shadow-xs"
                        >
                            -
                        </button>
                        <span class="w-8 text-center text-xs font-mono font-bold text-zinc-800 dark:text-zinc-200">
                            {{ item.quantity }}
                        </span>
                        <button
                            type="button"
                            @click="emit('updateQuantity', { lineId: item.id, delta: 1 })"
                            class="w-7 h-7 rounded-lg bg-white dark:bg-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 flex items-center justify-center font-bold text-sm transition cursor-pointer shadow-xs"
                        >
                            +
                        </button>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Edit Line Price Override -->
                        <button
                            type="button"
                            @click="emit('editLinePrice', item.id)"
                            class="p-1 rounded-lg text-zinc-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-zinc-800 transition text-[11px] cursor-pointer"
                            title="Override unit price"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>

                        <!-- Add / Edit Line Note -->
                        <button
                            type="button"
                            @click="emit('editLineNotes', item.id)"
                            class="p-1 rounded-lg text-zinc-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-zinc-800 transition text-[11px] cursor-pointer"
                            title="Add special instructions or cooking note"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>

                        <!-- Delete Line Item -->
                        <button
                            type="button"
                            @click="emit('removeLine', item.id)"
                            class="p-1 rounded-lg text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition text-[11px] cursor-pointer"
                            title="Remove item"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Totals & Breakdown Summary -->
        <div class="p-3.5 bg-slate-50/80 dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 space-y-1.5 text-xs shrink-0 transition-colors">
            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <span>Subtotal ({{ cartItems.reduce((acc, i) => acc + i.quantity, 0) }} items)</span>
                <span class="font-mono text-zinc-800 dark:text-zinc-200 font-medium">{{ currencySymbol }} {{ subtotal.toFixed(2) }}</span>
            </div>

            <div v-if="discountTotal > 0" class="flex justify-between text-emerald-700 dark:text-emerald-400">
                <span class="flex items-center gap-1">
                    <span>Discount</span>
                    <span v-if="orderDiscount.value > 0" class="text-[10px] font-mono font-bold">
                        ({{ orderDiscount.type === 'percent' ? `${orderDiscount.value}%` : `${currencySymbol}${orderDiscount.value}` }})
                    </span>
                </span>
                <span class="font-mono font-bold">- {{ currencySymbol }} {{ discountTotal.toFixed(2) }}</span>
            </div>

            <div v-if="serviceChargeTotal > 0" class="flex justify-between text-amber-700 dark:text-amber-400">
                <span>Dine-in Service Charge (10%)</span>
                <span class="font-mono font-medium">+ {{ currencySymbol }} {{ serviceChargeTotal.toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                <span class="text-[11px]">Tax ({{ defaultTaxRate }}% SST {{ taxInclusive ? 'Inc.' : 'Excl.' }})</span>
                <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ currencySymbol }} {{ taxTotal.toFixed(2) }}</span>
            </div>

            <!-- Grand Total Highlight Bar -->
            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800 flex items-baseline justify-between">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Payable</div>
                    <div class="text-[10px] text-zinc-400 dark:text-zinc-500">Includes all taxes & discounts</div>
                </div>
                <div class="text-xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                    {{ currencySymbol }} {{ grandTotal.toFixed(2) }}
                </div>
            </div>

            <!-- Quick Cash Tender Bar (Exact, +10, +20, +50, +100) -->
            <div v-if="cartItems.length > 0" class="pt-2 border-t border-zinc-200/80 dark:border-zinc-800/80">
                <div class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider mb-1">
                    Quick Cash Tender
                </div>
                <div class="grid grid-cols-5 gap-1.5">
                    <button
                        type="button"
                        @click="emit('quickCashPayment', grandTotal)"
                        class="py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 text-[11px] font-mono font-bold border border-emerald-200 dark:border-emerald-800/60 transition cursor-pointer"
                    >
                        Exact
                    </button>
                    <button
                        type="button"
                        @click="emit('quickCashPayment', 10)"
                        class="py-1.5 rounded-lg bg-white dark:bg-zinc-900 hover:bg-zinc-100 text-zinc-700 dark:text-zinc-300 text-[11px] font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                    >
                        10
                    </button>
                    <button
                        type="button"
                        @click="emit('quickCashPayment', 20)"
                        class="py-1.5 rounded-lg bg-white dark:bg-zinc-900 hover:bg-zinc-100 text-zinc-700 dark:text-zinc-300 text-[11px] font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                    >
                        20
                    </button>
                    <button
                        type="button"
                        @click="emit('quickCashPayment', 50)"
                        class="py-1.5 rounded-lg bg-white dark:bg-zinc-900 hover:bg-zinc-100 text-zinc-700 dark:text-zinc-300 text-[11px] font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                    >
                        50
                    </button>
                    <button
                        type="button"
                        @click="emit('quickCashPayment', 100)"
                        class="py-1.5 rounded-lg bg-white dark:bg-zinc-900 hover:bg-zinc-100 text-zinc-700 dark:text-zinc-300 text-[11px] font-mono font-bold border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
                    >
                        100
                    </button>
                </div>
            </div>
        </div>

        <!-- Operational Action Footer -->
        <div class="p-2.5 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-4 gap-2 shrink-0">
            <!-- Hold Order Button -->
            <button
                type="button"
                :disabled="cartItems.length === 0"
                @click="emit('holdOrder')"
                class="py-2 px-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 disabled:opacity-40 text-zinc-700 dark:text-zinc-300 text-xs font-semibold flex flex-col items-center justify-center gap-0.5 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
            >
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Hold</span>
            </button>

            <!-- Discount Modal Trigger -->
            <button
                type="button"
                :disabled="cartItems.length === 0"
                @click="emit('openOrderDiscountModal')"
                class="py-2 px-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 disabled:opacity-40 text-zinc-700 dark:text-zinc-300 text-xs font-semibold flex flex-col items-center justify-center gap-0.5 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
            >
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Discount</span>
            </button>

            <!-- Void / Clear Cart Button -->
            <button
                type="button"
                :disabled="cartItems.length === 0"
                @click="emit('clearCart')"
                class="py-2 px-2 rounded-xl bg-slate-50 dark:bg-zinc-800 hover:bg-rose-50 dark:hover:bg-rose-950/60 hover:text-rose-700 dark:hover:text-rose-300 disabled:opacity-40 text-zinc-600 dark:text-zinc-400 text-xs font-semibold flex flex-col items-center justify-center gap-0.5 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer"
            >
                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span>Void</span>
            </button>

            <!-- Main Pay Button (Large High-Performance Action) -->
            <button
                type="button"
                :disabled="cartItems.length === 0"
                @click="emit('proceedPayment')"
                class="col-span-1 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white text-xs font-bold flex flex-col items-center justify-center gap-0.5 shadow-md shadow-emerald-900/20 transition active:scale-95 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>PAY (F4)</span>
            </button>
        </div>
    </div>
</template>
