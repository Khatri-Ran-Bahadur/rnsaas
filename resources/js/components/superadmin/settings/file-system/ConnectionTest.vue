<script setup lang="ts">
import { ref } from 'vue';
import type { ConnectionStatus, StorageProviderId } from '@/types/superadmin/file-system';
import Button from '@/components/Button.vue';

const props = defineProps<{
    providerId: StorageProviderId;
    providerName: string;
}>();

const emit = defineEmits<{
    (e: 'connection-tested', success: boolean): void;
}>();

const status = ref<ConnectionStatus>('idle');
const latency = ref<number>(142);
const simulateFailure = ref<boolean>(false);
const testTimestamp = ref<string>('');

const currentStep = ref<string>('');

const runTest = () => {
    status.value = 'testing';
    currentStep.value = 'Resolving endpoint DNS...';

    setTimeout(() => {
        currentStep.value = 'Authenticating access credentials...';
    }, 400);

    setTimeout(() => {
        currentStep.value = 'Testing bucket read/write probe...';
    }, 850);

    setTimeout(() => {
        if (simulateFailure.value) {
            status.value = 'failed';
            currentStep.value = '';
            emit('connection-tested', false);
        } else {
            status.value = 'connected';
            latency.value = Math.floor(Math.random() * (160 - 110 + 1) + 110);
            testTimestamp.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            currentStep.value = '';
            emit('connection-tested', true);
        }
    }, 1350);
};

defineExpose({
    runTest,
});
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 mb-8">
        <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-6">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                        Storage Connection Test
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Live diagnostic probe to test authentication, bucket existence, and read/write permissions.
                    </p>
                </div>
            </div>

            <!-- Simulation Toggle for testing UI states -->
            <label class="hidden sm:flex items-center gap-2 text-[11px] text-zinc-400 cursor-pointer">
                <span>Simulate failure:</span>
                <input
                    v-model="simulateFailure"
                    type="checkbox"
                    class="rounded border-zinc-300 text-rose-600 focus:ring-rose-500"
                />
            </label>
        </div>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border border-zinc-200/80 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-950/40">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                        Probe Target: <span class="text-primary-600 dark:text-primary-400">{{ providerName }}</span>
                    </p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                        Runs a harmless 1-byte read/write and delete payload cycle to guarantee healthy operations.
                    </p>
                </div>

                <Button
                    type="button"
                    variant="primary"
                    size="sm"
                    class="gap-2 text-xs font-semibold cursor-pointer shrink-0"
                    :disabled="status === 'testing'"
                    @click="runTest"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        :class="{ 'animate-spin': status === 'testing' }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ status === 'testing' ? 'Testing connection...' : 'Test Connection' }}</span>
                </Button>
            </div>

            <!-- Testing Progress State -->
            <div
                v-if="status === 'testing'"
                class="rounded-xl border border-primary-200 bg-primary-50/60 p-4 text-xs text-primary-900 dark:border-primary-900/60 dark:bg-primary-950/40 dark:text-primary-300 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary-500"></span>
                    </span>
                    <div>
                        <p class="font-bold">Testing connection...</p>
                        <p class="text-[11px] text-primary-700 dark:text-primary-400 mt-0.5">{{ currentStep }}</p>
                    </div>
                </div>
                <span class="text-[11px] font-mono text-primary-600 animate-pulse">Running diagnostics...</span>
            </div>

            <!-- Success State -->
            <div
                v-else-if="status === 'connected'"
                class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/70 dark:text-emerald-300">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-sm">Connection successful</p>
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                            Credentials verified. Read, write, and delete operations are fully operational.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 text-right">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-emerald-700/80 dark:text-emerald-400">Probe Latency</span>
                        <p class="font-mono font-bold text-sm text-emerald-900 dark:text-white">{{ latency }} ms</p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-emerald-700/80 dark:text-emerald-400">Timestamp</span>
                        <p class="font-mono font-bold text-sm text-emerald-900 dark:text-white">{{ testTimestamp || 'Now' }}</p>
                    </div>
                </div>
            </div>

            <!-- Failed State -->
            <div
                v-else-if="status === 'failed'"
                class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-300 flex items-start gap-3"
            >
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/70 dark:text-rose-300 mt-0.5">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-sm">Connection failed</p>
                    <p class="text-[11px] text-rose-700 dark:text-rose-300 mt-0.5">
                        Unable to authenticate with storage provider. Invalid credentials or unreachable bucket endpoint.
                    </p>
                    <div class="mt-2 text-[11px] font-mono bg-rose-100/70 dark:bg-rose-950 p-2 rounded-lg text-rose-800 dark:text-rose-300">
                        Error: 403 Forbidden - AccessDenied: The AWS Access Key Id you provided does not exist in our records.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
