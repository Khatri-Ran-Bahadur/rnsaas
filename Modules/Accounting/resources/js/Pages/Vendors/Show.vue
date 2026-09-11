<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import { usePermissions } from '@/composables/usePermissions';

interface PayableAccount {
    id: number;
    code: string;
    name: string;
}

interface VendorData {
    id: number;
    vendor_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    status: {
        value: string;
        label: string;
    } | string;
    payable_account?: PayableAccount | null;
    payment_terms_days: number;
    credit_limit: string;
    billing_address_line_1: string | null;
    billing_address_line_2: string | null;
    billing_city: string | null;
    billing_state: string | null;
    billing_postcode: string | null;
    billing_country: string | null;
}

interface BillItem {
    id: number;
    bill_number: string;
    bill_date: string;
    due_date: string;
    total_amount: string;
    status: string;
}

interface PaymentItem {
    id: number;
    payment_number: string;
    payment_date: string;
    amount: string;
    payment_method: string;
    status: string;
}

interface Props {
    vendor: VendorData;
    balance: {
        balance: string;
        vendor_id: number;
        vendor_name: string;
    };
    recentBills: BillItem[];
    recentPayments: PaymentItem[];
}

const props = defineProps<Props>();

const { can, isAdmin } = usePermissions();
const canManage = isAdmin.value || can('accounting.manage') || can('accounting.vendors.manage');

const getStatusValue = (status: VendorData['status']) => {
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
</script>

<template>
    <OrganizationLayout
        :title="vendor.name"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Vendors', href: '/admin/accounting/vendors' },
            { label: vendor.name },
        ]"
    >
        <Head :title="`${vendor.name} - Vendors`" />

        <div class="w-full space-y-6">
            <!-- Header Banner -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white text-xl shadow-xs">
                        {{ vendor.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                {{ vendor.name }}
                            </h1>
                            <Badge :variant="getStatusValue(vendor.status) === 'active' ? 'success' : 'neutral'">
                                {{ getStatusValue(vendor.status) === 'active' ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            Vendor Code: <span class="font-mono font-medium text-zinc-700 dark:text-zinc-300">{{ vendor.vendor_code }}</span>
                            <span v-if="vendor.tax_number" class="ml-3">Tax ID: {{ vendor.tax_number }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Link :href="`/admin/accounting/reports/vendors/${vendor.id}/statement`">
                        <Button variant="secondary" size="sm">
                            <svg class="h-4 w-4 mr-1.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Statement
                        </Button>
                    </Link>

                    <Link v-if="canManage" :href="`/admin/accounting/vendors/${vendor.id}/edit`">
                        <Button variant="secondary" size="sm">
                            Edit
                        </Button>
                    </Link>

                    <Link v-if="canManage" href="/admin/accounting/purchase-bills/create">
                        <Button variant="primary" size="sm">
                            + New Bill
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Financial Metrics Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Current Outstanding Balance
                    </p>
                    <p class="mt-2 text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                        {{ formatCurrency(balance.balance) }}
                    </p>
                    <p class="mt-1 text-[11px] text-zinc-400">Accounts payable net balance</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Credit Limit
                    </p>
                    <p class="mt-2 text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                        {{ formatCurrency(vendor.credit_limit) }}
                    </p>
                    <p class="mt-1 text-[11px] text-zinc-400">Authorized supplier credit ceiling</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Payment Terms
                    </p>
                    <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ vendor.payment_terms_days ? `Net ${vendor.payment_terms_days} Days` : 'Immediate' }}
                    </p>
                    <p class="mt-1 text-[11px] text-zinc-400">Default due date calculation</p>
                </div>
            </div>

            <!-- Details & Contact -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Contact & Account Details -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        Vendor Information
                    </h2>

                    <dl class="mt-4 space-y-3.5 text-xs">
                        <div>
                            <dt class="font-medium text-zinc-400">Email</dt>
                            <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">{{ vendor.email || 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-400">Phone</dt>
                            <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200">{{ vendor.phone || 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-400">Accounts Payable Account</dt>
                            <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200 font-mono">
                                {{ vendor.payable_account ? `${vendor.payable_account.code} - ${vendor.payable_account.name}` : 'Default AP Control Account (2110)' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-400">Billing Address</dt>
                            <dd class="mt-0.5 text-zinc-800 dark:text-zinc-200 leading-relaxed">
                                <span v-if="vendor.billing_address_line_1">{{ vendor.billing_address_line_1 }}<br /></span>
                                <span v-if="vendor.billing_address_line_2">{{ vendor.billing_address_line_2 }}<br /></span>
                                <span>{{ [vendor.billing_city, vendor.billing_state, vendor.billing_postcode].filter(Boolean).join(', ') }}</span>
                                <span v-if="vendor.billing_country">, {{ vendor.billing_country }}</span>
                                <span v-if="!vendor.billing_address_line_1 && !vendor.billing_city" class="italic text-zinc-400">No address specified</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Recent Purchase Bills & Payments -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Recent Bills -->
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Recent Purchase Bills
                            </h2>
                            <Link href="/admin/accounting/purchase-bills" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                View all bills &rarr;
                            </Link>
                        </div>

                        <div v-if="recentBills.length > 0" class="mt-4 overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="text-[11px] font-semibold uppercase text-zinc-400">
                                    <tr>
                                        <th class="pb-2">Bill #</th>
                                        <th class="pb-2">Date</th>
                                        <th class="pb-2">Due Date</th>
                                        <th class="pb-2 text-right">Amount</th>
                                        <th class="pb-2 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    <tr v-for="bill in recentBills" :key="bill.id">
                                        <td class="py-2.5 font-mono font-medium text-indigo-600 dark:text-indigo-400">
                                            <Link :href="`/admin/accounting/purchase-bills/${bill.id}`" class="hover:underline">
                                                {{ bill.bill_number }}
                                            </Link>
                                        </td>
                                        <td class="py-2.5 text-zinc-600 dark:text-zinc-400">{{ bill.bill_date }}</td>
                                        <td class="py-2.5 text-zinc-600 dark:text-zinc-400">{{ bill.due_date }}</td>
                                        <td class="py-2.5 text-right font-mono text-zinc-900 dark:text-zinc-100 font-medium">
                                            {{ formatCurrency(bill.total_amount) }}
                                        </td>
                                        <td class="py-2.5 text-right">
                                            <span
                                                :class="[
                                                    'inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase',
                                                    bill.status === 'posted' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' :
                                                    bill.status === 'issued' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' :
                                                    bill.status === 'void' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' :
                                                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                ]"
                                            >
                                                {{ bill.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="mt-4 text-xs text-zinc-400 italic">No purchase bills recorded for this vendor yet.</p>
                    </div>

                    <!-- Recent Payments -->
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Recent Vendor Payments
                            </h2>
                            <Link href="/admin/accounting/vendor-payments" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                View all payments &rarr;
                            </Link>
                        </div>

                        <div v-if="recentPayments.length > 0" class="mt-4 overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="text-[11px] font-semibold uppercase text-zinc-400">
                                    <tr>
                                        <th class="pb-2">Payment #</th>
                                        <th class="pb-2">Date</th>
                                        <th class="pb-2">Method</th>
                                        <th class="pb-2 text-right">Amount</th>
                                        <th class="pb-2 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    <tr v-for="payment in recentPayments" :key="payment.id">
                                        <td class="py-2.5 font-mono font-medium text-indigo-600 dark:text-indigo-400">
                                            <Link :href="`/admin/accounting/vendor-payments/${payment.id}`" class="hover:underline">
                                                {{ payment.payment_number }}
                                            </Link>
                                        </td>
                                        <td class="py-2.5 text-zinc-600 dark:text-zinc-400">{{ payment.payment_date }}</td>
                                        <td class="py-2.5 text-zinc-600 dark:text-zinc-400 uppercase text-[10px] font-semibold">{{ payment.payment_method }}</td>
                                        <td class="py-2.5 text-right font-mono text-zinc-900 dark:text-zinc-100 font-medium">
                                            {{ formatCurrency(payment.amount) }}
                                        </td>
                                        <td class="py-2.5 text-right">
                                            <span
                                                :class="[
                                                    'inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase',
                                                    payment.status === 'posted' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' :
                                                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                ]"
                                            >
                                                {{ payment.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="mt-4 text-xs text-zinc-400 italic">No vendor payments recorded for this vendor yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
