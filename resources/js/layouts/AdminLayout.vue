<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme';
import AdminSidebar from '@/components/AdminSidebar.vue';
import AdminHeader from '@/components/AdminHeader.vue';

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
</script>

<template>
    <div class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100 transition-colors duration-200">
        <Head :title="title" />

        <!-- Sidebar Component (Desktop & Mobile Drawer) -->
        <AdminSidebar
            :mobile-open="mobileMenuOpen"
            @update:mobile-open="mobileMenuOpen = $event"
            @close-mobile="mobileMenuOpen = false"
        />

        <!-- Main Content Area (Offset for Desktop Sidebar) -->
        <div class="flex min-h-screen flex-col lg:pl-72">
            <!-- Header Component -->
            <AdminHeader
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
