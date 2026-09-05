<script setup lang="ts">
import { computed, ref } from 'vue';
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

const openGroups = ref<Record<string, boolean>>({
    tenants: currentUrl.value.startsWith('/superadmin/tenants'),
    users: currentUrl.value.startsWith('/superadmin/users') || currentUrl.value.startsWith('/superadmin/roles'),
    subscriptions: currentUrl.value.startsWith('/superadmin/subscriptions'),
    cms: false,
    qa: false,
    security: currentUrl.value.startsWith('/superadmin/security') || currentUrl.value.startsWith('/superadmin/audit-logs'),
});

const toggleGroup = (key: string) => {
    openGroups.value[key] = !openGroups.value[key];
};

const isGroupActive = (paths: string[]) => {
    return paths.some((p) => isRouteActive(p));
};

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
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold tracking-wider shadow-xs shadow-indigo-500/25"
                    >
                        {{ (platform.name || 'S').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-sm tracking-tight text-zinc-900 dark:text-white truncate" :title="platform.name || 'SathiSaaS'">
                                {{ platform.name || 'SathiSaaS' }}
                            </span>
                            <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 shrink-0">SUPERADMIN</span>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">Platform Control Plane</p>
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

            <!-- Navigation Links (Collapsible Accordion matching screenshot) -->
            <div class="flex flex-1 flex-col overflow-y-auto px-3.5 py-4 space-y-1">
                <!-- 1. Dashboard (Active with Amber Left Vertical Accent Bar) -->
                <Link
                    href="/superadmin/dashboard"
                    :class="[
                        'group flex items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-all',
                        isRouteActive('/superadmin/dashboard')
                            ? 'border-l-[3.5px] border-amber-500 bg-[#edf2f7] font-semibold text-slate-900 dark:border-amber-400 dark:bg-zinc-800/90 dark:text-white rounded-l-none rounded-r-lg'
                            : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                    ]"
                    @click="emit('closeMobile')"
                >
                    <div class="flex items-center gap-3">
                        <svg
                            class="h-4 w-4 shrink-0 transition-colors"
                            :class="isRouteActive('/superadmin/dashboard') ? 'text-slate-800 dark:text-zinc-200' : 'text-slate-500 dark:text-zinc-400'"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </Link>

                <!-- 2. Organizations / Tenants (Collapsible) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.tenants || isGroupActive(['/superadmin/tenants'])
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('tenants')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span>Organizations</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.tenants }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform -translate-y-1 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-1 opacity-0"
                    >
                        <div v-if="openGroups.tenants" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <Link
                                href="/superadmin/tenants"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/tenants') && currentUrl !== '/superadmin/tenants/create'
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                All Organizations
                            </Link>
                            <Link
                                href="/superadmin/tenants/create"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    currentUrl === '/superadmin/tenants/create'
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                + New Organization
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- 3. Users (Collapsible) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.users || isGroupActive(['/superadmin/users', '/superadmin/roles'])
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('users')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Users</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.users }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform -translate-y-1 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-1 opacity-0"
                    >
                        <div v-if="openGroups.users" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <Link
                                href="/superadmin/users"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/users')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                User Directory
                            </Link>
                            <Link
                                href="/superadmin/roles"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/roles')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                Roles & Permissions
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- 4. Demo Requests (Direct Link) -->
                <div class="flex items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white transition-colors cursor-pointer">
                    <div class="flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        <span>Demo Requests</span>
                    </div>
                    <span class="rounded bg-amber-100 px-1.5 py-0.2 text-[10px] font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">New</span>
                </div>

                <!-- 5. Subscription (Collapsible - Exactly Matching Screenshot) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.subscriptions || isGroupActive(['/superadmin/subscriptions'])
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('subscriptions')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span>Subscription</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.subscriptions }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform -translate-y-1 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-1 opacity-0"
                    >
                        <div v-if="openGroups.subscriptions" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <Link
                                href="/superadmin/subscriptions"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/subscriptions') && !isRouteActive('/superadmin/subscriptions/plans')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                Subscription Setting
                            </Link>
                            <Link
                                href="/superadmin/subscriptions/plans"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/subscriptions/plans')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                Coupons
                            </Link>
                            <div class="block rounded-md px-2.5 py-1.5 text-xs text-slate-400 hover:bg-slate-50 dark:text-zinc-500 cursor-pointer">
                                Bank Transfer Requests
                            </div>
                            <Link
                                href="/superadmin/payments"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/payments')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                Orders
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- 6. CMS (Collapsible - As in Screenshot) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.cms
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('cms')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                            <span>CMS</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.cms }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform -translate-y-1 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-1 opacity-0"
                    >
                        <div v-if="openGroups.cms" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <div class="block rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                Landing Pages
                            </div>
                            <div class="block rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                Blog & Articles
                            </div>
                            <div class="block rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                Navigation Menus
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- 7. Email Templates (Direct Link) -->
                <Link
                    href="/superadmin/settings"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white transition-colors"
                    @click="emit('closeMobile')"
                >
                    <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Email Templates</span>
                </Link>

                <!-- 8. Notification Templates (Direct Link) -->
                <div class="flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white transition-colors cursor-pointer">
                    <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span>Notification Templates</span>
                </div>

                <!-- 9. QA & Testing (Collapsible - As in Screenshot) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.qa
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('qa')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            <span>QA & Testing</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.qa }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform -translate-y-1 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-1 opacity-0"
                    >
                        <div v-if="openGroups.qa" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <div class="block rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                Health Checks
                            </div>
                            <div class="block rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                Mock Data Generator
                            </div>
                            <div class="block rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                API Sandbox
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- 10. Media Library (Direct Link) -->
                <Link
                    href="/superadmin/media"
                    :class="[
                        'group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors',
                        isRouteActive('/superadmin/media')
                            ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                            : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                    ]"
                    @click="emit('closeMobile')"
                >
                    <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Media Library</span>
                </Link>

                <!-- 11. Security & Analytics (Collapsible) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.security || isGroupActive(['/superadmin/security', '/superadmin/audit-logs', '/superadmin/analytics'])
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('security')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Security & Logs</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.security }"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform -translate-y-1 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-1 opacity-0"
                    >
                        <div v-if="openGroups.security" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <Link
                                href="/superadmin/security"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/security')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                Security Center
                            </Link>
                            <Link
                                href="/superadmin/audit-logs"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/audit-logs')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                Audit Logs
                            </Link>
                            <Link
                                href="/superadmin/analytics"
                                :class="[
                                    'block rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/superadmin/analytics')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                Analytics
                            </Link>
                        </div>
                    </Transition>
                </div>

                <!-- 12. Settings (Direct Link) -->
                <Link
                    href="/superadmin/settings"
                    :class="[
                        'group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors',
                        isRouteActive('/superadmin/settings')
                            ? 'border-l-[3.5px] border-amber-500 bg-[#edf2f7] font-semibold text-slate-900 dark:border-amber-400 dark:bg-zinc-800/90 dark:text-white rounded-l-none rounded-r-lg'
                            : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                    ]"
                    @click="emit('closeMobile')"
                >
                    <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Settings</span>
                </Link>
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
