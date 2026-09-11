<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import { usePermissions } from '@/composables/usePermissions';

interface CustomerDetail {
    id: number;
    public_id: string;
    customer_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    status: string;
    credit_limit: string;
    payment_terms_days: number;
    billing_address_line_1: string | null;
    billing_address_line_2: string | null;
    billing_city: string | null;
    billing_state: string | null;
    billing_postcode: string | null;
    billing_country: string | null;
    receivable_account?: {
        id: number;
        code: string;
        name: string;
    } | null;
}

interface InvoiceSummary {
    id: number;
    public_id: string;
    invoice_number: string;
    invoice_date: string;
    due_date: string;
    grand_total: string;
    status: string;
}

interface Props {
    customer: CustomerDetail;
    balance: {
        balance: string;
    };
    invoices: InvoiceSummary[];
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = isAdmin.value || can('accounting.manage') || can('accounting.customers.manage');

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
    <Head :title="`${customer.name} - Customers`" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            href="/admin/accounting/customers"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; Customers
                        </Link>
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ customer.name }}
                        </h1>
                        <Badge :variant="customer.status === 'active' ? 'success' : 'neutral'">
                            {{ customer.status === 'active' ? 'Active' : 'Inactive' }}
                        </Badge>
                        <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600 dark:bg-zinc-800 dark:text-zinc-400">
                            {{ customer.customer_code }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="`/admin/accounting/reports/customers/${customer.public_id || customer.id}/statement`">
                        <Button variant="outline">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Statement
                        </Button>
                    </Link>

                    <Link
                        v-if="canManage"
                        :href="`/admin/accounting/invoices/create?customer_id=${customer.id}`"
                    >
                        <Button variant="outline">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Invoice
                        </Button>
                    </Link>

                    <Link
                        v-if="canManage"
                        :href="`/admin/accounting/payments/create?customer_id=${customer.id}`"
                    >
                        <Button variant="outline">
                            <svg class="-ml-1 mr-2 h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Record Receipt
                        </Button>
                    </Link>

                    <Link
                        v-if="canManage"
                        :href="`/admin/accounting/customers/${customer.public_id || customer.id}/edit`"
                    >
                        <Button>
                            Edit Customer
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Profile Summary Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left 2 Cols: Financial & Recent Invoices -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Metrics Card -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Current Balance</span>
                            <div class="mt-2 text-2xl font-bold" :class="Number(balance.balance) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'">
                                ${{ formatMoney(balance.balance) }}
                            </div>
                            <span class="text-xs text-slate-400 dark:text-zinc-500">Outstanding Receivable</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Credit Limit</span>
                            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                                ${{ formatMoney(customer.credit_limit) }}
                            </div>
                            <span class="text-xs text-slate-400 dark:text-zinc-500">Maximum Allowed Credit</span>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Payment Terms</span>
                            <div class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ customer.payment_terms_days }} Days
                            </div>
                            <span class="text-xs text-slate-400 dark:text-zinc-500">Net Due Interval</span>
                        </div>
                    </div>

                    <!-- Recent Invoices Table -->
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-zinc-800">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Sales Invoices</h2>
                            <Link
                                :href="`/admin/accounting/invoices?customer_id=${customer.id}`"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                View All &rarr;
                            </Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600 dark:text-zinc-400">
                                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                    <tr>
                                        <th scope="col" class="px-5 py-3">Invoice #</th>
                                        <th scope="col" class="px-5 py-3">Date</th>
                                        <th scope="col" class="px-5 py-3">Due Date</th>
                                        <th scope="col" class="px-5 py-3 text-right">Amount</th>
                                        <th scope="col" class="px-5 py-3 text-center">Status</th>
                                        <th scope="col" class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-zinc-800">
                                    <tr
                                        v-for="inv in invoices"
                                        :key="inv.id"
                                        class="transition-colors hover:bg-slate-50/70 dark:hover:bg-zinc-800/40"
                                    >
                                        <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white">
                                            <Link :href="`/admin/accounting/invoices/${inv.public_id || inv.id}`" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ inv.invoice_number }}
                                            </Link>
                                        </td>
                                        <td class="px-5 py-3 text-xs">{{ inv.invoice_date }}</td>
                                        <td class="px-5 py-3 text-xs">{{ inv.due_date }}</td>
                                        <td class="px-5 py-3 text-right font-medium text-slate-900 dark:text-white">
                                            ${{ formatMoney(inv.grand_total) }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <Badge :variant="getStatusVariant(inv.status)">
                                                {{ inv.status }}
                                            </Badge>
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <Link
                                                :href="`/admin/accounting/invoices/${inv.public_id || inv.id}`"
                                                class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="invoices.length === 0">
                                        <td colspan="6" class="py-8 text-center text-sm text-slate-400 dark:text-zinc-500">
                                            No sales invoices recorded yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Col: Contact & Billing Details -->
                <div class="space-y-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Contact Information</h2>
                        <div class="mt-4 space-y-3 text-sm">
                            <div>
                                <span class="text-xs text-slate-400 dark:text-zinc-500">Email</span>
                                <div class="font-medium text-slate-900 dark:text-white">{{ customer.email || '—' }}</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 dark:text-zinc-500">Phone</span>
                                <div class="font-medium text-slate-900 dark:text-white">{{ customer.phone || '—' }}</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 dark:text-zinc-500">Tax / VAT Number</span>
                                <div class="font-medium text-slate-900 dark:text-white">{{ customer.tax_number || '—' }}</div>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 dark:text-zinc-500">Receivable Account</span>
                                <div v-if="customer.receivable_account" class="font-medium text-slate-900 dark:text-white">
                                    <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ customer.receivable_account.code }}</span>
                                    - {{ customer.receivable_account.name }}
                                </div>
                                <div v-else class="text-slate-400 dark:text-zinc-500">Default Accounts Receivable</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Billing Address</h2>
                        <div class="mt-3 text-sm leading-relaxed text-slate-700 dark:text-zinc-300">
                            <div v-if="customer.billing_address_line_1">{{ customer.billing_address_line_1 }}</div>
                            <div v-if="customer.billing_address_line_2">{{ customer.billing_address_line_2 }}</div>
                            <div>
                                <span v-if="customer.billing_city">{{ customer.billing_city }}, </span>
                                <span v-if="customer.billing_state">{{ customer.billing_state }} </span>
                                <span v-if="customer.billing_postcode">{{ customer.billing_postcode }}</span>
                            </div>
                            <div v-if="customer.billing_country" class="font-medium text-slate-900 dark:text-white">
                                {{ customer.billing_country }}
                            </div>
                            <div v-if="!customer.billing_address_line_1 && !customer.billing_city" class="text-slate-400 dark:text-zinc-500">
                                No billing address provided.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
