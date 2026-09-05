<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import StatsCard from '@/components/StatsCard.vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';

interface Tenant {
    public_id: string;
    name: string;
    slug: string;
    industry: string | null;
    status: string;
    country_code: string | null;
    timezone: string;
    locale: string;
    currency: string;
}

interface Members {
    total: number;
    active: number;
    invited: number;
    suspended: number;
    revoked: number;
}

interface Subscription {
    exists: boolean;
    status: string | null;
    plan: string | null;
    current_period_ends_at: string | null;
    trial_ends_at: string | null;
}

interface OrganizationOption {
    id: number;
    public_id: string;
    name: string;
    slug: string;
}

const props = defineProps<{
    tenant: Tenant;
    members: Members;
    subscription: Subscription;
    organizations?: OrganizationOption[];
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const statusColor = computed(() => {
    switch (props.tenant.status.toLowerCase()) {
        case 'active':
            return 'success';
        case 'suspended':
            return 'danger';
        case 'pending':
            return 'warning';
        default:
            return 'neutral';
    }
});

const subscriptionStatusColor = computed(() => {
    switch (props.subscription.status?.toLowerCase()) {
        case 'active':
            return 'success';
        case 'trialing':
            return 'info';
        case 'past_due':
        case 'cancelled':
            return 'danger';
        default:
            return 'neutral';
    }
});
</script>

<template>
    <OrganizationLayout
        :title="`${tenant.name} - Dashboard`"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Dashboard' },
        ]"
    >
        <Head :title="`${tenant.name} - Organization Dashboard`" />

        <div class="space-y-6">
            <!-- Organization Hero / Welcome Header -->
            <div class="flex flex-col justify-between gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 md:flex-row md:items-center">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white shadow-md shadow-indigo-500/20">
                        {{ tenant.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                {{ tenant.name }}
                            </h1>
                            <Badge :variant="statusColor" class="capitalize">
                                {{ tenant.status }}
                            </Badge>
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            Organization Workspace &bull; <span class="font-mono text-zinc-600 dark:text-zinc-300">{{ tenant.slug }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/members"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs shadow-indigo-500/25 hover:bg-indigo-700 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Manage Members</span>
                    </Link>
                </div>
            </div>

            <!-- Metric Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Members</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ members.total }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">All registered tenant seats</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active Members</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ members.active }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Currently active accounts</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-amber-500 dark:text-amber-400">Pending Invites</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ members.invited }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Awaiting user confirmation</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-rose-500 dark:text-rose-400">Suspended</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ members.suspended }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Disabled or locked seats</p>
                    </div>
                </div>
            </div>

            <!-- Detailed Section: Organization Profile & Subscription Information -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Organization Profile Card -->
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Organization Profile</h2>
                        </div>
                        <Badge :variant="statusColor" class="capitalize">{{ tenant.status }}</Badge>
                    </div>

                    <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Organization Name</dt>
                            <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ tenant.name }}</dd>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Workspace Slug</dt>
                            <dd class="mt-1 font-mono font-semibold text-zinc-900 dark:text-white">{{ tenant.slug }}</dd>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Industry</dt>
                            <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ tenant.industry || 'Not specified' }}</dd>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Currency</dt>
                            <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ tenant.currency }}</dd>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Timezone</dt>
                            <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ tenant.timezone }}</dd>
                        </div>

                        <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Locale / Language</dt>
                            <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ tenant.locale }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Subscription Status Card -->
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Subscription & Plan</h2>
                        </div>
                        <Badge
                            v-if="subscription.exists && subscription.status"
                            :variant="subscriptionStatusColor"
                            class="capitalize"
                        >
                            {{ subscription.status }}
                        </Badge>
                        <Badge v-else variant="neutral">No Subscription</Badge>
                    </div>

                    <div v-if="subscription.exists" class="mt-5 space-y-4">
                        <div class="rounded-xl bg-indigo-50/60 p-4 border border-indigo-100 dark:bg-indigo-950/40 dark:border-indigo-900/60">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Current Plan</span>
                            <p class="mt-1 text-xl font-bold text-zinc-900 dark:text-white">
                                {{ subscription.plan || 'Standard Plan' }}
                            </p>
                        </div>

                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-xs">
                            <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Current Period Ends</dt>
                                <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">
                                    {{ subscription.current_period_ends_at ? new Date(subscription.current_period_ends_at).toLocaleDateString() : 'Continuous' }}
                                </dd>
                            </div>

                            <div v-if="subscription.trial_ends_at" class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Trial Ends</dt>
                                <dd class="mt-1 font-semibold text-amber-600 dark:text-amber-400">
                                    {{ new Date(subscription.trial_ends_at).toLocaleDateString() }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div v-else class="mt-8 flex flex-col items-center justify-center py-6 text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">No active plan assigned</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Please contact platform administrators for subscription details.</p>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
