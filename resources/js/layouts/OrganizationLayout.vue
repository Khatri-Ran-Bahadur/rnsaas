<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import OrganizationSidebar from '@/components/OrganizationSidebar.vue';
import OrganizationHeader from '@/components/OrganizationHeader.vue';

defineProps<{
    title?: string;
    breadcrumbs?: Array<{ label: string; href?: string }>;
}>();

const { initTheme } = useTheme();

onMounted(() => {
    initTheme();
});

const mobileMenuOpen = ref(false);
const page = usePage();

const flashSuccess = computed(() => (page.props.flash as any)?.success);
const flashError = computed(() => (page.props.flash as any)?.error);
const showFlash = ref(true);

const impersonation = computed(() => (page.props.impersonation as any) ?? null);
const currentTenant = computed(() => (page.props.current_tenant as any) ?? null);

const isExiting = ref(false);

const exitImpersonation = () => {
    if (isExiting.value) return;
    isExiting.value = true;
    router.post('/admin/impersonate/exit', {}, {
        onFinish: () => {
            isExiting.value = false;
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100 transition-colors duration-200">
        <Head :title="title" />

        <!-- SuperAdmin Impersonation Banner -->
        <aside
            v-if="impersonation"
            aria-label="SuperAdmin Impersonation Notice"
            class="sticky top-0 z-50 flex items-center justify-between border-b border-amber-300 bg-amber-500 px-4 py-2 text-white shadow-md sm:px-8"
        >
            <div class="flex items-center gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-600 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <div class="text-xs sm:text-sm font-medium">
                    <span>SuperAdmin Impersonation Mode Active — Viewing </span>
                    <strong class="font-bold underline">{{ impersonation.tenant_name || currentTenant?.name || 'Organization' }}</strong>
                </div>
            </div>

            <button
                type="button"
                :disabled="isExiting"
                class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1 text-xs font-semibold text-amber-900 shadow-xs hover:bg-amber-50 transition-colors disabled:opacity-50 shrink-0"
                @click="exitImpersonation"
            >
                <svg v-if="!isExiting" class="h-3.5 w-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>{{ isExiting ? 'Exiting...' : 'Exit Admin Mode' }}</span>
            </button>
        </aside>

        <!-- Sidebar Component -->
        <OrganizationSidebar
            :mobile-open="mobileMenuOpen"
            @update:mobile-open="mobileMenuOpen = $event"
            @close-mobile="mobileMenuOpen = false"
        />

        <!-- Main Content Area -->
        <div class="flex min-h-screen flex-col lg:pl-72">
            <!-- Header Component -->
            <OrganizationHeader
                :breadcrumbs="breadcrumbs"
                @open-mobile="mobileMenuOpen = true"
            />

            <!-- Flash Alert Messages -->
            <div v-if="(flashSuccess || flashError) && showFlash" class="px-4 pt-4 sm:px-8">
                <div
                    v-if="flashSuccess"
                    class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/60 dark:text-emerald-300"
                >
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">{{ flashSuccess }}</span>
                    </div>
                    <button type="button" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400" @click="showFlash = false">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div
                    v-if="flashError"
                    class="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/60 dark:text-rose-300"
                >
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">{{ flashError }}</span>
                    </div>
                    <button type="button" class="text-rose-600 hover:text-rose-900 dark:text-rose-400" @click="showFlash = false">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Page Content Body -->
            <main class="flex-1 p-4 sm:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
