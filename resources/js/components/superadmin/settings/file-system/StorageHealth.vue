<script setup lang="ts">
import type { StorageHealthItem, StorageProviderId } from '@/types/superadmin/file-system';

defineProps<{
    providerId: StorageProviderId;
}>();

const healthItems: StorageHealthItem[] = [
    { name: 'Storage Provider', status: 'connected', description: 'Provider API reachable' },
    { name: 'Upload Operations', status: 'operational', description: 'Multipart PUT probes passing' },
    { name: 'Download Operations', status: 'operational', description: 'Public/Private GET operational' },
    { name: 'Delete Operations', status: 'operational', description: 'Lifecycle & purge enabled' },
    { name: 'Signed URLs', status: 'supported', description: 'HMAC temporary tokens valid' },
    { name: 'Image Processing', status: 'enabled', description: 'GD / Imagick drivers loaded' },
];
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 mb-8">
        <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-5">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                        Storage Health Matrix
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Operational status of core media primitives.
                    </p>
                </div>
            </div>

            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                All Systems Operational
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
                v-for="item in healthItems"
                :key="item.name"
                class="flex items-start justify-between p-3 rounded-xl border border-zinc-200/70 bg-zinc-50/60 dark:border-zinc-800 dark:bg-zinc-950/40"
            >
                <div>
                    <p class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                        {{ item.name }}
                    </p>
                    <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                        {{ item.description }}
                    </p>
                </div>

                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold capitalize text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ item.status }}
                </span>
            </div>
        </div>
    </div>
</template>
