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
    if (pattern === '/admin/dashboard') {
        return currentUrl.value === '/admin/dashboard' || currentUrl.value === '/admin';
    }
    return currentUrl.value.startsWith(pattern);
};

const user = computed(() => (page.props.auth as any)?.user ?? {
    name: 'Admin User',
    email: 'admin@example.com',
});

const currentTenant = computed(() => (page.props.current_tenant as any) ?? {
    id: null,
    name: 'Organization',
    slug: 'org',
    status: 'active',
});

const userTenants = computed<Array<{ id: number; public_id: string; name: string; slug: string }>>(() => {
    return (page.props.user_tenants as any) ?? [];
});

const impersonation = computed(() => (page.props.impersonation as any) ?? null);

const switcherOpen = ref(false);
const isSwitching = ref(false);

const switchTenant = (tenantId: number) => {
    if (currentTenant.value?.id === tenantId) {
        switcherOpen.value = false;
        return;
    }

    isSwitching.value = true;
    switcherOpen.value = false;
    router.post(`/admin/tenant/switch/${tenantId}`, {}, {
        onFinish: () => {
            isSwitching.value = false;
        },
    });
};

const exitImpersonation = () => {
    router.post('/admin/impersonate/exit');
};

const openGroups = ref<Record<string, boolean>>({
    organization: currentUrl.value.startsWith('/admin/branches'),
    users: currentUrl.value.startsWith('/admin/members'),
    hrm: false,
    payroll: false,
    subscriptions: false,
    settings: false,
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

        <!-- Sidebar Container -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-zinc-200 bg-white transition-transform duration-200 ease-in-out dark:border-zinc-800 dark:bg-zinc-900 lg:translate-x-0',
                mobileOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Organization Switcher / Brand Header -->
            <div class="relative flex h-16 shrink-0 items-center justify-between border-b border-zinc-200 px-4 dark:border-zinc-800">
                <div class="relative min-w-0 flex-1">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-lg p-1 text-left transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800/70"
                        @click="switcherOpen = !switcherOpen"
                    >
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 font-bold text-white shadow-xs shadow-indigo-500/25">
                            {{ (currentTenant.name || 'O').charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="truncate text-sm font-bold text-zinc-900 dark:text-white" :title="currentTenant.name">
                                    {{ currentTenant.name }}
                                </span>
                                <svg
                                    v-if="userTenants.length > 1"
                                    class="h-3.5 w-3.5 shrink-0 text-zinc-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <p class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">
                                {{ currentTenant.slug || 'Organization' }}
                            </p>
                        </div>
                    </button>

                    <!-- Organization Switcher Dropdown -->
                    <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div
                            v-if="switcherOpen"
                            class="absolute left-0 top-14 z-50 w-64 rounded-xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
                        >
                            <div class="px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                Organizations
                            </div>

                            <div class="max-h-60 space-y-1 overflow-y-auto py-1">
                                <button
                                    v-for="tenant in userTenants"
                                    :key="tenant.id"
                                    type="button"
                                    :disabled="isSwitching || tenant.id === currentTenant.id"
                                    :class="[
                                        'flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-left text-xs font-medium transition-colors',
                                        tenant.id === currentTenant.id
                                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'
                                            : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                                    ]"
                                    @click="switchTenant(tenant.id)"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-zinc-200 text-[10px] font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ tenant.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="truncate">{{ tenant.name }}</span>
                                    </div>
                                    <svg
                                        v-if="tenant.id === currentTenant.id"
                                        class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>

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

            <!-- Navigation Links (Accordion / Collapsible Sidebar) -->
            <div class="flex flex-1 flex-col overflow-y-auto px-3.5 py-4 space-y-1">
                <!-- 1. Dashboard (Direct Link with Amber Left Vertical Accent Bar) -->
                <Link
                    href="/admin/dashboard"
                    :class="[
                        'group flex items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-all',
                        isRouteActive('/admin/dashboard')
                            ? 'border-l-[3.5px] border-amber-500 bg-[#edf2f7] font-semibold text-slate-900 dark:border-amber-400 dark:bg-zinc-800/90 dark:text-white rounded-l-none rounded-r-lg'
                            : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                    ]"
                    @click="emit('closeMobile')"
                >
                    <div class="flex items-center gap-3">
                        <svg
                            class="h-4 w-4 shrink-0 transition-colors"
                            :class="isRouteActive('/admin/dashboard') ? 'text-slate-800 dark:text-zinc-200' : 'text-slate-500 dark:text-zinc-400'"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </Link>

                <!-- 2. Organization (Collapsible Accordion) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.organization || isGroupActive(['/admin/branches'])
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('organization')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span>Organization</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.organization }"
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
                        <div v-if="openGroups.organization" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <Link
                                href="/admin/branches"
                                :class="[
                                    'flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/admin/branches')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                <span>Branches</span>
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                            </Link>

                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-400 dark:text-zinc-500 cursor-not-allowed select-none">
                                <span>Departments</span>
                                <span class="text-[9px] text-zinc-400 font-medium">Soon</span>
                            </div>

                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-400 dark:text-zinc-500 cursor-not-allowed select-none">
                                <span>Company Profile</span>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- 3. Users & Access (Collapsible Accordion) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.users || isGroupActive(['/admin/members'])
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
                                href="/admin/members"
                                :class="[
                                    'flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs transition-colors',
                                    isRouteActive('/admin/members')
                                        ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/60 dark:text-indigo-300'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white',
                                ]"
                                @click="emit('closeMobile')"
                            >
                                <span>Members</span>
                            </Link>

                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-400 dark:text-zinc-500 cursor-not-allowed select-none">
                                <span>Roles & Permissions</span>
                            </div>

                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-400 dark:text-zinc-500 cursor-not-allowed select-none">
                                <span>Invitations</span>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- 4. HRM (Collapsible Accordion) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.hrm
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('hrm')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>HRM</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.hrm }"
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
                        <div v-if="openGroups.hrm" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Employees</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Attendance</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Leave Requests</span>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- 5. Payroll (Collapsible Accordion) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.payroll
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('payroll')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Payroll</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.payroll }"
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
                        <div v-if="openGroups.payroll" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Payroll Runs</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Salary Slips</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Expenses</span>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- 6. Subscriptions (Collapsible Accordion matching screenshot) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.subscriptions
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
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Subscription Setting</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Coupons</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Bank Transfer Requests</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-zinc-400 cursor-pointer">
                                <span>Orders</span>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- 7. Settings (Collapsible Accordion) -->
                <div>
                    <button
                        type="button"
                        :class="[
                            'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer',
                            openGroups.settings
                                ? 'bg-slate-100/80 text-slate-900 font-semibold dark:bg-zinc-800/80 dark:text-white'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-300 dark:hover:bg-zinc-800/70 dark:hover:text-white',
                        ]"
                        @click="toggleGroup('settings')"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Settings</span>
                        </div>
                        <svg
                            class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200"
                            :class="{ 'rotate-180': openGroups.settings }"
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
                        <div v-if="openGroups.settings" class="ml-5 mt-1 border-l border-zinc-200/90 pl-3.5 space-y-0.5 dark:border-zinc-800">
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-400 dark:text-zinc-500 cursor-not-allowed select-none">
                                <span>Organization Settings</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-xs text-slate-400 dark:text-zinc-500 cursor-not-allowed select-none">
                                <span>Security & Audit Logs</span>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Impersonation Notice Box in Sidebar -->
                <div v-if="impersonation" class="mt-auto pt-6">
                    <div class="rounded-xl border border-amber-300/80 bg-amber-50/90 p-3 text-xs dark:border-amber-700/60 dark:bg-amber-950/40">
                        <div class="flex items-center gap-2 font-semibold text-amber-900 dark:text-amber-200">
                            <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>SuperAdmin Mode</span>
                        </div>
                        <p class="mt-1 text-[11px] text-amber-800 dark:text-amber-300">
                            You are viewing this organization as SuperAdmin.
                        </p>
                        <button
                            type="button"
                            class="mt-2.5 flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-2.5 py-1.5 text-xs font-medium text-white shadow-xs hover:bg-amber-700 transition-colors"
                            @click="exitImpersonation"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Exit Admin Mode</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- User Profile Card -->
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                <div class="flex items-center justify-between rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/60">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shadow-xs shadow-indigo-500/25">
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
