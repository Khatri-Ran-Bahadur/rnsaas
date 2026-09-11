<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';

const reportCategories = [
    {
        name: 'Accounts Receivable',
        description: 'Track client receivables, unpaid sales invoices, aging debt, and statements.',
        icon: 'receivable',
        reports: [
            {
                name: 'Receivable Aging Report',
                description: 'Categorize unpaid client invoices by overdue time intervals (Current, 1-30, 31-60, 61-90, 90+ days).',
                href: '/admin/accounting/reports/receivable-aging',
                badge: 'Essential',
            },
            {
                name: 'Customer Directory & Statements',
                description: 'Inspect customer ledger activity, invoices, credit allocations, and print individual statements.',
                href: '/admin/accounting/customers',
                badge: 'Directory',
            },
        ],
    },
    {
        name: 'Accounts Payable',
        description: 'Monitor supplier obligations, pending purchase bills, payment schedules, and aging liabilities.',
        icon: 'payable',
        reports: [
            {
                name: 'Payable Aging Report',
                description: 'Categorize unpaid vendor bills by overdue time intervals to manage cash flow commitments.',
                href: '/admin/accounting/reports/payable-aging',
                badge: 'Essential',
            },
            {
                name: 'Vendor Directory & Statements',
                description: 'View supplier ledger history, open purchase bills, payments, and generated statement reports.',
                href: '/admin/accounting/vendors',
                badge: 'Directory',
            },
        ],
    },
    {
        name: 'General Ledger & Trial Balance',
        description: 'Verify double-entry book balancing and review detailed journal transaction audits.',
        icon: 'ledger',
        reports: [
            {
                name: 'Trial Balance',
                description: 'Complete list of all chart of accounts debit and credit balances to verify accounting equality.',
                href: '/admin/accounting/reports/trial-balance',
                badge: 'Audit',
            },
            {
                name: 'General Ledger',
                description: 'Detailed chronological transaction register and running balances for any postable account.',
                href: '/admin/accounting/reports/general-ledger',
                badge: 'Audit',
            },
        ],
    },
    {
        name: 'Financial Statements',
        description: 'Formal financial performance statements compliant with accounting standards.',
        icon: 'statements',
        reports: [
            {
                name: 'Profit and Loss (Income Statement)',
                description: 'Summary of company revenues, costs, and expenses incurred during a specific period.',
                href: '/admin/accounting/statements/profit-and-loss',
                badge: 'Financial',
            },
            {
                name: 'Balance Sheet',
                description: 'Financial snapshot of company assets, liabilities, and shareholder equity as of a specific date.',
                href: '/admin/accounting/statements/balance-sheet',
                badge: 'Financial',
            },
        ],
    },
];
</script>

<template>
    <Head title="Accounting Reports Center" />

    <OrganizationLayout>
        <div class="space-y-8">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Accounting & Financial Reports
                </h1>
                <p class="text-sm text-slate-500 dark:text-zinc-400">
                    Comprehensive reporting suite for receivables, payables, general ledger, and executive financial statements.
                </p>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                <div
                    v-for="cat in reportCategories"
                    :key="cat.name"
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-indigo-50 p-2.5 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                            <svg v-if="cat.icon === 'receivable'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg v-else-if="cat.icon === 'payable'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <svg v-else-if="cat.icon === 'ledger'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ cat.name }}</h2>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">{{ cat.description }}</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <Link
                            v-for="rep in cat.reports"
                            :key="rep.name"
                            :href="rep.href"
                            class="group block rounded-lg border border-slate-100 bg-slate-50/60 p-4 transition-all hover:border-indigo-200 hover:bg-indigo-50/40 dark:border-zinc-800 dark:bg-zinc-800/40 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/80"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400">
                                    {{ rep.name }}
                                </span>
                                <span class="rounded bg-slate-200/80 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-zinc-700 dark:text-zinc-300">
                                    {{ rep.badge }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 line-clamp-2 dark:text-zinc-400">
                                {{ rep.description }}
                            </p>
                            <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                View Report &rarr;
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
