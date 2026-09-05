<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import StatsCard from '@/components/StatsCard.vue';
import Badge from '@/components/Badge.vue';
import EmptyState from '@/components/EmptyState.vue';
import type { Tenant } from '@/types/tenancy';

interface DashboardStats {
    totalTenants: number;
    activeTenants: number;
    pendingTenants: number;
    suspendedTenants: number;
    totalUsers: number;
}

interface AuditItem {
    id: number;
    event: string;
    description?: string;
    created_at: string;
    actor?: {
        name: string;
        email: string;
    };
    tenant?: {
        name: string;
    };
}

const props = defineProps<{
    stats?: DashboardStats;
    recentTenants?: Tenant[];
    recentAudits?: AuditItem[];
}>();

const formatDate = (dateStr?: string) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <SuperAdminLayout
        title="Super Admin Dashboard"
        :breadcrumbs="[{ label: 'Dashboard' }]"
    >
        <!-- Top Hero Welcome Banner -->
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-semibold text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        SathiSaaS Platform Engine
                    </div>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Platform Administration
                    </h1>
                    <p class="mt-1.5 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
                        Real-time overview of tenant organizations, user memberships, platform provisioning, and system events.
                    </p>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <Link
                        href="/superadmin/tenants/create"
                        class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white shadow-xs transition-colors hover:bg-zinc-800 focus:outline-hidden focus:ring-2 focus:ring-zinc-900 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Organization</span>
                    </Link>

                    <Link
                        href="/superadmin/tenants"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 shadow-2xs transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>View Organizations</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Tenants -->
            <StatsCard
                title="Total Organizations"
                :value="stats?.totalTenants ?? 0"
                subtitle="Total registered workspaces"
                badge-color="zinc"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </template>
            </StatsCard>

            <!-- Active Tenants -->
            <StatsCard
                title="Active Organizations"
                :value="stats?.activeTenants ?? 0"
                subtitle="Live operating tenants"
                badge-color="emerald"
                trend="up"
                trend-value="Operating"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- Pending Approvals -->
            <StatsCard
                title="Pending Workspaces"
                :value="stats?.pendingTenants ?? 0"
                subtitle="Awaiting onboarding"
                badge-color="amber"
                :trend="(stats?.pendingTenants ?? 0) > 0 ? 'neutral' : undefined"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- Total Users -->
            <StatsCard
                title="Platform Users"
                :value="stats?.totalUsers ?? 0"
                subtitle="Total registered accounts"
                badge-color="blue"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </template>
            </StatsCard>
        </div>

        <!-- 2-Column Responsive Body -->
        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Left 2 Cols: Recent Organizations Table -->
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-zinc-200 p-5 sm:p-6 dark:border-zinc-800">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Recent Organizations
                            </h2>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Recently registered tenant accounts on SathiSaaS.
                            </p>
                        </div>

                        <Link
                            href="/superadmin/tenants"
                            class="text-xs font-semibold text-zinc-900 hover:underline dark:text-white"
                        >
                            View All →
                        </Link>
                    </div>

                    <!-- Table -->
                    <div v-if="recentTenants && recentTenants.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">Organization</th>
                                    <th scope="col" class="px-6 py-3.5">Status</th>
                                    <th scope="col" class="px-6 py-3.5">Country</th>
                                    <th scope="col" class="px-6 py-3.5">Created</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                <tr
                                    v-for="tenant in recentTenants"
                                    :key="tenant.id"
                                    class="transition-colors hover:bg-zinc-50/75 dark:hover:bg-zinc-800/40"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-semibold text-xs text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200">
                                                {{ tenant.name.substring(0, 2) }}
                                            </div>
                                            <div>
                                                <Link
                                                    :href="`/superadmin/tenants/${tenant.id}`"
                                                    class="font-semibold text-zinc-900 hover:underline dark:text-zinc-100"
                                                >
                                                    {{ tenant.name }}
                                                </Link>
                                                <div class="font-mono text-xs text-zinc-400 dark:text-zinc-500">
                                                    {{ tenant.slug }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge :variant="tenant.status" />
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs uppercase text-zinc-600 dark:text-zinc-400">
                                        {{ tenant.country_code || '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ formatDate(tenant.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link
                                            :href="`/superadmin/tenants/${tenant.id}`"
                                            class="rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-50 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="p-8">
                        <EmptyState
                            title="No organizations provisioned"
                            description="Get started by registering the first tenant on the platform."
                        >
                            <template #actions>
                                <Link
                                    href="/superadmin/tenants/create"
                                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-xs font-medium text-white shadow-xs hover:bg-zinc-800 dark:bg-white dark:text-zinc-900"
                                >
                                    Create Organization
                                </Link>
                            </template>
                        </EmptyState>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Platform Health & System Activity -->
            <div class="space-y-6">
                <!-- System Architecture Health -->
                <div class="rounded-xl border border-zinc-200 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                        Platform Architecture
                    </h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Core engines and module integration status.
                    </p>

                    <div class="mt-5 space-y-3.5">
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60">
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-emerald-500" />
                                <div>
                                    <p class="text-xs font-semibold text-zinc-900 dark:text-white">Tenancy Engine</p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Multi-tenant boundary</p>
                                </div>
                            </div>
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Active
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60">
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-emerald-500" />
                                <div>
                                    <p class="text-xs font-semibold text-zinc-900 dark:text-white">RBAC & Permissions</p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Spatie SuperAdmin</p>
                                </div>
                            </div>
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Active
                            </span>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60">
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-emerald-500" />
                                <div>
                                    <p class="text-xs font-semibold text-zinc-900 dark:text-white">Audit Engine</p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400">System audit trails</p>
                                </div>
                            </div>
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Active
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="rounded-xl border border-zinc-200 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                            Recent Platform Events
                        </h3>
                    </div>

                    <div v-if="recentAudits && recentAudits.length > 0" class="mt-4 space-y-4">
                        <div
                            v-for="item in recentAudits"
                            :key="item.id"
                            class="flex items-start gap-3 text-xs"
                        >
                            <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 overflow-hidden">
                                <p class="font-medium text-zinc-900 dark:text-white truncate">
                                    {{ item.event }}
                                </p>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">
                                    {{ item.actor?.name || 'System' }} • {{ formatDate(item.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-4 py-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        No recent platform events recorded.
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>