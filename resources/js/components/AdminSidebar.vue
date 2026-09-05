<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps<{
    mobileOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:mobileOpen', value: boolean): void;
    (e: 'closeMobile'): void;
}>();

const page = usePage();
const currentUrl = computed(() => page.url);

const isRouteActive = (pattern: string) => {
    if (pattern === '/superadmin/dashboard') {
        return currentUrl.value === '/superadmin/dashboard' || currentUrl.value === '/superadmin';
    }
    return currentUrl.value.startsWith(pattern);
};

const platform = computed(() => (page.props.platform as any) ?? {
    name: 'SathiSaaS',
    logo_url: null,
    favicon_url: null,
});

const user = computed(() => (page.props.auth as any)?.user ?? {
    name: 'Super Admin',
    email: 'admin@sathisaas.com',
});

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div>
        <!-- Mobile Backdrop -->
        <Transition
            enter-active-class="transition-opacity ease-linear duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-40 bg-zinc-900/60 backdrop-blur-xs lg:hidden"
                @click="emit('closeMobile')"
            />
        </Transition>

        <!-- Sidebar Container (Fixed on Desktop, Slide-over on Mobile) -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-zinc-200 bg-white transition-transform duration-200 ease-in-out dark:border-zinc-800 dark:bg-zinc-900 lg:translate-x-0',
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Brand Header -->
            <div class="flex h-16 shrink-0 items-center justify-between px-5 border-b border-zinc-200 dark:border-zinc-800">
                <Link href="/superadmin/dashboard" class="flex items-center gap-3 group min-w-0" @click="emit('closeMobile')">
                    <!-- Dynamic Platform Logo -->
                    <div
                        v-if="platform.logo_url"
                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200/80 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-950 shadow-xs"
                    >
                        <img
                            :src="platform.logo_url"
                            :alt="platform.name || 'Platform Logo'"
                            class="h-full w-full object-contain"
                        />
                    </div>
                    <div
                        v-else
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white font-bold tracking-wider shadow-xs shadow-primary-500/25"
                    >
                        {{ (platform.name || 'S').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-sm tracking-tight text-zinc-900 dark:text-white truncate" :title="platform.name || 'SathiSaaS'">
                                {{ platform.name || 'SathiSaaS' }}
                            </span>
                            <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[9px] font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 shrink-0">SUPERADMIN</span>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">Platform Management</p>
                    </div>
                </Link>

                <!-- Close Button (Mobile Only) -->
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 lg:hidden"
                    @click="emit('closeMobile')"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex flex-1 flex-col overflow-y-auto px-4 py-6">
                <!-- Section: Overview -->
                <div class="space-y-1">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        Overview
                    </p>

                    <Link
                        href="/superadmin/dashboard"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/dashboard')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </Link>

                    <Link
                        href="/superadmin/analytics"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/analytics')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Analytics</span>
                    </Link>
                </div>

                <!-- Section: Multi-Tenancy Management -->
                <div class="mt-7 space-y-1">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        Tenant Management
                    </p>

                    <Link
                        href="/superadmin/tenants"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/tenants')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>Organizations</span>
                    </Link>

                    <Link
                        href="/superadmin/tenants/create"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            currentUrl === '/superadmin/tenants/create'
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 font-semibold'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Organization</span>
                    </Link>
                </div>

                <!-- Section: User Management -->
                <div class="mt-7 space-y-1">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        User Management
                    </p>

                    <Link
                        href="/superadmin/users"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/users')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Users</span>
                    </Link>
                    <Link
                        href="/superadmin/roles"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/roles')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Roles</span>
                    </Link>
                </div>

                <!-- Section: Subscriptions & Plans -->
                <div class="mt-7 space-y-1">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        Subscriptions
                    </p>

                    <Link
                        href="/superadmin/subscriptions"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/subscriptions') && !isRouteActive('/superadmin/subscriptions/plans')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Subscriptions</span>
                    </Link>

                    <Link
                        href="/superadmin/subscriptions/plans"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/subscriptions/plans')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Subscription Plans</span>
                    </Link>
                </div>

                <!-- Section: Billing & Payments -->
                <div class="mt-7 space-y-1">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        Billing & Payments
                    </p>

                    <Link
                        href="/superadmin/payments"
                        :class="[
                            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/payments')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Payments</span>
                    </Link>
                </div>

                <!-- Section: Content & Media -->
                <div class="mt-7 space-y-1">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        Content & Assets
                    </p>

                    <Link
                        href="/superadmin/media"
                        :class="[
                            'group flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/media')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Media Library</span>
                        </div>
                    </Link>
                </div>

                <!-- Section: System & Audit -->
                <div class="mt-7 space-y-1">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        System & Security
                    </p>

                    <Link
                        href="/superadmin/security"
                        :class="[
                            'group flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/security')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Security Center</span>
                        </div>
                        <span
                            :class="[
                                'rounded px-1.5 py-0.5 text-[10px]',
                                isRouteActive('/superadmin/security')
                                    ? 'bg-white/20 text-white'
                                    : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                            ]"
                        >
                            Active
                        </span>
                    </Link>

                    <Link
                        href="/superadmin/audit-logs"
                        :class="[
                            'group flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/audit-logs')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Audit Logs</span>
                        </div>
                        <span
                            :class="[
                                'rounded px-1.5 py-0.5 text-[10px]',
                                isRouteActive('/superadmin/audit-logs')
                                    ? 'bg-white/20 text-white'
                                    : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                            ]"
                        >
                            Core
                        </span>
                    </Link>

                    <Link
                        href="/superadmin/settings"
                        :class="[
                            'group flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-all',
                            isRouteActive('/superadmin/settings') || isRouteActive('/admin/settings')
                                ? 'bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
                        ]"
                        @click="emit('closeMobile')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Platform Settings</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- User Footer Profile Card -->
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                <div class="flex items-center justify-between rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/60">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white shadow-xs shadow-primary-500/25">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="truncate">
                            <p class="truncate text-xs font-semibold text-zinc-900 dark:text-white">
                                {{ user.name }}
                            </p>
                            <p class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-200/60 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                        title="Sign Out"
                        @click="logout"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>
