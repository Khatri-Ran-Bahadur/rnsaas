<script setup lang="ts">
import Badge from '@/components/Badge.vue';

defineProps<{
    activeProviderName: string;
    isEncrypted?: boolean;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 border border-primary-200/60 dark:border-primary-800/40 shadow-xs">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        File System
                    </h1>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure where SathiSaaS stores and manages uploaded files across all modules.
                    </p>
                </div>
            </div>
        </div>

        <!-- Global Status Badges -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Active:</span>
                <span class="font-semibold text-zinc-900 dark:text-white">{{ activeProviderName }}</span>
            </div>

            <Badge variant="outline" size="sm" class="gap-1 border-primary-200 bg-primary-50/50 text-primary-700 dark:border-primary-800/50 dark:bg-primary-950/40 dark:text-primary-300">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Centralized Platform Media
            </Badge>

            <Badge variant="outline" size="sm" class="gap-1 border-zinc-200 text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Tenant-Isolated
            </Badge>
        </div>
    </div>
</template>
