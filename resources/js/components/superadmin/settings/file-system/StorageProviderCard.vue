<script setup lang="ts">
import type { StorageProviderId, StorageProviderInfo } from '@/types/superadmin/file-system';
import Badge from '@/components/Badge.vue';

defineProps<{
    providers: StorageProviderInfo[];
    selectedProvider: StorageProviderId;
    activeProvider: StorageProviderId;
}>();

const emit = defineEmits<{
    (e: 'select-provider', id: StorageProviderId): void;
    (e: 'set-active-provider', id: StorageProviderId): void;
}>();
</script>

<template>
    <div class="space-y-4 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                    Storage Providers
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Select a storage driver to configure or set as the default active provider for SathiSaaS.
                </p>
            </div>
            <span class="text-xs text-zinc-400 dark:text-zinc-500 italic">
                Centralized global configuration
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
                v-for="provider in providers"
                :key="provider.id"
                :class="[
                    'group relative rounded-2xl border p-5 transition-all duration-200 cursor-pointer flex flex-col justify-between',
                    selectedProvider === provider.id
                        ? 'border-primary-500 bg-white dark:bg-zinc-900 shadow-md ring-2 ring-primary-500/20'
                        : 'border-zinc-200/80 bg-white/70 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:border-zinc-700 hover:shadow-xs',
                ]"
                @click="emit('select-provider', provider.id)"
            >
                <div>
                    <!-- Card Top: Icon & Status -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div
                            :class="[
                                'flex h-12 w-12 items-center justify-center rounded-xl transition-colors',
                                selectedProvider === provider.id
                                    ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/80 dark:text-primary-400 border border-primary-200 dark:border-primary-800/80 shadow-xs'
                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 group-hover:bg-zinc-200/70 dark:group-hover:bg-zinc-700/60',
                            ]"
                        >
                            <!-- Local Storage Icon -->
                            <svg v-if="provider.id === 'local'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>

                            <!-- Amazon S3 Icon -->
                            <svg v-else-if="provider.id === 's3'" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>

                            <!-- Cloudflare R2 Icon -->
                            <svg v-else-if="provider.id === 'r2'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="activeProvider === provider.id"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                            <span
                                v-else-if="provider.isConfigured"
                                class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-[11px] font-medium text-zinc-700 border border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700"
                            >
                                Available
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800"
                            >
                                Not configured
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <span>{{ provider.name }}</span>
                            <span
                                v-if="selectedProvider === provider.id"
                                class="text-[10px] uppercase font-semibold tracking-wider text-primary-600 dark:text-primary-400"
                            >
                                Editing
                            </span>
                        </h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">
                            {{ provider.description }}
                        </p>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="pt-5 mt-4 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-xs">
                    <span class="text-zinc-400 dark:text-zinc-500 font-medium">
                        Driver: <strong class="text-zinc-700 dark:text-zinc-300 uppercase">{{ provider.id }}</strong>
                    </span>

                    <button
                        type="button"
                        class="text-xs font-semibold hover:underline cursor-pointer"
                        :class="[
                            selectedProvider === provider.id
                                ? 'text-primary-600 dark:text-primary-400'
                                : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                        ]"
                        @click.stop="emit('select-provider', provider.id)"
                    >
                        Configure &rarr;
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
