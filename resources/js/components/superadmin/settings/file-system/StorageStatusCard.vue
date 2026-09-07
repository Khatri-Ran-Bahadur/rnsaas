<script setup lang="ts">
import type { CurrentStorageStatus } from '@/types/superadmin/file-system';
import Button from '@/components/Button.vue';

defineProps<{
    status: CurrentStorageStatus;
    isTesting?: boolean;
}>();

const emit = defineEmits<{
    (e: 'test-connection'): void;
    (e: 'scroll-to-config'): void;
}>();
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-white via-zinc-50/50 to-zinc-100/50 p-6 shadow-xs dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900/80 dark:to-zinc-950 mb-8">
        <!-- Subtle background accent glow -->
        <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-primary-500/5 blur-3xl pointer-events-none dark:bg-primary-400/10"></div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Left Side: Status Info & Core Stats -->
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        Current Storage Status
                    </span>
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span>Connected</span>
                    </div>

                    <span v-if="status.isDefault" class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700">
                        Default Provider
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-4">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white tracking-tight">
                        {{ status.providerName }}
                    </h2>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Last connection verified: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ status.lastTested }}</span>
                    </span>
                </div>

                <!-- Stats Grid in Card -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-2">
                    <div class="rounded-xl bg-white/80 p-3.5 border border-zinc-200/60 dark:bg-zinc-900/90 dark:border-zinc-800 shadow-2xs">
                        <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Used Storage</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white mt-0.5">{{ status.usedStorage }}</p>
                        <div class="w-full bg-zinc-100 rounded-full h-1.5 mt-2 dark:bg-zinc-800 overflow-hidden">
                            <div class="bg-primary-500 h-1.5 rounded-full transition-all duration-500" :style="{ width: `${status.quotaPercent}%` }"></div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white/80 p-3.5 border border-zinc-200/60 dark:bg-zinc-900/90 dark:border-zinc-800 shadow-2xs">
                        <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Total Uploaded Files</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white mt-0.5">{{ status.fileCount }}</p>
                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1.5 flex items-center gap-0.5">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Synchronized
                        </p>
                    </div>

                    <div class="col-span-2 sm:col-span-1 rounded-xl bg-white/80 p-3.5 border border-zinc-200/60 dark:bg-zinc-900/90 dark:border-zinc-800 shadow-2xs">
                        <p class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Storage Quota</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white mt-0.5">{{ status.quotaStorage }}</p>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-1.5">
                            {{ status.quotaPercent }}% of soft quota utilized
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Side: Quick Action Buttons -->
            <div class="flex flex-row lg:flex-col items-center sm:items-stretch gap-3 lg:border-l lg:border-zinc-200/70 lg:pl-6 dark:lg:border-zinc-800 shrink-0">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="flex-1 sm:flex-initial gap-2 text-xs"
                    :disabled="isTesting"
                    @click="emit('test-connection')"
                >
                    <svg
                        class="h-3.5 w-3.5 text-zinc-500 transition-transform"
                        :class="{ 'animate-spin': isTesting }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ isTesting ? 'Testing...' : 'Test Connection' }}</span>
                </Button>

                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="flex-1 sm:flex-initial gap-2 text-xs text-zinc-700 dark:text-zinc-300"
                    @click="emit('scroll-to-config')"
                >
                    <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit Configuration</span>
                </Button>
            </div>
        </div>
    </div>
</template>
