<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Modal } from '@/components';
import PosReceiptModal, { type ReceiptData } from '../../Components/PosReceiptModal.vue';

export interface OrderRecord {
    id: number;
    invoice_number: string;
    date: string;
    customer_name: string;
    order_type: string;
    table_name?: string | null;
    items_count: number;
    subtotal: number;
    tax_total: number;
    discount_total: number;
    grand_total: number;
    payment_method: string;
    status: 'completed' | 'refunded' | 'voided';
    cashier_name: string;
    items: Array<{
        name: string;
        quantity: number;
        unit_price: number;
        total: number;
    }>;
}

const props = defineProps<{
    orders: OrderRecord[];
}>();

const selectedOrder = ref<OrderRecord | null>(null);
const showReceiptModal = ref(false);
const activeReceipt = ref<ReceiptData | null>(null);

const handleViewReceipt = (order: OrderRecord) => {
    activeReceipt.value = {
        receiptNumber: order.invoice_number,
        date: order.date,
        branchName: 'Main Store & Headquarters',
        companyName: 'SathiSaaS Sdn Bhd',
        taxNumber: 'W10-1808-32000123',
        address: 'Level 18, Pavilion Tower, Kuala Lumpur',
        phone: '+60 3-2148 8888',
        cashierName: order.cashier_name,
        terminalName: 'POS-01',
        customerName: order.customer_name,
        orderType: order.order_type,
        tableNumber: order.table_name,
        items: order.items.map((i) => ({
            name: i.name,
            quantity: i.quantity,
            unit_price: i.unit_price,
            total: i.total,
        })),
        subtotal: order.subtotal,
        discountTotal: order.discount_total,
        taxTotal: order.tax_total,
        grandTotal: order.grand_total,
        payments: [{ method_name: order.payment_method, amount: order.grand_total }],
        changeDue: 0,
        currencySymbol: tenantCurrency.value,
    };
    showReceiptModal.value = true;
};
</script>

<template>
    <Head title="POS Sales History - SathiSaaS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/pos" class="hover:text-emerald-600 transition">POS Register</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Orders</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>POS Sales & Transaction History</span>
                    </h1>
                </div>

                <div class="flex items-center space-x-3">
                    <Link
                        href="/admin/pos"
                        class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg transition flex items-center gap-2"
                    >
                        <span>Open POS Register</span>
                    </Link>
                </div>
            </div>

            <!-- Orders Table Card -->
            <Card class="overflow-hidden">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Recent POS Invoices</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Receipt / Invoice #</th>
                                <th class="px-4 py-3">Date & Time</th>
                                <th class="px-4 py-3">Customer & Channel</th>
                                <th class="px-4 py-3 text-center">Items</th>
                                <th class="px-4 py-3 text-right">Grand Total</th>
                                <th class="px-4 py-3 text-center">Payment</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="order in orders"
                                :key="order.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3 font-mono font-bold text-emerald-600">
                                    {{ order.invoice_number }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 font-mono">
                                    {{ order.date }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-zinc-800 dark:text-zinc-200">{{ order.customer_name }}</div>
                                    <div class="text-[10px] text-zinc-500 uppercase font-mono">
                                        {{ order.order_type }} {{ order.table_name ? `(${order.table_name})` : '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-bold text-zinc-700 dark:text-zinc-300">
                                    {{ order.items_count }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-extrabold text-zinc-900 dark:text-white">
                                    RM {{ order.grand_total.toFixed(2) }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300">
                                    {{ order.payment_method }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="order.status === 'completed' ? 'success' : order.status === 'refunded' ? 'warning' : 'danger'" class="uppercase text-[9px]">
                                        {{ order.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        @click="handleViewReceipt(order)"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-emerald-50 dark:hover:bg-zinc-700 text-zinc-700 hover:text-emerald-700 dark:text-zinc-300 transition cursor-pointer"
                                    >
                                        Reprint Receipt
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <PosReceiptModal
            :show="showReceiptModal"
            :receipt="activeReceipt"
            @close="showReceiptModal = false"
        />
    </OrganizationLayout>
</template>
