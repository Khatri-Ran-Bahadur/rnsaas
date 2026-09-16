<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';

interface Feature {
    id: number;
    name: string;
    description: string | null;
    module: string;
}

interface Plan {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string | number;
    currency: string;
    billing_cycle: string;
    included_branches: number;
    extra_branch_price: string | number;
    features?: Feature[];
}

interface Subscription {
    id: number;
    plan_id: number;
    status: string;
    allowed_branches: number;
    starts_at: string | null;
    current_period_starts_at: string | null;
    current_period_ends_at: string | null;
    plan?: Plan;
}

interface PendingTransfer {
    id: number;
    public_id: string;
    branches_count: number;
    total_amount: string | number;
    currency: string;
    billing_cycle: string;
    transaction_reference: string;
    receipt_path: string;
    notes: string | null;
    status: string;
    created_at: string;
    plan?: Plan;
}

const props = defineProps<{
    subscription: Subscription | null;
    pendingTransfer: PendingTransfer | null;
    branchStats: {
        allowed: number;
        used: number;
        available: number;
    };
    bankSettings: {
        enabled: boolean;
        instructions: string;
    };
}>();

const showAddBranchModal = ref(false);

const addBranchForm = useForm({
    additional_branches: 1,
    transaction_reference: '',
    receipt: null as File | null,
    notes: '',
});

const unitExtraPrice = computed(() => {
    return Number(props.subscription?.plan?.extra_branch_price ?? 15.0);
});

const calculatedAddonTotal = computed(() => {
    return (addBranchForm.additional_branches * unitExtraPrice.value).toFixed(2);
});

const handleReceiptChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        addBranchForm.receipt = target.files[0];
    }
};

const submitAddBranches = () => {
    addBranchForm.post('/admin/subscription/add-branches', {
        preserveScroll: true,
        onSuccess: () => {
            showAddBranchModal.value = false;
            addBranchForm.reset();
        },
    });
};

const isActive = computed(() => {
    return props.subscription && ['active', 'trialing'].includes(props.subscription.status);
});

const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="My Subscription - Organization Billing" />

    <OrganizationLayout>
        <div class="space-y-6 pb-12 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Organization Subscription
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">
                        Manage your active plan, branch capacity, and billing records.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/subscription/orders"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-slate-700 dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-800/80 transition-colors shadow-xs"
                    >
                        <svg class="w-4 h-4 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Billing History
                    </Link>
                    <Link
                        href="/admin/subscription/plans"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Upgrade / Change Plan
                    </Link>
                </div>
            </div>

            <!-- Pending Review Notice Banner -->
            <div
                v-if="pendingTransfer"
                class="rounded-2xl border border-amber-200 bg-amber-50/70 dark:border-amber-900/40 dark:bg-amber-950/20 p-5 shadow-xs"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs">
                        <svg class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-semibold text-amber-950 dark:text-amber-300">
                                Bank Transfer Payment Pending Review
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/60 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:text-amber-200">
                                Awaiting Approval
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-300/80 leading-relaxed">
                            We received your bank transfer payment of
                            <strong class="font-semibold text-amber-950 dark:text-amber-200">{{ pendingTransfer.currency }} {{ Number(pendingTransfer.total_amount).toFixed(2) }}</strong>
                            (Ref: <code class="font-mono bg-amber-100/70 dark:bg-amber-900/50 px-1.5 py-0.5 rounded text-xs">{{ pendingTransfer.transaction_reference }}</code>).
                            SuperAdmin is verifying your receipt. Your plan and branches will be activated automatically once approved.
                        </p>
                        <div class="mt-3 flex items-center gap-3">
                            <a
                                :href="`/storage/${pendingTransfer.receipt_path}`"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-900 dark:text-amber-300 underline underline-offset-4 hover:text-amber-700"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                View Uploaded Receipt
                            </a>
                            <span class="text-amber-300 dark:text-amber-700">•</span>
                            <span class="text-xs text-amber-800/70 dark:text-amber-400/70">
                                Submitted {{ formatDate(pendingTransfer.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Subscription Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Current Plan Card -->
                <div class="lg:col-span-2 rounded-2xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-xs relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-40 h-40 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-2xl pointer-events-none" />

                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                                Current Active Plan
                            </span>
                            <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1">
                                {{ subscription?.plan?.name ?? 'No Active Plan' }}
                            </h2>
                            <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1 max-w-md">
                                {{ subscription?.plan?.description ?? 'Subscribe to a package to unlock all modules for your business.' }}
                            </p>
                        </div>
                        <div>
                            <span
                                v-if="isActive"
                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                Active
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center rounded-full bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 px-3 py-1 text-xs font-semibold text-rose-700 dark:text-rose-300"
                            >
                                Unsubscribed
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-slate-100 dark:border-zinc-800/80 pt-6">
                        <div>
                            <span class="text-xs text-slate-400 dark:text-zinc-500">Billing Cycle</span>
                            <p class="text-sm font-semibold text-slate-800 dark:text-zinc-200 capitalize mt-0.5">
                                {{ subscription?.plan?.billing_cycle ?? 'Monthly' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 dark:text-zinc-500">Subscription Started</span>
                            <p class="text-sm font-semibold text-slate-800 dark:text-zinc-200 mt-0.5">
                                {{ formatDate(subscription?.current_period_starts_at ?? subscription?.starts_at ?? null) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 dark:text-zinc-500">Renews / Valid Until</span>
                            <p class="text-sm font-semibold text-slate-800 dark:text-zinc-200 mt-0.5">
                                {{ formatDate(subscription?.current_period_ends_at ?? null) }}
                            </p>
                        </div>
                    </div>

                    <!-- Included Plan Features -->
                    <div v-if="subscription?.plan?.features?.length" class="mt-6 border-t border-slate-100 dark:border-zinc-800/80 pt-6">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500 mb-3">
                            Included Modules & Features
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="feat in subscription.plan.features"
                                :key="feat.id"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-zinc-800 px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-zinc-300"
                            >
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                {{ feat.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Branch Capacity Card -->
                <div class="rounded-2xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500">
                                Branch Allocation
                            </span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                {{ branchStats.used }} / {{ branchStats.allowed }} Used
                            </span>
                        </div>

                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-slate-900 dark:text-white">
                                {{ branchStats.allowed }}
                            </span>
                            <span class="text-sm text-slate-500 dark:text-zinc-400">
                                allowed branch(es)
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-zinc-800 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="branchStats.used >= branchStats.allowed ? 'bg-amber-500' : 'bg-indigo-600'"
                                    :style="{ width: `${Math.min(100, (branchStats.used / Math.max(1, branchStats.allowed)) * 100)}%` }"
                                />
                            </div>
                            <div class="flex justify-between text-xs text-slate-400 dark:text-zinc-500 mt-2">
                                <span>{{ branchStats.used }} active</span>
                                <span>{{ branchStats.available }} remaining slot(s)</span>
                            </div>
                        </div>

                        <p class="mt-4 text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
                            Base plan includes {{ subscription?.plan?.included_branches ?? 1 }} branch. Additional branches are billed at
                            <strong>{{ subscription?.plan?.currency ?? 'USD' }} {{ Number(subscription?.plan?.extra_branch_price ?? 15).toFixed(2) }}</strong>/branch.
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-zinc-800">
                        <button
                            v-if="isActive"
                            type="button"
                            @click="showAddBranchModal = true"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 dark:bg-white px-4 py-2.5 text-sm font-semibold text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-zinc-100 transition-colors shadow-xs"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add More Branches
                        </button>
                        <Link
                            v-else
                            href="/admin/subscription/plans"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors shadow-xs"
                        >
                            Subscribe Now
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Add Branches Bank Transfer Modal -->
            <div
                v-if="showAddBranchModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
            >
                <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-2xl border border-slate-200 dark:border-zinc-800 my-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-zinc-800">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Purchase Additional Branches
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                                Expand your organization's multi-branch capacity.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="showAddBranchModal = false"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitAddBranches" class="mt-5 space-y-4">
                        <!-- Branch Count Input -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                How many additional branches do you want?
                            </label>
                            <div class="mt-1.5 flex items-center gap-3">
                                <input
                                    v-model.number="addBranchForm.additional_branches"
                                    type="number"
                                    min="1"
                                    max="50"
                                    class="w-32 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white focus:outline-hidden"
                                    required
                                />
                                <span class="text-xs text-slate-500 dark:text-zinc-400">
                                    × {{ subscription?.plan?.currency ?? 'USD' }} {{ unitExtraPrice.toFixed(2) }} / branch
                                </span>
                            </div>
                        </div>

                        <!-- Price Calculation Summary -->
                        <div class="rounded-xl bg-slate-50 dark:bg-zinc-950 p-4 border border-slate-100 dark:border-zinc-800">
                            <div class="flex justify-between text-xs text-slate-500 dark:text-zinc-400">
                                <span>Current Allowed Branches</span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ branchStats.allowed }}</span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500 dark:text-zinc-400 mt-1">
                                <span>New Total After Approval</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ branchStats.allowed + (Number(addBranchForm.additional_branches) || 0) }} branches
                                </span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-200 dark:border-zinc-800 flex justify-between items-baseline">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white">Total Amount Due</span>
                                <span class="text-xl font-extrabold text-slate-900 dark:text-white">
                                    {{ subscription?.plan?.currency ?? 'USD' }} {{ calculatedAddonTotal }}
                                </span>
                            </div>
                        </div>

                        <!-- Bank Transfer Instructions -->
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 dark:border-indigo-900/40 dark:bg-indigo-950/20 p-4">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-300">
                                SuperAdmin Bank Details
                            </span>
                            <pre class="mt-1.5 text-xs text-indigo-950/80 dark:text-indigo-200 whitespace-pre-wrap font-sans leading-relaxed">{{ bankSettings.instructions }}</pre>
                        </div>

                        <!-- Transaction Reference -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                Bank Transaction / Reference ID *
                            </label>
                            <input
                                v-model="addBranchForm.transaction_reference"
                                type="text"
                                placeholder="e.g. TXN-9482759284"
                                class="mt-1.5 w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white focus:outline-hidden"
                                required
                            />
                        </div>

                        <!-- Receipt Upload -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                Upload Payment Receipt (Image or PDF) *
                            </label>
                            <input
                                type="file"
                                accept="image/*,application/pdf"
                                @change="handleReceiptChange"
                                class="mt-1.5 w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                                required
                            />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                Additional Notes (Optional)
                            </label>
                            <textarea
                                v-model="addBranchForm.notes"
                                rows="2"
                                placeholder="Any note or depositor name..."
                                class="mt-1.5 w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white focus:outline-hidden"
                            />
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-zinc-800">
                            <button
                                type="button"
                                @click="showAddBranchModal = false"
                                class="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="addBranchForm.processing"
                                class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors disabled:opacity-50"
                            >
                                {{ addBranchForm.processing ? 'Submitting...' : 'Submit Payment' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
