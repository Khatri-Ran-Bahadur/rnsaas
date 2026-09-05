<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import StatsCard from '@/components/StatsCard.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Select from '@/components/Select.vue';
import Dropdown from '@/components/Dropdown.vue';
import Modal from '@/components/Modal.vue';
import Pagination from '@/components/Pagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import ListGridToggle from '@/components/ListGridToggle.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import SearchInput from '@/components/SearchInput.vue';
import FilterButton from '@/components/FilterButton.vue';

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
}

export interface SubscriptionItem {
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
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedSubscriptions {
    data: SubscriptionItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface PlanOption {
    id: number;
    name: string;
    billing_cycle?: string;
}

export interface Filters {
    search?: string;
    status?: string;
    plan?: number | string;
    billing_cycle?: string;
    per_page?: number | string;
}

export interface SummaryCounts {
    total: number;
    active: number;
    trialing: number;
    pending: number;
    canceled_or_expired: number;
}

const props = defineProps<{
    subscriptions: PaginatedSubscriptions;
    plans: PlanOption[];
    filters?: Filters;
    summary?: SummaryCounts;
}>();

// View and filter states
const viewMode = ref<'list' | 'grid'>('list');
const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');
const plan = ref(props.filters?.plan ? String(props.filters.plan) : '');
const billingCycle = ref(props.filters?.billing_cycle ?? '');
const perPage = ref(Number(props.filters?.per_page ?? 10));
const showFilters = ref(false);

const activeFiltersCount = computed(() => {
    return [status.value, plan.value, billingCycle.value].filter(Boolean).length;
});

const statusOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Trialing', value: 'trialing' },
    { label: 'Pending', value: 'pending' },
    { label: 'Past Due', value: 'past_due' },
    { label: 'Canceled', value: 'canceled' },
    { label: 'Expired', value: 'expired' },
];

const planOptions = computed(() => [
    { label: 'All Plans', value: '' },
    ...props.plans.map((p) => ({ label: p.name, value: String(p.id) })),
]);

const billingCycleOptions = [
    { label: 'All Cycles', value: '' },
    { label: 'Monthly', value: 'monthly' },
    { label: 'Quarterly', value: 'quarterly' },
    { label: 'Yearly', value: 'yearly' },
    { label: 'Lifetime', value: 'lifetime' },
];

const subToCancel = ref<SubscriptionItem | null>(null);
const isCanceling = ref(false);

const subToReactivate = ref<SubscriptionItem | null>(null);
const isReactivating = ref(false);

const canCancel = (sub: SubscriptionItem) => {
    return (sub.status === 'active' || sub.status === 'trialing') && !sub.canceled_at;
};

const canReactivate = (sub: SubscriptionItem): boolean => {
    if (!sub.canceled_at) return false;
    if (sub.status !== 'active' && sub.status !== 'trialing') return false;
    if (sub.current_period_ends_at && new Date(sub.current_period_ends_at) <= new Date()) return false;
    return true;
};

const confirmCancel = () => {
    if (!subToCancel.value) return;
    isCanceling.value = true;
    router.post(
        `/superadmin/subscriptions/${subToCancel.value.id}/cancel`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isCanceling.value = false;
                subToCancel.value = null;
            },
        }
    );
};

const confirmReactivate = () => {
    if (!subToReactivate.value) return;
    isReactivating.value = true;
    router.post(
        `/superadmin/subscriptions/${subToReactivate.value.id}/reactivate`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isReactivating.value = false;
                subToReactivate.value = null;
            },
        }
    );
};

const applyFilters = () => {
    router.get(
        '/superadmin/subscriptions',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            plan: plan.value || undefined,
            billing_cycle: billingCycle.value || undefined,
            per_page: perPage.value !== 10 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const handlePerPageChange = (newPerPage: number) => {
    perPage.value = newPerPage;
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    plan.value = '';
    billingCycle.value = '';
    applyFilters();
};

// Formatting helpers
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
    });
};

const getStatusBadgeVariant = (status: SubscriptionItem['status']) => {
    switch (status) {
        case 'active':
            return 'success';
        case 'trialing':
            return 'info';
        case 'pending':
            return 'warning';
        case 'past_due':
            return 'warning';
        case 'canceled':
        case 'expired':
            return 'danger';
        default:
            return 'default';
    }
};

const formatStatusLabel = (status: SubscriptionItem['status']) => {
    switch (status) {
        case 'active':
            return 'Active';
        case 'trialing':
            return 'In Trial';
        case 'pending':
            return 'Pending';
        case 'past_due':
            return 'Past Due';
        case 'canceled':
            return 'Canceled';
        case 'expired':
            return 'Expired';
        default:
            return status;
    }
};
</script>

<template>
    <SuperAdminLayout
        title="Subscriptions"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Subscriptions' },
        ]"
    >
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Subscriptions
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Manage and monitor all tenant organization subscriptions, billing schedules, and plan allocations.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    href="/superadmin/subscriptions/create"
                    variant="primary"
                >
                    <template #prefix>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </template>
                    <span>Create Subscription</span>
                </Button>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total -->
            <StatsCard
                title="Total"
                :value="summary?.total ?? '—'"
                subtitle="All subscriptions"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </template>
            </StatsCard>

            <!-- Active -->
            <StatsCard
                title="Active"
                :value="summary?.active ?? '—'"
                subtitle="Currently active tenants"
                badge-color="emerald"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- Trialing -->
            <StatsCard
                title="Trialing"
                :value="summary?.trialing ?? '—'"
                subtitle="In evaluation period"
                badge-color="blue"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- Pending -->
            <StatsCard
                title="Pending"
                :value="summary?.pending ?? '—'"
                subtitle="Awaiting activation / payment"
                badge-color="amber"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- Canceled / Expired -->
            <StatsCard
                title="Canceled / Expired"
                :value="summary?.canceled_or_expired ?? '—'"
                subtitle="Ended or deactivated"
                badge-color="rose"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatsCard>
        </div>

        <!-- Main Content Card (Accounting style: search + controls header, table/grid, and inside pagination) -->
        <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200/90 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <!-- Search & Controls Header -->
            <div class="p-4 sm:p-5 border-b border-zinc-200/90 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-900/60">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="flex-1 max-w-md">
                        <SearchInput
                            v-model="search"
                            placeholder="Search by organization name..."
                            @search="applyFilters"
                            @clear="clearFilters"
                        />
                    </div>

                    <!-- Right Controls (List/Grid Toggle, Per Page, Filter Button) -->
                    <div class="flex items-center gap-2.5 self-end sm:self-auto">
                        <!-- List / Grid Toggle -->
                        <ListGridToggle v-model="viewMode" />

                        <!-- Per Page Selector -->
                        <PerPageSelector
                            v-model="perPage"
                            @change="handlePerPageChange"
                        />

                        <!-- Advanced Filter Toggle Button -->
                        <FilterButton
                            :show-filters="showFilters"
                            :count="activeFiltersCount"
                            @toggle="showFilters = !showFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Advanced Filters Row (Expandable) -->
            <div
                v-if="showFilters"
                class="p-4 sm:p-5 bg-zinc-50/90 border-b border-zinc-200/90 dark:bg-zinc-900/90 dark:border-zinc-800"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                    <!-- Status Filter -->
                    <div>
                        <Select
                            v-model="status"
                            label="Status"
                            :options="statusOptions"
                            placeholder="All Statuses"
                        />
                    </div>

                    <!-- Plan Filter -->
                    <div>
                        <Select
                            v-model="plan"
                            label="Plan"
                            :options="planOptions"
                            placeholder="All Plans"
                        />
                    </div>

                    <!-- Billing Cycle Filter -->
                    <div>
                        <Select
                            v-model="billingCycle"
                            label="Billing Cycle"
                            :options="billingCycleOptions"
                            placeholder="All Cycles"
                        />
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="primary"
                            size="sm"
                            @click="applyFilters"
                        >
                            Apply Filters
                        </Button>
                        <Button
                            v-if="search || status || plan || billingCycle"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="clearFilters"
                        >
                            Clear
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Content Area: Empty State -->
            <div v-if="subscriptions.data.length === 0" class="p-12 text-center">
                <EmptyState
                    title="No Subscriptions Found"
                    description="There are no subscriptions matching your selected filters or search parameters."
                >
                    <template #actions>
                        <Button
                            v-if="search || status || plan || billingCycle"
                            variant="outline"
                            size="sm"
                            @click="clearFilters"
                        >
                            Clear Filters
                        </Button>
                        <Button
                            v-else
                            href="/superadmin/subscriptions/create"
                            variant="primary"
                            size="sm"
                        >
                            Create First Subscription
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <!-- Content Area: List View (Table) -->
            <div v-else-if="viewMode === 'list'" class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                    <thead class="border-b border-zinc-200 bg-zinc-100/75 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                        <tr>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Tenant / Organization</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Plan</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Status</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Billing Cycle</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Price</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Started At</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Period Start</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Period End</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Canceled At</th>
                            <th scope="col" class="h-11 px-4 text-right align-middle">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/80 dark:divide-zinc-800/80">
                        <tr
                            v-for="sub in subscriptions.data"
                            :key="sub.id"
                            class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40"
                        >
                            <!-- 1. Tenant / Organization -->
                            <td class="p-4 align-middle font-medium text-zinc-900 dark:text-zinc-100">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-semibold text-xs text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200/60 dark:border-zinc-700/60">
                                        {{ (sub.tenant?.name ?? 'T').substring(0, 2) }}
                                    </div>
                                    <div>
                                        <Link
                                            :href="`/superadmin/subscriptions/${sub.id}`"
                                            class="font-semibold text-zinc-900 hover:underline dark:text-white"
                                        >
                                            {{ sub.tenant?.name ?? `Tenant #${sub.tenant_id}` }}
                                        </Link>
                                        <div class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                                            {{ sub.public_id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Plan -->
                            <td class="px-3 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ sub.plan?.name ?? `Plan #${sub.plan_id}` }}
                            </td>

                            <!-- 3. Status -->
                            <td class="px-3 py-4 whitespace-nowrap">
                                <Badge
                                    :variant="getStatusBadgeVariant(sub.status)"
                                    :label="formatStatusLabel(sub.status)"
                                />
                            </td>

                            <!-- 4. Billing Cycle -->
                            <td class="px-3 py-4 text-xs font-medium text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                {{ formatBillingCycle(sub.plan?.billing_cycle) }}
                            </td>

                            <!-- 5. Price -->
                            <td class="px-3 py-4 font-semibold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                {{ formatAmount(sub.plan?.price, sub.plan?.currency) }}
                            </td>

                            <!-- 6. Started At -->
                            <td class="px-3 py-4 font-mono text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ formatDate(sub.starts_at) }}
                            </td>

                            <!-- 7. Current Period Start -->
                            <td class="px-3 py-4 font-mono text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ formatDate(sub.current_period_starts_at) }}
                            </td>

                            <!-- 8. Current Period End -->
                            <td class="px-3 py-4 font-mono text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ formatDate(sub.current_period_ends_at) }}
                            </td>

                            <!-- 9. Canceled At -->
                            <td class="px-3 py-4 font-mono text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                <span v-if="sub.canceled_at" class="text-rose-600 dark:text-rose-400">
                                    {{ formatDate(sub.canceled_at) }}
                                </span>
                                <span v-else class="text-zinc-400">—</span>
                            </td>

                            <!-- 10. Actions (Teleported Dropdown with colored icons, no clipping!) -->
                            <td class="p-4 align-middle text-right whitespace-nowrap">
                                <Dropdown align="right" width="w-48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors cursor-pointer"
                                            title="Actions"
                                        >
                                            <span class="sr-only">Open menu</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #default="{ close }">
                                        <div class="py-1 text-xs">
                                            <!-- View Details -->
                                            <Link
                                                :href="`/superadmin/subscriptions/${sub.id}`"
                                                class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                                @click="close"
                                            >
                                                <svg class="h-4 w-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>View Details</span>
                                            </Link>

                                            <!-- Cancel Subscription (if active or trialing) -->
                                            <button
                                                v-if="canCancel(sub)"
                                                type="button"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 transition-colors rounded-md cursor-pointer text-left"
                                                @click="subToCancel = sub; close()"
                                            >
                                                <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                                <span>Cancel Subscription</span>
                                            </button>

                                            <!-- Reactivate Subscription (if canceled but period not ended) -->
                                            <button
                                                v-if="canReactivate(sub)"
                                                type="button"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-950/40 transition-colors rounded-md cursor-pointer text-left"
                                                @click="subToReactivate = sub; close()"
                                            >
                                                <svg class="h-4 w-4 text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Reactivate Subscription</span>
                                            </button>
                                        </div>
                                    </template>
                                </Dropdown>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Content Area: Grid View (Accounting-style Cards) -->
            <div
                v-else-if="viewMode === 'grid'"
                class="p-5 bg-zinc-50/30 dark:bg-zinc-950/30"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <div
                        v-for="sub in subscriptions.data"
                        :key="sub.id"
                        class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 transition-all"
                    >
                        <!-- Top Info (Avatar + Tenant Name + Public ID) -->
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-bold text-sm text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700">
                                    {{ (sub.tenant?.name ?? 'T').substring(0, 2) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <Link
                                        :href="`/superadmin/subscriptions/${sub.id}`"
                                        class="font-semibold text-sm text-zinc-900 hover:underline dark:text-white truncate block"
                                        :title="sub.tenant?.name"
                                    >
                                        {{ sub.tenant?.name ?? `Tenant #${sub.tenant_id}` }}
                                    </Link>
                                    <span class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500 truncate block">
                                        {{ sub.public_id }}
                                    </span>
                                </div>
                            </div>

                            <!-- Middle Details -->
                            <div class="space-y-2 text-xs">
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Plan</span>
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100 truncate block">
                                        {{ sub.plan?.name ?? 'Custom Plan' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Cycle</span>
                                        <span class="text-zinc-800 dark:text-zinc-200 font-medium">
                                            {{ formatBillingCycle(sub.plan?.billing_cycle) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Price</span>
                                        <span class="text-zinc-900 dark:text-white font-semibold">
                                            {{ formatAmount(sub.plan?.price, sub.plan?.currency) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-1">
                                    <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] font-medium">Renews / Ends</span>
                                    <span class="font-mono text-zinc-700 dark:text-zinc-300">
                                        {{ formatDate(sub.current_period_ends_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer (Dashed Divider, Badges & Quick Action) -->
                        <div class="mt-4 pt-3 border-t border-dashed border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <!-- Status Badge -->
                            <Badge
                                :variant="getStatusBadgeVariant(sub.status)"
                                :label="formatStatusLabel(sub.status)"
                            />

                            <!-- Actions Menu -->
                            <Dropdown align="right" width="w-48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white transition-colors cursor-pointer"
                                        title="Actions"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </template>

                                <template #default="{ close }">
                                    <div class="py-1 text-xs">
                                        <Link
                                            :href="`/superadmin/subscriptions/${sub.id}`"
                                            class="flex items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md"
                                            @click="close"
                                        >
                                            <svg class="h-4 w-4 text-zinc-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>View Details</span>
                                        </Link>

                                        <button
                                            v-if="canCancel(sub)"
                                            type="button"
                                            class="w-full flex items-center gap-2.5 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 transition-colors rounded-md cursor-pointer text-left"
                                            @click="subToCancel = sub; close()"
                                        >
                                            <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <span>Cancel Subscription</span>
                                        </button>

                                        <button
                                            v-if="canReactivate(sub)"
                                            type="button"
                                            class="w-full flex items-center gap-2.5 px-3 py-2 text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-950/40 transition-colors rounded-md cursor-pointer text-left"
                                            @click="subToReactivate = sub; close()"
                                        >
                                            <svg class="h-4 w-4 text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>Reactivate Subscription</span>
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Footer Pagination (Inside the card container) -->
            <div
                v-if="subscriptions.total > 0"
                class="border-t border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
            >
                <Pagination :data="subscriptions" />
            </div>
        </div>

        <!-- Cancel Confirmation Modal -->
        <Modal
            :show="!!subToCancel"
            title="Cancel Subscription"
            description="Are you sure you want to cancel this tenant subscription?"
            max-width="md"
            @close="subToCancel = null"
        >
            <div v-if="subToCancel" class="space-y-3 py-2 text-sm text-zinc-600 dark:text-zinc-300">
                <p>
                    Are you sure you want to cancel the subscription for
                    <strong class="text-zinc-900 dark:text-white">{{ subToCancel.tenant?.name ?? `Tenant #${subToCancel.tenant_id}` }}</strong>?
                </p>
                <div class="rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                    <div class="flex justify-between py-1">
                        <span class="text-zinc-500">Plan:</span>
                        <span class="font-medium text-zinc-900 dark:text-white">{{ subToCancel.plan?.name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-t border-zinc-200/60 dark:border-zinc-700/60">
                        <span class="text-zinc-500">Access Continues Until:</span>
                        <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ formatDate(subToCancel.current_period_ends_at) }}</span>
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
                        @click="subToCancel = null"
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

        <!-- Reactivate Confirmation Modal -->
        <Modal
            :show="!!subToReactivate"
            title="Reactivate Subscription"
            description="Are you sure you want to reactivate this subscription?"
            max-width="md"
            @close="subToReactivate = null"
        >
            <div v-if="subToReactivate" class="space-y-3 py-2 text-sm text-zinc-600 dark:text-zinc-300">
                <p>
                    This will reactivate the subscription for
                    <strong class="text-zinc-900 dark:text-white">{{ subToReactivate.tenant?.name ?? `Tenant #${subToReactivate.tenant_id}` }}</strong>
                    and restore automated renewals.
                </p>
                <div class="rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                    <div class="flex justify-between py-1">
                        <span class="text-zinc-500">Plan:</span>
                        <span class="font-medium text-zinc-900 dark:text-white">{{ subToReactivate.plan?.name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-t border-zinc-200/60 dark:border-zinc-700/60">
                        <span class="text-zinc-500">Current Period Ends:</span>
                        <span class="font-mono font-medium text-zinc-900 dark:text-white">{{ formatDate(subToReactivate.current_period_ends_at) }}</span>
                    </div>
                </div>
                <p class="text-xs text-zinc-500">
                    The scheduled cancellation timestamp will be cleared and the subscription will continue regular renewals.
                </p>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="isReactivating"
                        @click="subToReactivate = null"
                    >
                        <span>Cancel</span>
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        :loading="isReactivating"
                        @click="confirmReactivate"
                    >
                        <span>Reactivate Subscription</span>
                    </Button>
                </div>
            </template>
        </Modal>
    </SuperAdminLayout>
</template>
