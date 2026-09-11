<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import { usePermissions } from '@/composables/usePermissions';

interface AccountRef {
    id: number;
    code: string;
    name: string;
}

interface VendorRef {
    id: number;
    vendor_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    billing_address_line_1: string | null;
    billing_city: string | null;
    billing_state: string | null;
    billing_country: string | null;
}

interface LineItem {
    id: number;
    line_number: number;
    description: string;
    quantity: string;
    unit_price: string;
    discount_amount: string;
    tax_rate: string;
    tax_amount: string;
    subtotal: string;
    total: string;
    debit_account?: AccountRef | null;
    tax_account?: AccountRef | null;
}

interface JournalLine {
    id: number;
    debit: string;
    credit: string;
    account?: AccountRef | null;
}

interface JournalEntryRef {
    id: number;
    entry_number: string;
    entry_date: string;
    lines?: JournalLine[];
}

interface BillData {
    id: number;
    bill_number: string;
    bill_date: string;
    due_date: string;
    currency: string;
    subtotal: string;
    tax_amount: string;
    discount_amount: string;
    total_amount: string;
    reference: string | null;
    notes: string | null;
    status: {
        value: string;
        label: string;
    } | string;
    vendor?: VendorRef | null;
    lines?: LineItem[];
    journal_entry?: JournalEntryRef | null;
}

interface AllocationItem {
    id: number;
    allocated_amount: string;
    allocated_at: string;
    payment?: {
        id: number;
        payment_number: string;
        payment_date: string;
        payment_method: string;
    } | null;
}

interface Props {
    bill: BillData;
    allocations: AllocationItem[];
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = ref(isAdmin.value || can('accounting.manage') || can('accounting.bills.manage'));

const isActionRunning = ref(false);

const getStatusValue = (status: BillData['status']) => {
    if (typeof status === 'object' && status !== null) {
        return status.value;
    }
    return status;
};

const formatCurrency = (val: string | number) => {
    const num = parseFloat(String(val) || '0');
    return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const issueBill = () => {
    if (isActionRunning.value) return;
    isActionRunning.value = true;
    router.post(
        `/admin/accounting/purchase-bills/${props.bill.id}/issue`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isActionRunning.value = false;
            },
        }
    );
};

const postBill = () => {
    if (isActionRunning.value) return;
    if (!confirm('Are you sure you want to post this purchase bill to the General Ledger? This action is idempotent.')) {
        return;
    }
    isActionRunning.value = true;
    router.post(
        `/admin/accounting/purchase-bills/${props.bill.id}/post`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isActionRunning.value = false;
            },
        }
    );
};

const voidBill = () => {
    if (isActionRunning.value) return;
    if (!confirm('Are you sure you want to void this purchase bill?')) {
        return;
    }
    isActionRunning.value = true;
    router.post(
        `/admin/accounting/purchase-bills/${props.bill.id}/void`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isActionRunning.value = false;
            },
        }
    );
};
</script>

<template>
    <OrganizationLayout
        :title="bill.bill_number"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Purchase Bills', href: '/admin/accounting/purchase-bills' },
            { label: bill.bill_number },
        ]"
    >
        <Head :title="`${bill.bill_number} - Purchase Bills`" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Action & Status Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <span
                        :class="[
                            'inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                            getStatusValue(bill.status) === 'posted' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' :
                            getStatusValue(bill.status) === 'issued' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' :
                            getStatusValue(bill.status) === 'void' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800' :
                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-700'
                        ]"
                    >
                        Status: {{ getStatusValue(bill.status) }}
                    </span>

                    <span v-if="bill.reference" class="text-xs text-zinc-500">
                        Ref: <strong class="font-mono text-zinc-700 dark:text-zinc-300">{{ bill.reference }}</strong>
                    </span>
                </div>

                <div v-if="canManage" class="flex flex-wrap items-center gap-2.5">
                    <!-- Draft Actions -->
                    <template v-if="getStatusValue(bill.status) === 'draft'">
                        <Link :href="`/admin/accounting/purchase-bills/${bill.id}/edit`">
                            <Button variant="secondary" size="sm">
                                Edit Bill
                            </Button>
                        </Link>
                        <Button variant="primary" size="sm" :disabled="isActionRunning" @click="issueBill">
                            {{ isActionRunning ? 'Issuing...' : 'Issue Bill' }}
                        </Button>
                    </template>

                    <!-- Issued Actions -->
                    <template v-if="getStatusValue(bill.status) === 'issued'">
                        <Button variant="secondary" size="sm" :disabled="isActionRunning" @click="voidBill">
                            Void Bill
                        </Button>
                        <Button variant="primary" size="sm" :disabled="isActionRunning" @click="postBill">
                            {{ isActionRunning ? 'Posting...' : 'Post to Ledger' }}
                        </Button>
                    </template>

                    <!-- Posted Status Indicator -->
                    <template v-if="getStatusValue(bill.status) === 'posted'">
                        <span class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Posted to General Ledger
                        </span>
                    </template>
                </div>
            </div>

            <!-- Bill Paper Invoice Layout -->
            <div class="rounded-xl border border-zinc-200 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <!-- Header -->
                <div class="flex flex-col gap-6 sm:flex-row sm:justify-between border-b border-zinc-200 pb-8 dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Purchase Bill</span>
                        <h2 class="mt-1 font-mono text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ bill.bill_number }}
                        </h2>

                        <div class="mt-4 text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                            <p><span class="font-medium text-zinc-400">Bill Date:</span> {{ bill.bill_date }}</p>
                            <p><span class="font-medium text-zinc-400">Due Date:</span> {{ bill.due_date }}</p>
                            <p v-if="bill.reference"><span class="font-medium text-zinc-400">Reference:</span> {{ bill.reference }}</p>
                        </div>
                    </div>

                    <div class="text-left sm:text-right">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Vendor / Supplier</span>
                        <h3 class="mt-1 text-base font-bold text-zinc-900 dark:text-zinc-100">
                            {{ bill.vendor?.name }}
                        </h3>
                        <p class="font-mono text-xs text-zinc-500">{{ bill.vendor?.vendor_code }}</p>
                        <div class="mt-2 text-xs text-zinc-500 leading-relaxed">
                            <p v-if="bill.vendor?.email">{{ bill.vendor.email }}</p>
                            <p v-if="bill.vendor?.phone">{{ bill.vendor.phone }}</p>
                            <p v-if="bill.vendor?.tax_number">Tax ID: {{ bill.vendor.tax_number }}</p>
                            <p v-if="bill.vendor?.billing_address_line_1">{{ bill.vendor.billing_address_line_1 }}</p>
                            <p>{{ [bill.vendor?.billing_city, bill.vendor?.billing_state, bill.vendor?.billing_country].filter(Boolean).join(', ') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Lines Table -->
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-zinc-200 text-[11px] font-semibold uppercase text-zinc-400 dark:border-zinc-800">
                            <tr>
                                <th class="w-8 pb-3">#</th>
                                <th class="pb-3">Description</th>
                                <th class="pb-3">Debit Account</th>
                                <th class="pb-3 text-right">Qty</th>
                                <th class="pb-3 text-right">Unit Price</th>
                                <th class="pb-3 text-right">Disc</th>
                                <th class="pb-3 text-right">Tax</th>
                                <th class="pb-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="line in bill.lines" :key="line.id">
                                <td class="py-3 text-zinc-400 font-mono">{{ line.line_number }}</td>
                                <td class="py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ line.description }}</td>
                                <td class="py-3 text-zinc-500 font-mono">
                                    {{ line.debit_account ? `${line.debit_account.code} - ${line.debit_account.name}` : '-' }}
                                </td>
                                <td class="py-3 text-right font-mono">{{ line.quantity }}</td>
                                <td class="py-3 text-right font-mono">{{ formatCurrency(line.unit_price) }}</td>
                                <td class="py-3 text-right font-mono text-rose-600">
                                    {{ Number(line.discount_amount) > 0 ? `-${formatCurrency(line.discount_amount)}` : '-' }}
                                </td>
                                <td class="py-3 text-right font-mono">
                                    {{ Number(line.tax_rate) > 0 ? `${line.tax_rate}% (${formatCurrency(line.tax_amount)})` : '-' }}
                                </td>
                                <td class="py-3 text-right font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(line.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Breakdown -->
                <div class="mt-8 flex justify-end border-t border-zinc-200 pt-6 dark:border-zinc-800">
                    <div class="w-full sm:w-72 space-y-2 text-xs">
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Subtotal</span>
                            <span class="font-mono">{{ formatCurrency(bill.subtotal) }}</span>
                        </div>
                        <div v-if="Number(bill.discount_amount) > 0" class="flex justify-between text-rose-600">
                            <span>Discount</span>
                            <span class="font-mono">-{{ formatCurrency(bill.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>Total Tax</span>
                            <span class="font-mono">{{ formatCurrency(bill.tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-bold text-zinc-900 dark:border-zinc-800 dark:text-zinc-100">
                            <span>Grand Total ({{ bill.currency }})</span>
                            <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ formatCurrency(bill.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div v-if="bill.notes" class="mt-8 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <h4 class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Memo / Notes</h4>
                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400 whitespace-pre-line">{{ bill.notes }}</p>
                </div>
            </div>

            <!-- Payment Allocations Section -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                    Payment Allocations & Settlements
                </h3>

                <div v-if="allocations && allocations.length > 0" class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-zinc-200 text-[11px] font-semibold uppercase text-zinc-400 dark:border-zinc-800">
                            <tr>
                                <th class="pb-2">Payment #</th>
                                <th class="pb-2">Payment Date</th>
                                <th class="pb-2">Method</th>
                                <th class="pb-2 text-right">Allocated Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <tr v-for="alloc in allocations" :key="alloc.id">
                                <td class="py-2.5 font-mono font-medium text-indigo-600 dark:text-indigo-400">
                                    <Link v-if="alloc.payment" :href="`/admin/accounting/vendor-payments/${alloc.payment.id}`" class="hover:underline">
                                        {{ alloc.payment.payment_number }}
                                    </Link>
                                    <span v-else>-</span>
                                </td>
                                <td class="py-2.5 text-zinc-600 dark:text-zinc-400">{{ alloc.payment?.payment_date || alloc.allocated_at }}</td>
                                <td class="py-2.5 text-zinc-600 dark:text-zinc-400 uppercase text-[10px] font-semibold">{{ alloc.payment?.payment_method || '-' }}</td>
                                <td class="py-2.5 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(alloc.allocated_amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="mt-4 text-xs text-zinc-400 italic">No vendor payments have been allocated to this purchase bill yet.</p>
            </div>
        </div>
    </OrganizationLayout>
</template>
