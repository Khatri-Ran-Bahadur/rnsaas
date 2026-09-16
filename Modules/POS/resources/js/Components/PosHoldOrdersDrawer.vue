<script setup lang="ts">
export interface HeldOrder {
    id: string;
    held_at: string;
    customer_name: string;
    table_number: string;
    order_type: string;
    items_count: number;
    total_amount: number;
    cart_items: any[];
}

const props = defineProps<{
    show: boolean;
    heldOrders: HeldOrder[];
    currencySymbol: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'resumeOrder', order: HeldOrder): void;
    (e: 'discardOrder', orderId: string): void;
}>();
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex justify-end bg-black/50 dark:bg-black/70 backdrop-blur-xs select-none"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-md bg-white dark:bg-zinc-900 border-l border-zinc-200 dark:border-zinc-800 h-full flex flex-col shadow-2xl animate-in slide-in-from-right duration-200">
            <!-- Header -->
            <div class="px-5 py-4 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>Parked / Held Orders</span>
                        <span class="text-xs bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 px-2 py-0.5 rounded-full font-mono font-bold">
                            {{ heldOrders.length }}
                        </span>
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Resume parked transactions without losing cart items</p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- List of Held Orders -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <div
                    v-if="heldOrders.length === 0"
                    class="h-full flex flex-col items-center justify-center text-center p-8 text-zinc-400"
                >
                    <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center mb-2.5 text-zinc-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">No held orders</div>
                    <div class="text-[11px] text-zinc-500 mt-0.5">Orders placed on hold will appear here</div>
                </div>

                <div
                    v-for="order in heldOrders"
                    :key="order.id"
                    class="bg-slate-50 dark:bg-zinc-950 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 hover:border-amber-400 transition space-y-3 shadow-xs"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                                <span>{{ order.customer_name }}</span>
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-mono font-medium">({{ order.table_number }})</span>
                            </div>
                            <div class="text-[10px] text-zinc-500 font-mono mt-0.5 flex items-center gap-2">
                                <span>ID: {{ order.id }}</span>
                                <span>•</span>
                                <span>{{ order.held_at }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ currencySymbol }} {{ order.total_amount.toFixed(2) }}
                            </div>
                            <div class="text-[10px] text-zinc-500">
                                {{ order.items_count }} items
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-2 pt-2 border-t border-zinc-200 dark:border-zinc-900">
                        <button
                            type="button"
                            @click="emit('discardOrder', order.id)"
                            class="px-2.5 py-1.5 rounded-lg text-[11px] text-zinc-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
                        >
                            Discard
                        </button>
                        <button
                            type="button"
                            @click="emit('resumeOrder', order)"
                            class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow transition flex items-center gap-1 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <span>Resume Order</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
