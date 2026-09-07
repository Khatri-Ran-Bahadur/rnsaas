<script setup lang="ts">
import type { StorageSecurityConfig, StorageVisibility } from '@/types/superadmin/file-system';
import Switch from '@/components/Switch.vue';

const props = defineProps<{
    modelValue: StorageSecurityConfig;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', val: StorageSecurityConfig): void;
}>();

const setVisibility = (val: StorageVisibility) => {
    emit('update:modelValue', {
        ...props.modelValue,
        defaultVisibility: val,
    });
};
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 mb-8">
        <div class="flex items-center gap-3 pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                    Storage Security & Visibility
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Define default access controls and temporary signed URL authorization protocols.
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- 1. Default File Visibility -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                    Default File Visibility
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Private Option -->
                    <div
                        :class="[
                            'p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3',
                            modelValue.defaultVisibility === 'private'
                                ? 'border-primary-500 bg-primary-50/40 text-primary-950 dark:bg-primary-950/30 dark:border-primary-800 dark:text-white ring-1 ring-primary-500/30'
                                : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-800/60 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300',
                        ]"
                        @click="setVisibility('private')"
                    >
                        <div class="mt-0.5">
                            <input
                                type="radio"
                                name="visibility"
                                value="private"
                                :checked="modelValue.defaultVisibility === 'private'"
                                class="text-primary-600 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <span class="text-xs font-bold block flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Private (Recommended)
                            </span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 block">
                                Restricts direct cloud bucket URLs. Files require tenant authentication or signed tokens.
                            </span>
                        </div>
                    </div>

                    <!-- Public Option -->
                    <div
                        :class="[
                            'p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3',
                            modelValue.defaultVisibility === 'public'
                                ? 'border-amber-500 bg-amber-50/40 text-amber-950 dark:bg-amber-950/30 dark:border-amber-800 dark:text-white ring-1 ring-amber-500/30'
                                : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-800/60 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300',
                        ]"
                        @click="setVisibility('public')"
                    >
                        <div class="mt-0.5">
                            <input
                                type="radio"
                                name="visibility"
                                value="public"
                                :checked="modelValue.defaultVisibility === 'public'"
                                class="text-amber-600 focus:ring-amber-500"
                            />
                        </div>
                        <div>
                            <span class="text-xs font-bold block flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                Public
                            </span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 block">
                                Accessible globally via direct asset URLs without application session checks.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contextual Alert -->
                <div class="mt-3">
                    <div
                        v-if="modelValue.defaultVisibility === 'public'"
                        class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-300 flex items-start gap-3"
                    >
                        <svg class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="font-bold">Security Warning: Public Visibility Enabled</p>
                            <p class="mt-0.5 text-rose-700 dark:text-rose-300/90">
                                Public files can be accessed without authenticated application access. Use this only for files intended to be publicly accessible (e.g., branding logos, website marketing materials).
                            </p>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-4 text-xs text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-300 flex items-start gap-3"
                    >
                        <svg class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <div>
                            <p class="font-bold">Encrypted & Access Protected</p>
                            <p class="mt-0.5 text-emerald-700 dark:text-emerald-300/90">
                                Private files must be accessed through authorized application endpoints or temporary signed URLs with cryptographic signature expiry.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Signed URL settings -->
            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-white">Enforce Temporary Signed URLs</span>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Generates short-lived HMAC URLs for secure document downloading.</p>
                    </div>
                    <Switch
                        :model-value="modelValue.enforceSignedUrls"
                        @update:model-value="emit('update:modelValue', { ...modelValue, enforceSignedUrls: $event })"
                    />
                </div>

                <div v-if="modelValue.enforceSignedUrls" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                            Default Signed URL Expiry
                        </label>
                        <div class="relative">
                            <input
                                :value="modelValue.signedUrlExpiryMinutes"
                                type="number"
                                min="1"
                                max="1440"
                                class="w-full rounded-md border border-zinc-300 bg-white px-2.5 py-1.5 pr-14 text-xs text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                @input="emit('update:modelValue', { ...modelValue, signedUrlExpiryMinutes: Number(($event.target as HTMLInputElement).value) })"
                            />
                            <span class="absolute right-2.5 top-1.5 text-[11px] text-zinc-400">minutes</span>
                        </div>
                        <p class="text-[10px] text-zinc-400 mt-1">E.g., 60 minutes for employee contract viewing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
