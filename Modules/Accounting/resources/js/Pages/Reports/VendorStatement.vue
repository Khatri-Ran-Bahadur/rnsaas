<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, DatePicker } from '@/components';

interface VendorRef {
    id: number;
    vendor_code: string;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
}

interface StatementLedgerRow {
    date: string;
    type: string;
    reference: string;
    description: string;
    debit: string;
    credit: string;
    running_balance: string;
}

interface StatementData {
    vendor_id: number;
    vendor_name: string;
    from_date: string;
    to_date: string;
    opening_balance: string;
    total_invoiced: string;
    total_paid: string;
    closing_balance: string;
    ledger: StatementLedgerRow[];
}

interface Props {
    vendor: VendorRef;
    statement: StatementData;
    fromDate: string;
    toDate: string;
}

const props = defineProps<Props>();

const from = ref(props.fromDate);
const to = ref(props.toDate);

const applyDateRange = () => {
    router.get(
        `/admin/accounting/reports/vendors/${props.vendor.id}/statement`,
        {
            from_date: from.value,
            to_date: to.value,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const formatCurrency = (val: string | number) => {
    const num = parseFloat(String(val) || '0');
    return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const printStatement = () => {
    window.print();
};
</script>

<template>
    <OrganizationLayout
        :title="`Statement - ${vendor.name}`"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Accounting', href: '/admin/accounting/vendors' },
            { label: 'Reports' },
            { label: 'Vendor Statement' },
        ]"
    >
        <Head :title="`Vendor Statement - ${vendor.name}`" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Filter & Print Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Vendor Statement: {{ vendor.name }}
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Statement Period: {{ statement.from_date }} &mdash; {{ statement.to_date }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="flex items-center gap-1.5 text-xs text-zinc-500">
                        <div class="w-36">
                            <DatePicker v-model="from" placeholder="From date" />
                        </div>
                    </div>
                    <span class="text-xs text-zinc-400">to</span>
                    <div class="flex items-center gap-1.5 text-xs text-zinc-500">
                        <div class="w-36">
                            <DatePicker v-model="to" placeholder="To date" />
                        </div>
                    </div>
                    <Button variant="secondary" size="sm" @click="applyDateRange">
                        Apply
                    </Button>
                    <Button variant="secondary" size="sm" @click="printStatement">
                        <svg class="h-4 w-4 mr-1 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </Button>
                </div>
            </div>

            <!-- Statement Summary Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-zinc-500">Opening Balance</p>
                    <p class="mt-1 text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100">{{ formatCurrency(statement.opening_balance) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Total Invoiced</p>
                    <p class="mt-1 text-xl font-bold font-mono text-indigo-600 dark:text-indigo-400">+{{ formatCurrency(statement.total_invoiced) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Payments</p>
                    <p class="mt-1 text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">-{{ formatCurrency(statement.total_paid) }}</p>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">Closing Balance</p>
                    <p class="mt-1 text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100">{{ formatCurrency(statement.closing_balance) }}</p>
                </div>
            </div>

            <!-- Statement Ledger Table -->
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Transaction Activity
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                            <tr>
                                <th class="px-5 py-3.5">Date</th>
                                <th class="px-3 py-3.5">Type</th>
                                <th class="px-4 py-3.5">Reference #</th>
                                <th class="px-4 py-3.5">Description</th>
                                <th class="px-4 py-3.5 text-right">Debit</th>
                                <th class="px-4 py-3.5 text-right">Credit</th>
                                <th class="px-5 py-3.5 text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <!-- Opening Balance Row -->
                            <tr class="bg-zinc-50/50 font-medium dark:bg-zinc-800/30">
                                <td class="px-5 py-3 text-zinc-500">{{ statement.from_date }}</td>
                                <td class="px-3 py-3 uppercase text-[10px] text-zinc-400">OPENING</td>
                                <td class="px-4 py-3 text-zinc-400">-</td>
                                <td class="px-4 py-3 text-zinc-500 italic">Opening Balance as of {{ statement.from_date }}</td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-400">-</td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-400">-</td>
                                <td class="px-5 py-3 text-right font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(statement.opening_balance) }}
                                </td>
                            </tr>

                            <!-- Ledger Transactions -->
                            <tr
                                v-for="(row, idx) in statement.ledger"
                                :key="idx"
                                class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/50"
                            >
                                <td class="px-5 py-3 text-zinc-600 dark:text-zinc-400">{{ row.date }}</td>
                                <td class="px-3 py-3">
                                    <span
                                        :class="[
                                            'inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase',
                                            row.type === 'bill' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' :
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        ]"
                                    >
                                        {{ row.type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-indigo-600 dark:text-indigo-400 font-medium">
                                    {{ row.reference }}
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ row.description }}</td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                    {{ Number(row.debit) > 0 ? formatCurrency(row.debit) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                    {{ Number(row.credit) > 0 ? formatCurrency(row.credit) : '-' }}
                                </td>
                                <td class="px-5 py-3 text-right font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ formatCurrency(row.running_balance) }}
                                </td>
                            </tr>
                        </tbody>
                        <!-- Closing Balance Footer -->
                        <tfoot class="border-t-2 border-zinc-300 bg-zinc-50 font-bold dark:border-zinc-700 dark:bg-zinc-800/60">
                            <tr>
                                <td colspan="6" class="px-5 py-3.5 text-right uppercase tracking-wider text-[11px] text-zinc-600 dark:text-zinc-400">
                                    Closing Balance as of {{ statement.to_date }}:
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-base text-indigo-600 dark:text-indigo-400">
                                    {{ formatCurrency(statement.closing_balance) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
