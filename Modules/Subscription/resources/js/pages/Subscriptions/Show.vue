<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';

export interface TenantInfo {
    id: number;
    public_id: string;
    name: string;
}

export interface PlanInfo {
    id: number;
    public_id: string;
    name: string;
    price: string | number;
    currency: string;
    billing_cycle: string;
    trial_days?: number;
}

export interface PaymentTransaction {
    id: number;
    public_id: string;
    subscription_id: number | null;
    tenant_id: number;
    provider: string;
    amount: string | number;
    currency: string;
    status: string;
    type: string;
    paid_at: string | null;
    created_at: string;
}

export interface SubscriptionDetail {
    id: number;
    public_id: string;
    tenant_id: number;
    plan_id: number;
    status: 'pending' | 'trialing' | 'active' | 'past_due' | 'canceled' | 'expired';
    starts_at: string | null;
    trial_ends_at: string | null;
    current_period_starts_at: string | null;
    current_period_ends_at: string | null;
    canceled_at: string | null;
    ends_at: string | null;
    created_at: string;
    tenant?: TenantInfo;
    plan?: PlanInfo;
    payments?: PaymentTransaction[];
}

const props = defineProps<{
    subscription: SubscriptionDetail;
}>();

const formatAmount = (price?: string | number, currency?: string) => {
    if (price === undefined || price === null) return '—';
    const num = typeof price === 'string' ? parseFloat(price) : price;
    return `${currency?.toUpperCase() ?? 'USD'} ${isNaN(num) ? '0.00' : num.toFixed(2)}`;
};

const formatBillingCycle = (cycle?: string | null) => {
    if (!cycle) return '—';
    return cycle.charAt(0).toUpperCase() + cycle.slice(1);
};

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusBadgeVariant = (statusStr: string) => {
    switch (statusStr) {
        case 'active':
            return 'active';
        case 'trialing':
            return 'trialing';
        case 'pending':
            return 'pending';
        case 'past_due':
            return 'paused';
        case 'canceled':
        case 'expired':
            return 'cancelled';
        default:
            return 'neutral';
    }
};

const formatProvider = (providerStr?: string | null) => {
    if (!providerStr) return '—';
    switch (providerStr.toLowerCase()) {
        case 'bank_transfer':
            return 'Bank Transfer';
        case 'stripe':
            return 'Stripe';
        case 'razorpay':
            return 'Razorpay';
        default:
            return providerStr.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }
};

const showCancelModal = ref(false);
const isCanceling = ref(false);

const canCancel = (sub: SubscriptionDetail) => {
    return (sub.status === 'active' || sub.status === 'trialing') && !sub.canceled_at;
};

const confirmCancel = () => {
    isCanceling.value = true;
    router.post(
        `/admin/subscriptions/${props.subscription.id}/cancel`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isCanceling.value = false;
                showCancelModal.value = false;
            },
        }
    );
};
</script>

<template>
    <AdminLayout
        title="Subscription Details"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Subscriptions', href: '/admin/subscriptions' },
            { label: subscription.public_id },
        ]"
    >
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Subscription Details
                    </h1>
                    <Badge
                        :variant="getStatusBadgeVariant(subscription.status)"
                        size="md"
                        :label="subscription.status.toUpperCase()"
                    />
                </div>
                <p class="mt-1 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                    {{ subscription.public_id }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    v-if="canCancel(subscription)"
                    variant="danger"
                    size="sm"
                    @click="showCancelModal = true"
                >
                    <template #prefix>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                    <span>Cancel Subscription</span>
                </Button>

                <Link href="/admin/subscriptions">
                    <Button variant="outline" size="sm">
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </template>
                        <span>Back to Subscriptions</span>
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Canceled Status Banner -->
        <div
            v-if="subscription.canceled_at"
            class="mt-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200"
        >
            <svg class="h-5 w-5 shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="text-sm">
                <p class="font-semibold">Subscription Scheduled for Cancellation</p>
                <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300">
                    This subscription was canceled on {{ formatDate(subscription.canceled_at) }}. The organization retains active access until the current billing period ends on {{ formatDate(subscription.ends_at ?? subscription.current_period_ends_at) }}.
                </p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Subscription & Plan Info (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Subscription Overview Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">
                        Subscription Overview
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Plan Name</span>
                            <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ subscription.plan?.name ?? `Plan #${subscription.plan_id}` }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Price & Billing Cycle</span>
                            <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ formatAmount(subscription.plan?.price, subscription.plan?.currency) }} / {{ formatBillingCycle(subscription.plan?.billing_cycle) }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Started At</span>
                            <p class="mt-1 font-mono text-zinc-800 dark:text-zinc-200">
                                {{ formatDate(subscription.starts_at) }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Trial Ends At</span>
                            <p class="mt-1 font-mono text-zinc-800 dark:text-zinc-200">
                                {{ subscription.trial_ends_at ? formatDate(subscription.trial_ends_at) : 'No trial' }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Current Period Start</span>
                            <p class="mt-1 font-mono text-zinc-800 dark:text-zinc-200">
                                {{ formatDate(subscription.current_period_starts_at) }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Current Period End</span>
                            <p class="mt-1 font-mono text-zinc-800 dark:text-zinc-200">
                                {{ formatDate(subscription.current_period_ends_at) }}
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Canceled At</span>
                            <p class="mt-1 font-mono text-zinc-800 dark:text-zinc-200">
                                <span v-if="subscription.canceled_at" class="text-rose-600 dark:text-rose-400">
                                    {{ formatDate(subscription.canceled_at) }}
                                </span>
                                <span v-else>Not canceled</span>
                            </p>
                        </div>

                        <div class="rounded-lg border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Ends At</span>
                            <p class="mt-1 font-mono text-zinc-800 dark:text-zinc-200">
                                {{ subscription.ends_at ? formatDate(subscription.ends_at) : 'Active / Continuous' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Related Payments Section -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                            Related Payments
                        </h3>
                        <span class="text-xs text-zinc-400">
                            {{ subscription.payments?.length ?? 0 }} records
                        </span>
                    </div>

                    <div v-if="subscription.payments && subscription.payments.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                            <thead class="border-b border-zinc-200 bg-zinc-50/75 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400">
                                <tr>
                                    <th class="py-2.5 px-3">Payment ID</th>
                                    <th class="py-2.5 px-3">Provider</th>
                                    <th class="py-2.5 px-3">Amount</th>
                                    <th class="py-2.5 px-3">Status</th>
                                    <th class="py-2.5 px-3">Paid At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr v-for="payment in subscription.payments" :key="payment.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40">
                                    <td class="py-3 px-3 font-mono font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ payment.public_id }}
                                    </td>
                                    <td class="py-3 px-3">
                                        {{ formatProvider(payment.provider) }}
                                    </td>
                                    <td class="py-3 px-3 font-semibold text-zinc-900 dark:text-white">
                                        {{ formatAmount(payment.amount, payment.currency) }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <Badge
                                            :variant="payment.status === 'paid' ? 'active' : payment.status === 'pending' ? 'pending' : 'cancelled'"
                                            size="sm"
                                            :label="payment.status"
                                        />
                                    </td>
                                    <td class="py-3 px-3 font-mono text-zinc-500 dark:text-zinc-400">
                                        {{ formatDate(payment.paid_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-6 text-xs text-zinc-400">
                        No payment transactions recorded for this subscription.
                    </div>
                </div>
            </div>

            <!-- Right Column: Tenant Info & Actions (1 col) -->
            <div class="space-y-6">
                <!-- Tenant Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">
                        Organization
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Organization Name</span>
                            <p class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ subscription.tenant?.name ?? `Tenant #${subscription.tenant_id}` }}
                            </p>
                        </div>

                        <div>
                            <span class="font-medium text-zinc-500 dark:text-zinc-400">Tenant Public ID</span>
                            <p class="mt-0.5 font-mono text-zinc-700 dark:text-zinc-300">
                                {{ subscription.tenant?.public_id ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Lifecycle Actions Box -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-2">
                        Lifecycle Operations
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed mb-4">
                        Perform administrative lifecycle actions directly via dedicated backend actions.
                    </p>

                    <div v-if="canCancel(subscription)">
                        <Button
                            variant="danger"
                            size="sm"
                            class="w-full"
                            @click="showCancelModal = true"
                        >
                            <span>Cancel Subscription</span>
                        </Button>
                        <p class="mt-2 text-[11px] text-zinc-400 dark:text-zinc-500 text-center">
                            Cancels renewal at period end.
                        </p>
                    </div>
                    <div v-else-if="subscription.canceled_at" class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40">
                        <p class="font-medium">Canceled</p>
                        <p class="text-[11px] mt-0.5">Ends on {{ formatDate(subscription.ends_at ?? subscription.current_period_ends_at) }}</p>
                    </div>
                    <div v-else class="text-xs text-zinc-400">
                        No lifecycle actions available for status <span class="font-mono font-medium">{{ subscription.status }}</span>.
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Confirmation Modal -->
        <Modal
            :show="showCancelModal"
            title="Cancel Subscription"
            description="Are you sure you want to cancel this tenant subscription?"
            max-width="md"
            @close="showCancelModal = false"
        >
            <div class="space-y-3 py-2 text-sm text-zinc-600 dark:text-zinc-300">
                <p>
                    This will schedule the subscription for <span class="font-semibold text-zinc-900 dark:text-white">{{ subscription.tenant?.name }}</span> to be canceled.
                </p>
                <div class="rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                    <div class="flex justify-between py-1">
                        <span class="text-zinc-500">Plan:</span>
                        <span class="font-medium text-zinc-900 dark:text-white">{{ subscription.plan?.name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-t border-zinc-200/60 dark:border-zinc-700/60">
                        <span class="text-zinc-500">Access Continues Until:</span>
                        <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ formatDate(subscription.current_period_ends_at) }}</span>
                    </div>
                </div>
                <p class="text-xs text-zinc-500">
                    The tenant will continue to have access to features until the end of the current billing cycle, after which the subscription will not renew.
                </p>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="isCanceling"
                        @click="showCancelModal = false"
                    >
                        <span>Keep Subscription</span>
                    </Button>
                    <Button
                        type="button"
                        variant="danger"
                        size="sm"
                        :loading="isCanceling"
                        @click="confirmCancel"
                    >
                        <span>Confirm Cancellation</span>
                    </Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
