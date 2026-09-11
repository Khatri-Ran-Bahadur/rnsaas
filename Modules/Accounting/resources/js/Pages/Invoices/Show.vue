<script setup lang="ts">
import { ref } from 'vue';
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
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    billing_address_line_1: string | null;
    billing_city: string | null;
    billing_country: string | null;
}

interface InvoiceLineItem {
    id: number;
    line_number: number;
    description: string;
    quantity: string | number;
    unit_price: string | number;
    discount_amount: string | number;
    tax_rate: string | number;
    tax_amount: string | number;
    subtotal: string | number;
    total: string | number;
    revenue_account?: {
        id: number;
        code: string;
        name: string;
    } | null;
}

interface PaymentAllocation {
    id: number;
    allocated_amount: string;
    customer_payment?: {
        id: number;
        public_id: string;
        payment_number: string;
        payment_date: string;
        status: string;
    } | null;
}

interface InvoiceDetail {
    id: number;
    public_id: string;
    invoice_number: string;
    invoice_date: string;
    due_date: string;
    currency: string;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    grand_total: string;
    status: string;
    reference: string | null;
    notes: string | null;
    customer?: CustomerInfo | null;
    lines: InvoiceLineItem[];
    allocations?: PaymentAllocation[];
    journal_entry_id?: number | null;
}

interface Props {
    invoice: InvoiceDetail;
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = isAdmin.value || can('accounting.manage') || can('accounting.invoices.manage');

const isProcessing = ref(false);

const issueInvoice = () => {
    if (!confirm('Are you sure you want to issue this sales invoice?')) return;
    isProcessing.value = true;
    router.post(
        `/admin/accounting/invoices/${props.invoice.public_id || props.invoice.id}/issue`,
        {},
        {
            onFinish: () => { isProcessing.value = false; },
        }
    );
};

const postInvoice = () => {
    if (!confirm('Are you sure you want to post this sales invoice to the General Ledger? This cannot be undone.')) return;
    isProcessing.value = true;
    router.post(
        `/admin/accounting/invoices/${props.invoice.public_id || props.invoice.id}/post`,
        {},
        {
            onFinish: () => { isProcessing.value = false; },
        }
    );
};

const voidInvoice = () => {
    if (!confirm('Are you sure you want to void this invoice?')) return;
    isProcessing.value = true;
    router.post(
        `/admin/accounting/invoices/${props.invoice.public_id || props.invoice.id}/void`,
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

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'posted':
        case 'paid':
            return 'success';
        case 'issued':
            return 'primary';
        case 'draft':
            return 'neutral';
        case 'void':
            return 'error';
        default:
            return 'warning';
    }
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number} - Accounting`" />

    <OrganizationLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Header & Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/accounting/invoices"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; Sales Invoices
                        </Link>
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ invoice.invoice_number }}
                        </h1>
                        <Badge :variant="getStatusVariant(invoice.status)">
                            {{ invoice.status }}
                        </Badge>
                    </div>
                </div>

                <div v-if="canManage" class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        onclick="window.print()"
                    >
                        <svg class="-ml-1 mr-1.5 inline-block h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </button>

                    <!-- Draft Actions -->
                    <template v-if="invoice.status === 'draft'">
                        <Link :href="`/admin/accounting/invoices/${invoice.public_id || invoice.id}/edit`">
                            <Button variant="outline">Edit</Button>
                        </Link>
                        <Button :loading="isProcessing" @click="issueInvoice">
                            Issue Invoice
                        </Button>
                    </template>

                    <!-- Issued Actions -->
                    <template v-if="invoice.status === 'issued'">
                        <Button variant="outline" class="text-rose-600 hover:bg-rose-50" :loading="isProcessing" @click="voidInvoice">
                            Void
                        </Button>
                        <Button :loading="isProcessing" @click="postInvoice">
                            Post to Ledger
                        </Button>
                    </template>

                    <!-- Posted Actions -->
                    <template v-if="invoice.status === 'posted'">
                        <Link :href="`/admin/accounting/payments/create?customer_id=${invoice.customer?.id}`">
                            <Button>
                                Record Payment
                            </Button>
                        </Link>
                    </template>
                </div>
            </div>

            <!-- Invoice Paper Document -->
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <!-- Document Top: Organization & Customer Info -->
                <div class="flex flex-col justify-between gap-6 border-b border-slate-200 pb-8 sm:flex-row dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Bill To:</span>
                        <div v-if="invoice.customer" class="mt-2 space-y-1">
                            <div class="text-lg font-bold text-slate-900 dark:text-white">
                                <Link :href="`/admin/accounting/customers/${invoice.customer.public_id || invoice.customer.id}`" class="hover:underline">
                                    {{ invoice.customer.name }}
                                </Link>
                            </div>
                            <div class="font-mono text-xs text-slate-500 dark:text-zinc-400">{{ invoice.customer.customer_code }}</div>
                            <div v-if="invoice.customer.billing_address_line_1" class="text-sm text-slate-600 dark:text-zinc-400">
                                {{ invoice.customer.billing_address_line_1 }}
                            </div>
                            <div v-if="invoice.customer.billing_city" class="text-sm text-slate-600 dark:text-zinc-400">
                                {{ invoice.customer.billing_city }}, {{ invoice.customer.billing_country }}
                            </div>
                            <div v-if="invoice.customer.tax_number" class="text-xs text-slate-500 dark:text-zinc-400">
                                Tax ID: {{ invoice.customer.tax_number }}
                            </div>
                        </div>
                    </div>

                    <div class="sm:text-right">
                        <div class="text-3xl font-extrabold uppercase tracking-tight text-slate-900 dark:text-white">
                            Invoice
                        </div>
                        <div class="mt-2 space-y-1 text-sm text-slate-600 dark:text-zinc-400">
                            <div><span class="font-semibold text-slate-900 dark:text-white">Invoice #:</span> {{ invoice.invoice_number }}</div>
                            <div><span class="font-semibold text-slate-900 dark:text-white">Date:</span> {{ invoice.invoice_date }}</div>
                            <div><span class="font-semibold text-slate-900 dark:text-white">Due Date:</span> {{ invoice.due_date }}</div>
                            <div v-if="invoice.reference"><span class="font-semibold text-slate-900 dark:text-white">Ref:</span> {{ invoice.reference }}</div>
                            <div v-if="invoice.journal_entry_id">
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">GL Entry:</span> #{{ invoice.journal_entry_id }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="mt-8 overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-zinc-400">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 w-12 text-center">#</th>
                                <th scope="col" class="px-4 py-3">Description</th>
                                <th scope="col" class="px-4 py-3">Account</th>
                                <th scope="col" class="px-4 py-3 text-right w-20">Qty</th>
                                <th scope="col" class="px-4 py-3 text-right w-28">Unit Price</th>
                                <th scope="col" class="px-4 py-3 text-right w-20">Tax</th>
                                <th scope="col" class="px-4 py-3 text-right w-24">Discount</th>
                                <th scope="col" class="px-4 py-3 text-right w-32">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                            <tr v-for="line in invoice.lines" :key="line.id">
                                <td class="px-4 py-3 text-center text-xs font-mono text-slate-400">{{ line.line_number }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ line.description }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <span v-if="line.revenue_account" class="font-mono text-indigo-600 dark:text-indigo-400">
                                        {{ line.revenue_account.code }} - {{ line.revenue_account.name }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3 text-right">{{ line.quantity }}</td>
                                <td class="px-4 py-3 text-right">${{ formatMoney(line.unit_price) }}</td>
                                <td class="px-4 py-3 text-right">${{ formatMoney(line.tax_amount) }}</td>
                                <td class="px-4 py-3 text-right">${{ formatMoney(line.discount_amount) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900 dark:text-white">
                                    ${{ formatMoney(line.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Summary -->
                <div class="mt-8 flex flex-col items-end border-t border-slate-200 pt-6 dark:border-zinc-800">
                    <div class="w-80 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-600 dark:text-zinc-400">
                            <span>Subtotal:</span>
                            <span class="font-medium text-slate-900 dark:text-white">${{ formatMoney(invoice.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600 dark:text-zinc-400">
                            <span>Discount Total:</span>
                            <span class="font-medium text-slate-900 dark:text-white">-${{ formatMoney(invoice.discount_total) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600 dark:text-zinc-400">
                            <span>Tax Total:</span>
                            <span class="font-medium text-slate-900 dark:text-white">+${{ formatMoney(invoice.tax_total) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-3 text-lg font-bold text-slate-900 dark:border-zinc-800 dark:text-white">
                            <span>Grand Total:</span>
                            <span class="text-indigo-600 dark:text-indigo-400">${{ formatMoney(invoice.grand_total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes / Remarks -->
                <div v-if="invoice.notes" class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Notes & Payment Instructions</span>
                    <p class="mt-1 text-sm text-slate-700 whitespace-pre-wrap dark:text-zinc-300">{{ invoice.notes }}</p>
                </div>

                <!-- Allocations (Payment receipts against this invoice) -->
                <div v-if="invoice.allocations && invoice.allocations.length > 0" class="mt-8 border-t border-slate-200 pt-6 dark:border-zinc-800">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                        Payment Receipts Applied
                    </h3>
                    <div class="mt-3 space-y-2">
                        <div
                            v-for="alloc in invoice.allocations"
                            :key="alloc.id"
                            class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-zinc-800 dark:bg-zinc-800/40"
                        >
                            <div>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    {{ alloc.customer_payment?.payment_number || 'Receipt' }}
                                </span>
                                <span class="ml-2 text-xs text-slate-400 dark:text-zinc-500">
                                    on {{ alloc.customer_payment?.payment_date }}
                                </span>
                            </div>
                            <div class="font-bold text-emerald-600 dark:text-emerald-400">
                                -${{ formatMoney(alloc.allocated_amount) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
