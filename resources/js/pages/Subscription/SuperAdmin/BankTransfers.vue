<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';

interface Tenant {
    id: number;
    name: string;
    slug: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Plan {
    id: number;
    name: string;
}

interface Transfer {
    id: number;
    public_id: string;
    tenant_id: number;
    plan_id: number;
    branches_count: number;
    base_amount: string | number;
    extra_branches_amount: string | number;
    total_amount: string | number;
    currency: string;
    billing_cycle: string;
    transaction_reference: string;
    receipt_path: string;
    notes: string | null;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    approved_at: string | null;
    created_at: string;
    tenant?: Tenant;
    user?: User;
    plan?: Plan;
    approver?: User;
}

const props = defineProps<{
    transfers: {
        data: Transfer[];
        links: any[];
        total: number;
    };
    counts: {
        all: number;
        pending: number;
        approved: number;
        rejected: number;
    };
    filters: {
        status: string;
        search: string;
    };
}>();

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'all');

const applyFilter = (status: string) => {
    currentStatus.value = status;
    router.get('/superadmin/subscriptions/bank-transfers', {
        status,
        search: search.value || undefined,
    }, { preserveState: true });
};

const handleSearch = () => {
    router.get('/superadmin/subscriptions/bank-transfers', {
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
        search: search.value || undefined,
    }, { preserveState: true });
};

// Receipt Preview Modal
const selectedReceiptUrl = ref<string | null>(null);

// Reject Modal
const rejectingTransfer = ref<Transfer | null>(null);
const rejectForm = useForm({
    rejection_reason: '',
});

const openRejectModal = (transfer: Transfer) => {
    rejectingTransfer.value = transfer;
    rejectForm.rejection_reason = '';
};

const submitReject = () => {
    if (!rejectingTransfer.value) return;
    rejectForm.post(`/superadmin/subscriptions/bank-transfers/${rejectingTransfer.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            rejectingTransfer.value = null;
        },
    });
};

const approveTransfer = (transfer: Transfer) => {
    if (confirm(`Approve bank transfer #${transfer.transaction_reference} for ${transfer.tenant?.name}? This will instantly activate the subscription with ${transfer.branches_count} branches.`)) {
        router.post(`/superadmin/subscriptions/bank-transfers/${transfer.id}/approve`, {}, {
            preserveScroll: true,
        });
    }
};

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
    <Head title="Bank Transfer Requests - SuperAdmin" />

    <SuperAdminLayout>
        <div class="space-y-6 pb-12 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Bank Transfer Approvals
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">
                        Review customer payment receipts, verify bank transactions, and approve tenant subscriptions.
                    </p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    @click="applyFilter('all')"
                    :class="[
                        'rounded-2xl p-4 border transition-all cursor-pointer shadow-xs',
                        currentStatus === 'all'
                            ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20'
                            : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900',
                    ]"
                >
                    <span class="text-xs font-semibold text-slate-500 dark:text-zinc-400">All Submissions</span>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ counts.all }}</p>
                </div>

                <div
                    @click="applyFilter('pending')"
                    :class="[
                        'rounded-2xl p-4 border transition-all cursor-pointer shadow-xs',
                        currentStatus === 'pending'
                            ? 'border-amber-500 bg-amber-50/50 dark:bg-amber-950/20 ring-2 ring-amber-500/20'
                            : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">Pending Review</span>
                        <span v-if="counts.pending > 0" class="h-2 w-2 rounded-full bg-amber-500 animate-ping" />
                    </div>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ counts.pending }}</p>
                </div>

                <div
                    @click="applyFilter('approved')"
                    :class="[
                        'rounded-2xl p-4 border transition-all cursor-pointer shadow-xs',
                        currentStatus === 'approved'
                            ? 'border-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20'
                            : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900',
                    ]"
                >
                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Approved</span>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ counts.approved }}</p>
                </div>

                <div
                    @click="applyFilter('rejected')"
                    :class="[
                        'rounded-2xl p-4 border transition-all cursor-pointer shadow-xs',
                        currentStatus === 'rejected'
                            ? 'border-rose-600 bg-rose-50/50 dark:bg-rose-950/20'
                            : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900',
                    ]"
                >
                    <span class="text-xs font-semibold text-rose-700 dark:text-rose-400">Rejected</span>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ counts.rejected }}</p>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <button
                        v-for="s in ['all', 'pending', 'approved', 'rejected']"
                        :key="s"
                        type="button"
                        @click="applyFilter(s)"
                        :class="[
                            'rounded-xl px-3.5 py-1.5 text-xs font-semibold capitalize transition-all',
                            currentStatus === s
                                ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs'
                                : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800',
                        ]"
                    >
                        {{ s }}
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Search reference, organization, user..."
                        class="w-full sm:w-72 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white focus:outline-hidden"
                    />
                    <button
                        type="button"
                        @click="handleSearch"
                        class="rounded-xl bg-slate-100 dark:bg-zinc-800 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors"
                    >
                        Search
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-2xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-zinc-800/50 border-b border-slate-100 dark:border-zinc-800 text-slate-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-6 py-3.5 font-semibold">Organization</th>
                                <th class="px-6 py-3.5 font-semibold">Reference ID</th>
                                <th class="px-6 py-3.5 font-semibold">Plan & Branches</th>
                                <th class="px-6 py-3.5 font-semibold">Total Amount</th>
                                <th class="px-6 py-3.5 font-semibold">Date</th>
                                <th class="px-6 py-3.5 font-semibold">Receipt</th>
                                <th class="px-6 py-3.5 font-semibold">Status</th>
                                <th class="px-6 py-3.5 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60 text-slate-700 dark:text-zinc-300">
                            <tr v-if="!transfers.data.length">
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400 dark:text-zinc-500">
                                    No bank transfer requests found.
                                </td>
                            </tr>
                            <tr
                                v-for="t in transfers.data"
                                :key="t.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/30 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ t.tenant?.name ?? 'Unknown Tenant' }}
                                    </div>
                                    <span class="text-[11px] text-slate-400 dark:text-zinc-500">
                                        {{ t.user?.email }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono font-medium text-slate-900 dark:text-white">
                                    {{ t.transaction_reference }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-white">
                                        {{ t.plan?.name ?? 'Branch Add-on' }}
                                    </div>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 mt-0.5">
                                        {{ t.branches_count }} branch(es)
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ t.currency }} {{ Number(t.total_amount).toFixed(2) }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-zinc-400">
                                    {{ formatDate(t.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        type="button"
                                        @click="selectedReceiptUrl = `/storage/${t.receipt_path}`"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-800 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-zinc-200 hover:bg-slate-100 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View Slip
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="t.status === 'approved'"
                                        class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                                    >
                                        Approved
                                    </span>
                                    <span
                                        v-else-if="t.status === 'pending'"
                                        class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-300"
                                    >
                                        Pending
                                    </span>
                                    <div v-else class="space-y-1">
                                        <span class="inline-flex items-center rounded-full bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:text-rose-300">
                                            Rejected
                                        </span>
                                        <p v-if="t.rejection_reason" class="text-[10px] text-rose-600 dark:text-rose-400 max-w-[150px] truncate" :title="t.rejection_reason">
                                            {{ t.rejection_reason }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div v-if="t.status === 'pending'" class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="approveTransfer(t)"
                                            class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 transition-colors shadow-xs"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            @click="openRejectModal(t)"
                                            class="inline-flex items-center gap-1 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 px-3 py-1.5 text-xs font-semibold text-rose-700 dark:text-rose-300 hover:bg-rose-100 transition-colors"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                    <div v-else-if="t.status === 'approved'" class="text-[11px] text-slate-400 dark:text-zinc-500">
                                        By {{ t.approver?.name ?? 'Admin' }}
                                    </div>
                                    <div v-else class="text-[11px] text-slate-400 dark:text-zinc-500">
                                        Closed
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Receipt Modal -->
            <div
                v-if="selectedReceiptUrl"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/75 backdrop-blur-xs p-4"
                @click.self="selectedReceiptUrl = null"
            >
                <div class="relative max-w-3xl w-full max-h-[90vh] bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-zinc-800 p-4 flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-zinc-800">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                            Payment Receipt Document
                        </h4>
                        <div class="flex items-center gap-2">
                            <a
                                :href="selectedReceiptUrl"
                                target="_blank"
                                download
                                class="rounded-lg px-2.5 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50"
                            >
                                Download
                            </a>
                            <button
                                type="button"
                                @click="selectedReceiptUrl = null"
                                class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-zinc-800"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 overflow-auto p-4 flex items-center justify-center bg-slate-50 dark:bg-zinc-950 rounded-xl mt-3">
                        <iframe
                            v-if="selectedReceiptUrl.endsWith('.pdf')"
                            :src="selectedReceiptUrl"
                            class="w-full h-[65vh] rounded-lg"
                        />
                        <img
                            v-else
                            :src="selectedReceiptUrl"
                            alt="Payment Receipt"
                            class="max-h-[70vh] object-contain rounded-lg shadow-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Reject Reason Modal -->
            <div
                v-if="rejectingTransfer"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
            >
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-2xl border border-slate-200 dark:border-zinc-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Reject Bank Transfer #{{ rejectingTransfer.transaction_reference }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                        Please state the reason for rejecting this payment (e.g. invalid receipt, funds not received).
                    </p>

                    <form @submit.prevent="submitReject" class="mt-4 space-y-4">
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            rows="3"
                            required
                            placeholder="Reason for rejection..."
                            class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:border-rose-500 focus:bg-white focus:outline-hidden"
                        />
                        <span v-if="rejectForm.errors.rejection_reason" class="text-xs text-rose-600 block">
                            {{ rejectForm.errors.rejection_reason }}
                        </span>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <button
                                type="button"
                                @click="rejectingTransfer = null"
                                class="rounded-xl px-3.5 py-1.5 text-xs font-medium text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="rejectForm.processing"
                                class="rounded-xl bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-rose-500 disabled:opacity-50"
                            >
                                Confirm Rejection
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
