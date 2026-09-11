<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, DatePicker } from '@/components';

interface StatementEntry {
    type: 'invoice' | 'payment';
    date: string;
    reference: string;
    description: string;
    debit: string | number;
    credit: string | number;
    balance: string | number;
}

interface StatementData {
    customer: {
        id: string;
        name: string;
        code: string;
    };
    period: {
        from: string;
        to: string;
    };
    entries: StatementEntry[];
    closing_balance: string;
}

interface Props {
    customer: {
        id: number;
        public_id: string;
        customer_code: string;
        name: string;
    };
    statement: StatementData;
    fromDate: string;
    toDate: string;
}

const props = defineProps<Props>();

const fromInput = ref(props.fromDate);
const toInput = ref(props.toDate);

const applyRange = () => {
    router.get(
        `/admin/accounting/reports/customers/${props.customer.public_id || props.customer.id}/statement`,
        {
            from_date: fromInput.value,
            to_date: toInput.value,
        },
        { preserveState: true }
    );
};

const formatMoney = (val: string | number | undefined) => {
    const num = Number(val || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head :title="`Statement - ${customer.name}`" />

    <OrganizationLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link
                            :href="`/admin/accounting/customers/${customer.public_id || customer.id}`"
                            class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                        >
                            &larr; {{ customer.name }}
                        </Link>
                    </div>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Customer Statement of Account
                    </h1>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        onclick="window.print()"
                    >
                        Print
                    </button>

                    <div class="flex items-center gap-2">
                        <div class="w-36">
                            <DatePicker v-model="fromInput" placeholder="From date" />
                        </div>
                        <span class="text-xs text-slate-400">to</span>
                        <div class="w-36">
                            <DatePicker v-model="toInput" placeholder="To date" />
                        </div>
                        <Button size="sm" @click="applyRange">
                            Filter
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Statement Paper Document -->
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col justify-between gap-6 border-b border-slate-200 pb-6 sm:flex-row dark:border-zinc-800">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Statement For</span>
                        <div class="mt-1 text-xl font-bold text-slate-900 dark:text-white">{{ customer.name }}</div>
                        <div class="font-mono text-xs text-slate-500 dark:text-zinc-400">{{ customer.customer_code }}</div>
                    </div>

                    <div class="sm:text-right">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">Statement Period</span>
                        <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                            {{ statement.period.from }} &mdash; {{ statement.period.to }}
                        </div>
                        <div class="mt-2 text-xs font-medium text-slate-500">
                            Closing Balance:
                            <span class="font-bold text-slate-900 dark:text-white">${{ formatMoney(statement.closing_balance) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Ledger Entries -->
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-zinc-400">
                        <thead class="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                            <tr>
                                <th class="px-3 py-2.5">Date</th>
                                <th class="px-3 py-2.5">Type</th>
                                <th class="px-3 py-2.5">Reference</th>
                                <th class="px-3 py-2.5 text-right">Invoiced (Dr)</th>
                                <th class="px-3 py-2.5 text-right">Paid (Cr)</th>
                                <th class="px-3 py-2.5 text-right font-bold">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                            <tr v-for="(entry, idx) in statement.entries" :key="idx" class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/30">
                                <td class="px-3 py-2.5 font-medium text-slate-900 dark:text-white">{{ entry.date }}</td>
                                <td class="px-3 py-2.5 uppercase text-[10px] font-semibold">
                                    <span :class="entry.type === 'invoice' ? 'text-indigo-600 dark:text-indigo-400' : 'text-emerald-600 dark:text-emerald-400'">
                                        {{ entry.type }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 font-mono text-slate-700 dark:text-zinc-300">{{ entry.reference }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">
                                    {{ Number(entry.debit) > 0 ? `$${formatMoney(entry.debit)}` : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                    {{ Number(entry.credit) > 0 ? `-$${formatMoney(entry.credit)}` : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold font-mono text-slate-900 dark:text-white">
                                    ${{ formatMoney(entry.balance) }}
                                </td>
                            </tr>
                            <tr v-if="statement.entries.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400 dark:text-zinc-500">
                                    No transactions recorded within this date range.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-slate-300 font-semibold text-slate-900 dark:border-zinc-700 dark:text-white">
                            <tr>
                                <td colspan="5" class="px-3 py-3 text-right uppercase text-xs tracking-wider">Closing Balance:</td>
                                <td class="px-3 py-3 text-right font-mono text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                    ${{ formatMoney(statement.closing_balance) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
