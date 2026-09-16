<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';

interface Plan {
    id: number;
    name: string;
}

interface Transfer {
    id: number;
    public_id: string;
    branches_count: number;
    total_amount: string | number;
    currency: string;
    billing_cycle: string;
    transaction_reference: string;
    receipt_path: string;
    notes: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    created_at: string;
    approved_at: string | null;
    plan?: Plan;
}

defineProps<{
    transfers: {
        data: Transfer[];
        links: any[];
        total: number;
    };
}>();

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Billing & Payment Receipts - Organization" />

    <OrganizationLayout>
        <div class="space-y-6 pb-12 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Billing & Payment History
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">
                        Track your bank transfer requests, invoices, and verified receipts.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/subscription"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-slate-700 dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-800/80 transition-colors shadow-xs"
                    >
                        My Subscription
                    </Link>
                    <Link
                        href="/admin/subscription/plans"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                    >
                        Upgrade / Change Plan
                    </Link>
                </div>
            </div>

            <!-- Orders Table Card -->
            <div class="rounded-2xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-zinc-800/50 border-b border-slate-100 dark:border-zinc-800 text-slate-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-6 py-3.5 font-semibold">Reference ID</th>
                                <th class="px-6 py-3.5 font-semibold">Plan</th>
                                <th class="px-6 py-3.5 font-semibold">Branches</th>
                                <th class="px-6 py-3.5 font-semibold">Amount</th>
                                <th class="px-6 py-3.5 font-semibold">Submitted Date</th>
                                <th class="px-6 py-3.5 font-semibold">Status</th>
                                <th class="px-6 py-3.5 font-semibold text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60 text-slate-700 dark:text-zinc-300">
                            <tr v-if="!transfers.data.length">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 dark:text-zinc-500">
                                    No payment requests found yet.
                                </td>
                            </tr>
                            <tr
                                v-for="t in transfers.data"
                                :key="t.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/30 transition-colors"
                            >
                                <td class="px-6 py-4 font-mono font-medium text-slate-900 dark:text-white">
                                    {{ t.transaction_reference }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                                    {{ t.plan?.name ?? 'Branch Add-on' }}
                                    <span class="block text-[11px] font-normal text-slate-400 dark:text-zinc-500 capitalize">
                                        {{ t.billing_cycle }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-zinc-800 font-semibold text-slate-700 dark:text-zinc-200">
                                        {{ t.branches_count }} {{ t.branches_count === 1 ? 'branch' : 'branches' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ t.currency }} {{ Number(t.total_amount).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-zinc-400">
                                    {{ formatDate(t.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="t.status === 'approved'"
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                                    >
                                        Approved
                                    </span>
                                    <span
                                        v-else-if="t.status === 'pending'"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-300"
                                    >
                                        Pending Review
                                    </span>
                                    <div v-else class="space-y-1">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:text-rose-300">
                                            Rejected
                                        </span>
                                        <p v-if="t.rejection_reason" class="text-[11px] text-rose-600 dark:text-rose-400">
                                            {{ t.rejection_reason }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a
                                        :href="`/storage/${t.receipt_path}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 dark:border-zinc-800 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
