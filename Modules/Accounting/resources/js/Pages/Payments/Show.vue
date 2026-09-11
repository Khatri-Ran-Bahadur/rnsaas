<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import { usePermissions } from '@/composables/usePermissions';

interface CustomerInfo {
    id: number;
    public_id: string;
    customer_code: string;
    name: string;
}

interface BankAccountInfo {
    id: number;
    code: string;
    name: string;
}

interface InvoiceAllocation {
    id: number;
    allocated_amount: string;
    invoice?: {
        id: number;
        public_id: string;
        invoice_number: string;
        due_date: string;
        grand_total: string;
        status: string;
    } | null;
}

interface PaymentDetail {
    id: number;
    public_id: string;
    payment_number: string;
    payment_date: string;
    amount: string;
    currency: string;
    payment_method: string;
    reference: string | null;
    notes: string | null;
    status: string;
    customer?: CustomerInfo | null;
    bank_account?: BankAccountInfo | null;
    allocations: InvoiceAllocation[];
    journal_entry_id?: number | null;
}

interface OpenInvoice {
    id: number;
    public_id: string;
    invoice_number: string;
    due_date: string;
    grand_total: string;
}

interface Props {
    payment: PaymentDetail;
    openInvoices: OpenInvoice[];
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = isAdmin.value || can('accounting.manage') || can('accounting.payments.manage');

const isProcessing = ref(false);

// Allocation inputs for open invoices
const allocationInputs = ref<Record<number, string>>({});

// Initialize allocation inputs if already allocated
props.payment.allocations.forEach(alloc => {
    if (alloc.invoice?.id) {
        allocationInputs.value[alloc.invoice.id] = alloc.allocated_amount;
    }
});

const totalAllocated = computed(() => {
    return Object.values(allocationInputs.value)
        .reduce((sum, val) => sum + (Number(val) || 0), 0)
        .toFixed(2);
});

const unallocatedAmount = computed(() => {
    const total = Number(props.payment.amount || 0);
    const allocated = Number(totalAllocated.value);
    return Math.max(0, total - allocated).toFixed(2);
});

const saveAllocations = () => {
    const allocationsArray = Object.entries(allocationInputs.value)
        .filter(([_, amt]) => Number(amt) > 0)
        .map(([invId, amt]) => ({
            invoice_id: Number(invId),
            amount: amt,
        }));

    if (allocationsArray.length === 0) {
        alert('Please specify an allocated amount for at least one invoice.');
        return;
    }

    isProcessing.value = true;
    router.post(
        `/admin/accounting/payments/${props.payment.public_id || props.payment.id}/allocate`,
        { allocations: allocationsArray },
        {
            onFinish: () => { isProcessing.value = false; },
        }
    );
};

const postPayment = () => {
    if (!confirm('Are you sure you want to post this customer payment to the General Ledger? This cannot be undone.')) return;
    isProcessing.value = true;
    router.post(
        `/admin/accounting/payments/${props.payment.public_id || props.payment.id}/post`,
        {},
        {
            onFinish: () => { isProcessing.value = false; },
        }
    );
};

const formatMoney = (val: string | number | undefined) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head :title="`Receipt ${payment.payment_number} - Accounting`" />

    <OrganizationLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/accounting/payments"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; Customer Receipts
                        </Link>
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ payment.payment_number }}
                        </h1>
                        <Badge :variant="payment.status === 'posted' ? 'success' : 'neutral'">
                            {{ payment.status }}
                        </Badge>
                    </div>
                </div>

                <div v-if="canManage" class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        onclick="window.print()"
                    >
                        Print Receipt
                    </button>

                    <Button
                        v-if="payment.status === 'draft'"
                        :loading="isProcessing"
                        @click="postPayment"
                    >
                        Post to Ledger
                    </Button>
                </div>
            </div>

            <!-- Receipt Overview Card -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-6 sm:flex-row dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Customer</span>
                        <div v-if="payment.customer" class="mt-1">
                            <Link
                                :href="`/admin/accounting/customers/${payment.customer.public_id || payment.customer.id}`"
                                class="text-lg font-bold text-slate-900 hover:underline dark:text-white"
                            >
                                {{ payment.customer.name }}
                            </Link>
                            <div class="font-mono text-xs text-slate-500 dark:text-zinc-400">{{ payment.customer.customer_code }}</div>
                        </div>
                    </div>

                    <div class="sm:text-right">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Amount Received</span>
                        <div class="mt-1 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                            ${{ formatMoney(payment.amount) }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-zinc-400">Date: {{ payment.payment_date }}</div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                    <div>
                        <span class="text-xs text-slate-400 dark:text-zinc-500">Deposit Account</span>
                        <div v-if="payment.bank_account" class="font-medium text-slate-900 dark:text-white">
                            <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ payment.bank_account.code }}</span>
                            - {{ payment.bank_account.name }}
                        </div>
                        <div v-else class="text-slate-400 dark:text-zinc-500">—</div>
                    </div>

                    <div>
                        <span class="text-xs text-slate-400 dark:text-zinc-500">Payment Method</span>
                        <div class="font-medium capitalize text-slate-900 dark:text-white">
                            {{ payment.payment_method.replace('_', ' ') }}
                        </div>
                    </div>

                    <div>
                        <span class="text-xs text-slate-400 dark:text-zinc-500">Reference / TXN</span>
                        <div class="font-medium text-slate-900 dark:text-white">
                            {{ payment.reference || '—' }}
                        </div>
                    </div>
                </div>

                <div v-if="payment.notes" class="mt-4 border-t border-slate-200 pt-4 text-xs text-slate-600 dark:border-zinc-800 dark:text-zinc-400">
                    <span class="font-semibold">Notes:</span> {{ payment.notes }}
                </div>
            </div>

            <!-- Bill Allocations Section -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Sales Invoice Allocations</h2>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">
                            Apply this payment amount towards specific open customer invoices.
                        </p>
                    </div>

                    <div class="flex items-center gap-4 text-sm font-semibold">
                        <div>
                            <span class="text-xs font-normal text-slate-400">Allocated:</span>
                            <span class="ml-1 text-emerald-600 dark:text-emerald-400">${{ totalAllocated }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-normal text-slate-400">Unallocated:</span>
                            <span class="ml-1" :class="Number(unallocatedAmount) > 0 ? 'text-amber-500' : 'text-slate-500'">
                                ${{ unallocatedAmount }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- If Draft: Interactive Allocation Table -->
                <div v-if="payment.status === 'draft'" class="mt-4">
                    <div v-if="openInvoices.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-zinc-400">
                            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-4 py-2.5">Invoice #</th>
                                    <th scope="col" class="px-4 py-2.5">Due Date</th>
                                    <th scope="col" class="px-4 py-2.5 text-right">Invoice Amount</th>
                                    <th scope="col" class="px-4 py-2.5 text-right w-44">Allocated Amount ($)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                                <tr v-for="inv in openInvoices" :key="inv.id">
                                    <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                        <Link :href="`/admin/accounting/invoices/${inv.public_id || inv.id}`" class="hover:underline">
                                            {{ inv.invoice_number }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ inv.due_date }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-900 dark:text-white">
                                        ${{ formatMoney(inv.grand_total) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input
                                            v-model="allocationInputs[inv.id]"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :max="Number(inv.grand_total)"
                                            placeholder="0.00"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-right text-sm text-slate-900 focus:border-indigo-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mt-4 flex justify-end">
                            <Button size="sm" :loading="isProcessing" @click="saveAllocations">
                                Save Allocations
                            </Button>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm text-slate-400 dark:text-zinc-500">
                        No open invoices available for this customer.
                    </div>
                </div>

                <!-- If Posted: Read-only Allocations -->
                <div v-else class="mt-4">
                    <div v-if="payment.allocations.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-zinc-400">
                            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                <tr>
                                    <th class="px-4 py-2.5">Invoice #</th>
                                    <th class="px-4 py-2.5">Due Date</th>
                                    <th class="px-4 py-2.5 text-right">Allocated Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                                <tr v-for="alloc in payment.allocations" :key="alloc.id">
                                    <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                        {{ alloc.invoice?.invoice_number || 'Invoice' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ alloc.invoice?.due_date }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                        ${{ formatMoney(alloc.allocated_amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="py-6 text-center text-sm text-slate-400 dark:text-zinc-500">
                        Unallocated payment (kept as customer credit on account).
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
