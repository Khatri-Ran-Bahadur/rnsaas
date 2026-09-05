<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ThemeToggle from '@/components/ThemeToggle.vue';

defineProps<{
    breadcrumbs?: Array<{ label: string; href?: string }>;
}>();

const emit = defineEmits<{
    (e: 'openMobile'): void;
}>();

const page = usePage();
const userDropdownOpen = ref(false);
const userDropdownRef = ref<HTMLElement | null>(null);

const user = computed(() => (page.props.auth as any)?.user ?? {
    name: 'Admin User',
    email: 'admin@example.com',
});

const currentTenant = computed(() => (page.props.current_tenant as any) ?? {
    name: 'Organization',
    slug: 'org',
});

const impersonation = computed(() => (page.props.impersonation as any) ?? null);

const handleClickOutside = (event: MouseEvent) => {
    if (userDropdownRef.value && !userDropdownRef.value.contains(event.target as Node)) {
        userDropdownOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', handleClickOutside);
});

const exitImpersonation = () => {
    router.post('/admin/impersonate/exit');
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-zinc-200 bg-white/90 px-4 sm:px-8 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/90">
        <!-- Left: Mobile Toggle & Breadcrumbs -->
        <div class="flex items-center gap-4">
            <!-- Mobile Menu Toggle Button -->
            <button
                type="button"
                class="rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 lg:hidden"
                title="Open navigation menu"
                @click="emit('openMobile')"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Breadcrumbs -->
            <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="hidden sm:flex items-center space-x-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
                    <span v-if="idx > 0" class="text-zinc-300 dark:text-zinc-600">/</span>
                    <Link
                        v-if="crumb.href"
                        :href="crumb.href"
                        class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                    >
                        {{ crumb.label }}
                    </Link>
                    <span v-else class="text-zinc-900 dark:text-white font-semibold">
                        {{ crumb.label }}
                    </span>
                </template>
            </nav>
        </div>

        <!-- Right: Impersonation badge, Theme Toggle & Profile Dropdown -->
        <div class="flex items-center gap-3">
            <!-- Impersonation indicator (Header Pill) -->
            <div
                v-if="impersonation"
                class="hidden sm:flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
            >
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75" />
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500" />
                </span>
                <span>SuperAdmin Impersonation</span>
                <button
                    type="button"
                    class="ml-1 font-semibold text-amber-900 underline hover:text-amber-700 dark:text-amber-200 dark:hover:text-white"
                    @click="exitImpersonation"
                >
                    Exit
                </button>
            </div>

            <!-- Organization Badge -->
            <div class="hidden md:flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500" />
                <span>{{ currentTenant.name }}</span>
            </div>

            <!-- Theme Toggle Component -->
            <ThemeToggle />

            <!-- User Dropdown Menu -->
            <div ref="userDropdownRef" class="relative">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg p-1.5 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    @click.stop="userDropdownOpen = !userDropdownOpen"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white shadow-xs shadow-indigo-500/25">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div class="hidden text-left md:block">
                        <p class="text-xs font-semibold text-zinc-900 dark:text-white leading-tight">
                            {{ user.name }}
                        </p>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 leading-tight">
                            {{ currentTenant.name }}
                        </p>
                    </div>
                    <svg class="hidden md:block h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <Transition
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <div
                        v-if="userDropdownOpen"
                        class="absolute right-0 mt-2 w-56 rounded-xl border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-800 dark:bg-zinc-900 z-50"
                    >
                        <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
                            <p class="text-xs font-semibold text-zinc-900 dark:text-white">{{ user.name }}</p>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ user.email }}</p>
                            <span class="mt-1 inline-block rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                {{ currentTenant.name }}
                            </span>
                        </div>

                        <div class="py-1">
                            <Link
                                href="/admin/dashboard"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-1.5 text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                @click="userDropdownOpen = false"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span>Dashboard</span>
                            </Link>

                            <Link
                                href="/admin/members"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-1.5 text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                @click="userDropdownOpen = false"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Members</span>
                            </Link>
                        </div>

                        <!-- Impersonation Action in Dropdown -->
                        <div v-if="impersonation" class="border-t border-zinc-100 pt-1 dark:border-zinc-800">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40"
                                @click="exitImpersonation"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Exit Admin Mode</span>
                            </button>
                        </div>

                        <div class="border-t border-zinc-100 pt-1 dark:border-zinc-800">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                @click="logout"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Sign Out</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>
