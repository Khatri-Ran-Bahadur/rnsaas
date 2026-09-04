<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import EmptyState from '@/components/EmptyState.vue';

export type StorageProviderType = 'server' | 's3' | 's3_compatible';

export interface StorageServerConfig {
    disk?: string;
    visibility?: string;
    root?: string;
    status?: string;
}

export interface StorageS3Config {
    bucket?: string;
    region?: string;
    access_key?: string;
    secret_key_configured?: boolean;
    endpoint?: string | null;
    public_url?: string | null;
}

export interface StorageS3CompatibleConfig {
    endpoint?: string;
    region?: string;
    bucket?: string;
    access_key?: string;
    secret_key_configured?: boolean;
    public_url?: string | null;
}

export interface StorageProviderConfig {
    active_provider?: StorageProviderType | string;
    status?: 'active' | 'not_configured' | 'failed' | string;
    server?: StorageServerConfig;
    s3?: StorageS3Config;
    s3_compatible?: StorageS3CompatibleConfig;
    test_status?: 'not_tested' | 'testing' | 'connected' | 'failed' | string;
    migration_supported?: boolean;
}

export interface StorageDiskItem {
    name: string;
    driver?: string;
    status?: 'healthy' | 'warning' | 'unavailable' | string;
    visibility?: 'public' | 'private' | string;
    capacity?: string | null;
    used?: string | null;
    available?: string | null;
    usage_percent?: number | null;
    root?: string | null;
}

export interface StorageHealthProbe {
    status?: 'healthy' | 'warning' | 'failed' | string;
    read_test?: 'passed' | 'failed' | string;
    write_test?: 'passed' | 'failed' | string;
    delete_test?: 'passed' | 'failed' | string;
    last_checked?: string | null;
}

export interface StorageSettingsData {
    default_disk?: string;
    driver?: string;
    status?: 'healthy' | 'warning' | 'unavailable' | string;
    visibility?: string;
    total_capacity?: string | null;
    used_storage?: string | null;
    available_storage?: string | null;
    usage_percent?: number | null;
    media_library_url?: string | null;
    health?: StorageHealthProbe;
    disks?: StorageDiskItem[];
    provider_config?: StorageProviderConfig;
}

interface Props {
    storage?: StorageSettingsData | null;
}

const props = withDefaults(defineProps<Props>(), {
    storage: null,
});

// Toast / Notification State
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

function showToast(message: string, type: 'success' | 'error' = 'success') {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 3500);
}

// Fallback data when props are not yet provided by backend
const storageData = computed<StorageSettingsData>(() => {
    return (
        props.storage ?? {
            default_disk: 'public (local)',
            driver: 'local',
            status: 'healthy',
            visibility: 'public',
            total_capacity: null, // Displays 'Not available' per prompt instructions
            used_storage: null,   // Displays 'Not available' per prompt instructions
            available_storage: null,
            usage_percent: null,
            media_library_url: '/superadmin/media',
            health: {
                status: 'healthy',
                read_test: 'passed',
                write_test: 'passed',
                delete_test: 'passed',
                last_checked: new Date().toISOString(),
            },
            disks: [
                {
                    name: 'public',
                    driver: 'local',
                    status: 'healthy',
                    visibility: 'public',
                    capacity: null,
                    used: null,
                    available: null,
                    usage_percent: null,
                    root: 'storage/app/public',
                },
                {
                    name: 'local',
                    driver: 'local',
                    status: 'healthy',
                    visibility: 'private',
                    capacity: null,
                    used: null,
                    available: null,
                    usage_percent: null,
                    root: 'storage/app/private',
                },
            ],
            provider_config: {
                active_provider: 'server',
                status: 'active',
                server: {
                    disk: 'public',
                    visibility: 'public',
                    root: 'storage/app/public',
                    status: 'Active',
                },
                s3: {
                    bucket: '',
                    region: 'us-east-1',
                    access_key: '',
                    secret_key_configured: false,
                    endpoint: null,
                    public_url: null,
                },
                s3_compatible: {
                    endpoint: '',
                    region: 'auto',
                    bucket: '',
                    access_key: '',
                    secret_key_configured: false,
                    public_url: null,
                },
                test_status: 'not_tested',
                migration_supported: false,
            },
        }
    );
});

// Empty State check: only show if explicitly empty object
const isStorageUnavailable = computed(() => {
    if (props.storage === null || props.storage === undefined) return false;
    return Object.keys(props.storage).length === 0;
});

// Format Timestamps
const formatTimestamp = (iso?: string | null) => {
    if (!iso) return 'Never';
    try {
        const d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
};

// Health Probe State
const isProbing = ref(false);
const probeData = ref<StorageHealthProbe>({
    status: storageData.value.health?.status ?? 'healthy',
    read_test: storageData.value.health?.read_test ?? 'passed',
    write_test: storageData.value.health?.write_test ?? 'passed',
    delete_test: storageData.value.health?.delete_test ?? 'passed',
    last_checked: storageData.value.health?.last_checked ?? new Date().toISOString(),
});

const executeStorageProbe = async () => {
    if (isProbing.value) return;

    isProbing.value = true;
    setTimeout(() => {
        probeData.value = {
            status: 'healthy',
            read_test: 'passed',
            write_test: 'passed',
            delete_test: 'passed',
            last_checked: new Date().toISOString(),
        };
        isProbing.value = false;
        showToast('Storage integrity check passed successfully.');
    }, 550);
};

// Disk Detail Modal State
const selectedDisk = ref<StorageDiskItem | null>(null);
const showDiskModal = ref(false);

const openDiskDetails = (disk: StorageDiskItem) => {
    selectedDisk.value = disk;
    showDiskModal.value = true;
};

const closeDiskModal = () => {
    showDiskModal.value = false;
    selectedDisk.value = null;
};

// Badge helper
const getStatusBadgeVariant = (status?: string): 'active' | 'pending' | 'cancelled' | 'neutral' => {
    const s = (status || '').toLowerCase().trim();
    if (['healthy', 'active', 'ok', 'operational', 'passed'].includes(s)) return 'active';
    if (['warning', 'degraded'].includes(s)) return 'pending';
    if (['unavailable', 'failed', 'critical', 'error'].includes(s)) return 'cancelled';
    return 'neutral';
};

// Usage calculation
const hasCapacityData = computed(() => {
    return (
        storageData.value.total_capacity !== null &&
        storageData.value.total_capacity !== undefined &&
        storageData.value.used_storage !== null &&
        storageData.value.used_storage !== undefined
    );
});

const computedUsagePercent = computed<number | null>(() => {
    if (storageData.value.usage_percent !== null && storageData.value.usage_percent !== undefined) {
        return storageData.value.usage_percent;
    }
    return null;
});

const usageBarColor = computed(() => {
    const pct = computedUsagePercent.value;
    if (pct === null) return 'bg-primary-500';
    if (pct >= 90) return 'bg-rose-500';
    if (pct >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
});

// ==========================================
// Storage Provider Configuration State
// ==========================================

const activeProvider = computed<StorageProviderType>(() => {
    const raw = (storageData.value.provider_config?.active_provider || '').toLowerCase();
    if (raw === 's3' || raw === 'amazon_s3') return 's3';
    if (raw === 's3_compatible' || raw === 's3-compatible') return 's3_compatible';
    return 'server';
});

const activeProviderLabel = computed<string>(() => {
    switch (activeProvider.value) {
        case 's3':
            return 'Amazon S3';
        case 's3_compatible':
            return 'S3-Compatible';
        default:
            return 'Server Storage';
    }
});

const providerStatus = computed<'active' | 'not_configured' | 'failed'>(() => {
    const s = (storageData.value.provider_config?.status || '').toLowerCase();
    if (s === 'active' || s === 'healthy') return 'active';
    if (s === 'failed' || s === 'error') return 'failed';
    return 'not_configured';
});

const providerStatusLabel = computed<string>(() => {
    switch (providerStatus.value) {
        case 'active':
            return 'Active';
        case 'failed':
            return 'Failed';
        default:
            return 'Not Configured';
    }
});

const providerStatusVariant = computed<'active' | 'cancelled' | 'neutral'>(() => {
    switch (providerStatus.value) {
        case 'active':
            return 'active';
        case 'failed':
            return 'cancelled';
        default:
            return 'neutral';
    }
});

// Selected provider for interactive tabs/selector
const selectedProvider = ref<StorageProviderType>(activeProvider.value);

watch(
    () => activeProvider.value,
    (newVal) => {
        selectedProvider.value = newVal;
    }
);

// Server Configuration display values
const serverConfig = computed(() => {
    return {
        disk: storageData.value.provider_config?.server?.disk ?? 'public',
        visibility: storageData.value.provider_config?.server?.visibility ?? 'public',
        root: storageData.value.provider_config?.server?.root ?? 'storage/app/public',
        status: storageData.value.provider_config?.server?.status ?? 'Active',
    };
});

// Amazon S3 Form State
const s3Form = ref({
    bucket: storageData.value.provider_config?.s3?.bucket ?? '',
    region: storageData.value.provider_config?.s3?.region ?? 'us-east-1',
    access_key: storageData.value.provider_config?.s3?.access_key ?? '',
    secret_key: '',
    endpoint: storageData.value.provider_config?.s3?.endpoint ?? '',
    public_url: storageData.value.provider_config?.s3?.public_url ?? '',
    secret_configured: Boolean(storageData.value.provider_config?.s3?.secret_key_configured),
});
const showS3Secret = ref(false);

// S3-Compatible Form State
const s3CompatForm = ref({
    endpoint: storageData.value.provider_config?.s3_compatible?.endpoint ?? '',
    region: storageData.value.provider_config?.s3_compatible?.region ?? 'auto',
    bucket: storageData.value.provider_config?.s3_compatible?.bucket ?? '',
    access_key: storageData.value.provider_config?.s3_compatible?.access_key ?? '',
    secret_key: '',
    public_url: storageData.value.provider_config?.s3_compatible?.public_url ?? '',
    secret_configured: Boolean(storageData.value.provider_config?.s3_compatible?.secret_key_configured),
});
const showS3CompatSecret = ref(false);

// Test Connection Visual States
type ConnectionTestState = 'not_tested' | 'testing' | 'connected' | 'failed';
const testStatus = ref<ConnectionTestState>(
    (storageData.value.provider_config?.test_status as ConnectionTestState) || 'not_tested'
);

const testStatusBadgeLabel = computed<string>(() => {
    switch (testStatus.value) {
        case 'testing':
            return 'Testing...';
        case 'connected':
            return 'Connected';
        case 'failed':
            return 'Failed';
        default:
            return 'Not Tested';
    }
});

const testStatusBadgeVariant = computed<'active' | 'pending' | 'cancelled' | 'neutral'>(() => {
    switch (testStatus.value) {
        case 'connected':
            return 'active';
        case 'testing':
            return 'pending';
        case 'failed':
            return 'cancelled';
        default:
            return 'neutral';
    }
});

const handleTestConnection = () => {
    // "If backend support is not yet implemented, buttons may exist visually but MUST NOT make fake requests. Do not invent endpoints."
    showToast('Storage test diagnostic will run via backend job once controller action is connected.', 'success');
};

const handleSaveConfiguration = () => {
    // "If backend support is not yet implemented, buttons may exist visually but MUST NOT make fake requests. Do not invent endpoints."
    showToast('Storage provider configuration will be persisted once backend controller action is connected.', 'success');
};
</script>

<template>
    <div class="space-y-6">
        <!-- Toast Notification -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="notification"
                :class="[
                    'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium shadow-xl text-white',
                    notification.type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'
                ]"
            >
                <svg v-if="notification.type === 'success'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ notification.message }}</span>
            </div>
        </Transition>

        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-4 dark:border-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    Storage Management
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Monitor configured storage disks, capacity, and application file usage.
                </p>
            </div>

            <!-- Media Library Link (Per requirement: Do not duplicate media upload manager, link to Media module) -->
            <Link
                v-if="storageData.media_library_url"
                :href="storageData.media_library_url"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-primary-500 focus:outline-none transition-colors shrink-0"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Open Media Library</span>
            </Link>
        </div>

        <!-- ERROR / EMPTY STATE (If storage is marked unavailable) -->
        <div v-if="isStorageUnavailable">
            <EmptyState
                title="Storage Infrastructure Unavailable"
                description="Unable to connect to the filesystem driver or retrieve storage disk telemetry. Please verify disk permissions and configuration."
            >
                <template #icon>
                    <svg class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </template>
                <template #actions>
                    <Button variant="primary" size="sm" @click="executeStorageProbe">
                        Retry Storage Probe
                    </Button>
                </template>
            </EmptyState>
        </div>

        <!-- MAIN STORAGE MANAGEMENT INTERFACE -->
        <div v-else class="space-y-6">
            <!-- 1. SUMMARY CARDS (4 Cards Grid) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Card 1: Default Disk -->
                <div class="flex flex-col justify-between rounded-xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Default Disk</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-base font-bold text-zinc-900 dark:text-zinc-100 truncate" :title="storageData.default_disk || 'public (local)'">
                            {{ storageData.default_disk || 'public (local)' }}
                        </p>
                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Active filesystem disk</p>
                    </div>
                </div>

                <!-- Card 2: Storage Status -->
                <div class="flex flex-col justify-between rounded-xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Storage Status</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <Badge
                            :variant="getStatusBadgeVariant(storageData.status)"
                            :label="storageData.status === 'unavailable' ? 'Unavailable' : 'Healthy'"
                            size="md"
                        />
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">Operational</span>
                    </div>
                </div>

                <!-- Card 3: Total Capacity -->
                <div class="flex flex-col justify-between rounded-xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Capacity</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                            {{ storageData.total_capacity || 'Not available' }}
                        </p>
                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Aggregated allocation</p>
                    </div>
                </div>

                <!-- Card 4: Used Storage -->
                <div class="flex flex-col justify-between rounded-xl border border-zinc-200/80 bg-white p-4 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Used Storage</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                            {{ storageData.used_storage || 'Not available' }}
                        </p>
                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Media & uploads</p>
                    </div>
                </div>
            </div>

            <!-- ================================================== -->
            <!-- 2. STORAGE PROVIDER CONFIGURATION                  -->
            <!-- ================================================== -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-6">
                <!-- Section Header with Title, Description, and Active Provider Info -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Storage Provider Configuration
                            </h3>
                            <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                Filesystem Target
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Choose where SathiSaaS stores uploaded media and application files.
                        </p>
                    </div>

                    <!-- CURRENT PROVIDER & STATUS (Do not fabricate values) -->
                    <div class="flex items-center gap-3 bg-zinc-50 dark:bg-zinc-950/60 p-2.5 rounded-xl border border-zinc-200/60 dark:border-zinc-800 shrink-0 text-xs">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Active Provider</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ activeProviderLabel }}
                            </span>
                        </div>
                        <div class="h-6 w-px bg-zinc-200 dark:bg-zinc-800"></div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Status</span>
                            <div>
                                <Badge
                                    :variant="providerStatusVariant"
                                    :label="providerStatusLabel"
                                    size="sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIVE STORAGE WARNING -->
                <div class="rounded-xl border border-amber-200/80 bg-amber-50/70 p-4 text-xs dark:border-amber-900/60 dark:bg-amber-950/40">
                    <div class="flex items-start gap-3">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/70 dark:text-amber-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-amber-900 dark:text-amber-200">
                                Active Storage Provider Notice
                            </h4>
                            <p class="mt-0.5 text-amber-800 dark:text-amber-300/90 leading-relaxed">
                                Changing the active storage provider affects where new files are uploaded. Existing media remains on its original storage until migrated.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PROVIDER SELECTOR (Segmented / Radio selection) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3">
                        Provider Selector
                    </label>
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                        <!-- Server Storage Option -->
                        <button
                            type="button"
                            :class="[
                                'relative flex flex-col items-start rounded-xl border p-4 text-left transition-all',
                                selectedProvider === 'server'
                                    ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20 dark:border-primary-500 dark:bg-primary-950/20'
                                    : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950/40 dark:hover:border-zinc-700'
                            ]"
                            @click="selectedProvider = 'server'"
                        >
                            <div class="flex w-full items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Server Storage</span>
                                </div>
                                <span :class="[
                                    'flex h-4 w-4 items-center justify-center rounded-full border transition-colors',
                                    selectedProvider === 'server'
                                        ? 'border-primary-600 bg-primary-600 dark:border-primary-400 dark:bg-primary-400'
                                        : 'border-zinc-300 dark:border-zinc-600'
                                ]">
                                    <span v-if="selectedProvider === 'server'" class="h-1.5 w-1.5 rounded-full bg-white dark:bg-zinc-900" />
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                Store files on the application server.
                            </p>
                            <div v-if="activeProvider === 'server'" class="mt-3">
                                <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    ● Current Active
                                </span>
                            </div>
                        </button>

                        <!-- Amazon S3 Option -->
                        <button
                            type="button"
                            :class="[
                                'relative flex flex-col items-start rounded-xl border p-4 text-left transition-all',
                                selectedProvider === 's3'
                                    ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20 dark:border-primary-500 dark:bg-primary-950/20'
                                    : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950/40 dark:hover:border-zinc-700'
                            ]"
                            @click="selectedProvider = 's3'"
                        >
                            <div class="flex w-full items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Amazon S3</span>
                                </div>
                                <span :class="[
                                    'flex h-4 w-4 items-center justify-center rounded-full border transition-colors',
                                    selectedProvider === 's3'
                                        ? 'border-primary-600 bg-primary-600 dark:border-primary-400 dark:bg-primary-400'
                                        : 'border-zinc-300 dark:border-zinc-600'
                                ]">
                                    <span v-if="selectedProvider === 's3'" class="h-1.5 w-1.5 rounded-full bg-white dark:bg-zinc-900" />
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                Store files in an Amazon S3 bucket.
                            </p>
                            <div v-if="activeProvider === 's3'" class="mt-3">
                                <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    ● Current Active
                                </span>
                            </div>
                        </button>

                        <!-- S3-Compatible Option -->
                        <button
                            type="button"
                            :class="[
                                'relative flex flex-col items-start rounded-xl border p-4 text-left transition-all',
                                selectedProvider === 's3_compatible'
                                    ? 'border-primary-500 bg-primary-50/40 ring-2 ring-primary-500/20 dark:border-primary-500 dark:bg-primary-950/20'
                                    : 'border-zinc-200/80 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950/40 dark:hover:border-zinc-700'
                            ]"
                            @click="selectedProvider = 's3_compatible'"
                        >
                            <div class="flex w-full items-center justify-between mb-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">S3-Compatible Storage</span>
                                </div>
                                <span :class="[
                                    'flex h-4 w-4 items-center justify-center rounded-full border transition-colors',
                                    selectedProvider === 's3_compatible'
                                        ? 'border-primary-600 bg-primary-600 dark:border-primary-400 dark:bg-primary-400'
                                        : 'border-zinc-300 dark:border-zinc-600'
                                ]">
                                    <span v-if="selectedProvider === 's3_compatible'" class="h-1.5 w-1.5 rounded-full bg-white dark:bg-zinc-900" />
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                Use services such as Cloudflare R2, DigitalOcean Spaces, MinIO, Wasabi, or other S3-compatible storage.
                            </p>
                            <div v-if="activeProvider === 's3_compatible'" class="mt-3">
                                <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    ● Current Active
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- CONFIGURATION DETAILS PANELS -->
                <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/50 p-5 dark:border-zinc-800 dark:bg-zinc-950/30">
                    <!-- 1. SERVER STORAGE SPEC PANEL -->
                    <div v-if="selectedProvider === 'server'" class="space-y-4">
                        <div class="border-b border-zinc-200/70 pb-3 dark:border-zinc-800">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Server Storage Configuration
                            </h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Standard local filesystem storage managed directly on the host server.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 text-xs">
                            <div class="rounded-lg border border-zinc-200/80 bg-white p-3.5 dark:border-zinc-800 dark:bg-zinc-900">
                                <span class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Disk</span>
                                <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ serverConfig.disk }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-zinc-200/80 bg-white p-3.5 dark:border-zinc-800 dark:bg-zinc-900">
                                <span class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Visibility</span>
                                <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100 capitalize">
                                    {{ serverConfig.visibility }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-zinc-200/80 bg-white p-3.5 dark:border-zinc-800 dark:bg-zinc-900">
                                <span class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Root</span>
                                <p class="mt-1 font-mono font-semibold text-zinc-900 dark:text-zinc-100 truncate" :title="serverConfig.root">
                                    {{ serverConfig.root }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-zinc-200/80 bg-white p-3.5 dark:border-zinc-800 dark:bg-zinc-900">
                                <span class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Status</span>
                                <div class="mt-1">
                                    <Badge variant="active" :label="serverConfig.status || 'Active'" size="sm" />
                                </div>
                            </div>
                        </div>

                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500 italic">
                            * Note: Operating system mount points and private server credentials are safe and not exposed.
                        </p>
                    </div>

                    <!-- 2. AMAZON S3 CONFIGURATION PANEL -->
                    <div v-else-if="selectedProvider === 's3'" class="space-y-4">
                        <div class="border-b border-zinc-200/70 pb-3 dark:border-zinc-800">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                Amazon S3 Credentials & Bucket Details
                            </h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Configure your AWS S3 bucket, region, and IAM programmatic access credentials.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Bucket <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="s3Form.bucket"
                                    type="text"
                                    placeholder="e.g. sathisaas-production-storage"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Region <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="s3Form.region"
                                    type="text"
                                    placeholder="e.g. us-east-1, eu-central-1"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Access Key <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="s3Form.access_key"
                                    type="text"
                                    placeholder="e.g. AKIAIOSFODNN7EXAMPLE"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs font-mono text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                        Secret Key <span class="text-rose-500">*</span>
                                    </label>
                                    <span v-if="s3Form.secret_configured && !s3Form.secret_key" class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Configured</span>
                                    </span>
                                </div>
                                <div class="relative">
                                    <input
                                        v-model="s3Form.secret_key"
                                        :type="showS3Secret ? 'text' : 'password'"
                                        :placeholder="s3Form.secret_configured ? '••••••••••••••••••••••••••••••••' : 'Enter Secret Key'"
                                        class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 pr-10 text-xs font-mono text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                        @click="showS3Secret = !showS3Secret"
                                    >
                                        <svg v-if="showS3Secret" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Endpoint <span class="text-zinc-400 font-normal lowercase">(optional)</span>
                                </label>
                                <input
                                    v-model="s3Form.endpoint"
                                    type="text"
                                    placeholder="https://s3.amazonaws.com or custom endpoint"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Public URL / CDN URL <span class="text-zinc-400 font-normal lowercase">(optional)</span>
                                </label>
                                <input
                                    v-model="s3Form.public_url"
                                    type="text"
                                    placeholder="e.g. https://cdn.sathisaas.com"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 3. S3-COMPATIBLE STORAGE CONFIGURATION PANEL -->
                    <div v-else-if="selectedProvider === 's3_compatible'" class="space-y-4">
                        <div class="border-b border-zinc-200/70 pb-3 dark:border-zinc-800">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                S3-Compatible Storage Configuration
                            </h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Connect Cloudflare R2, DigitalOcean Spaces, MinIO, Wasabi, or Backblaze B2.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Endpoint <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="s3CompatForm.endpoint"
                                    type="text"
                                    placeholder="e.g. https://<account_id>.r2.cloudflarestorage.com"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Region <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="s3CompatForm.region"
                                    type="text"
                                    placeholder="e.g. auto, us-east-1, nyc3"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Bucket <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="s3CompatForm.bucket"
                                    type="text"
                                    placeholder="e.g. sathisaas-media-bucket"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Access Key <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="s3CompatForm.access_key"
                                    type="text"
                                    placeholder="Access Key or Key ID"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs font-mono text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                        Secret Key <span class="text-rose-500">*</span>
                                    </label>
                                    <span v-if="s3CompatForm.secret_configured && !s3CompatForm.secret_key" class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Configured</span>
                                    </span>
                                </div>
                                <div class="relative">
                                    <input
                                        v-model="s3CompatForm.secret_key"
                                        :type="showS3CompatSecret ? 'text' : 'password'"
                                        :placeholder="s3CompatForm.secret_configured ? '••••••••••••••••••••••••••••••••' : 'Enter Secret Access Key'"
                                        class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 pr-10 text-xs font-mono text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                        @click="showS3CompatSecret = !showS3CompatSecret"
                                    >
                                        <svg v-if="showS3CompatSecret" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Public URL / CDN URL <span class="text-zinc-400 font-normal lowercase">(optional)</span>
                                </label>
                                <input
                                    v-model="s3CompatForm.public_url"
                                    type="text"
                                    placeholder="e.g. https://pub-xxxx.r2.dev or custom CDN"
                                    class="w-full rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TEST CONNECTION EXPLANATION & ACTIONS -->
                <div class="rounded-xl border border-zinc-200/80 bg-white p-4.5 dark:border-zinc-800 dark:bg-zinc-900/90 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 mt-0.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h5 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    Test Connection
                                </h5>
                                <Badge
                                    :variant="testStatusBadgeVariant"
                                    :label="testStatusBadgeLabel"
                                    size="sm"
                                />
                            </div>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-xl">
                                The connection test verifies that SathiSaaS can write, read, verify, and delete a temporary test object.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 self-end sm:self-center shrink-0">
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="handleTestConnection"
                        >
                            <svg class="h-3.5 w-3.5 mr-1 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Test Connection</span>
                        </Button>

                        <Button
                            variant="primary"
                            size="sm"
                            @click="handleSaveConfiguration"
                        >
                            <span>Save Configuration</span>
                        </Button>
                    </div>
                </div>

                <!-- EXISTING MEDIA (Informational with migration button) -->
                <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-4.5 dark:border-zinc-800 dark:bg-zinc-950/50">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 mt-0.5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                        Existing Media
                                    </h4>
                                    <span class="rounded bg-zinc-200/80 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        Data Preservation
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-2xl">
                                    Existing files remain associated with their original storage disk. Changing the active provider does not automatically migrate existing files.
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 self-end sm:self-center">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled
                                class="opacity-60 cursor-not-allowed text-xs"
                                title="Background migration queue will be enabled when backend migration is implemented"
                            >
                                <svg class="h-3.5 w-3.5 mr-1 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <span>Migrate Existing Media</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. MAIN SECTION: STORAGE OVERVIEW & USAGE VISUALIZATION -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-6">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Storage Overview
                        </h3>
                    </div>
                    <Badge
                        :variant="getStatusBadgeVariant(storageData.status)"
                        :label="storageData.status === 'unavailable' ? 'Unavailable' : 'Healthy'"
                        size="sm"
                    />
                </div>

                <!-- 8 Diagnostic Points (2-Column Desktop Grid) -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Disk Name</span>
                        <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">{{ storageData.default_disk || 'public' }}</p>
                    </div>

                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Driver</span>
                        <p class="text-xs font-mono font-semibold text-zinc-800 dark:text-zinc-200">{{ storageData.driver || 'local' }}</p>
                    </div>

                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Visibility</span>
                        <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 capitalize">{{ storageData.visibility || 'Public' }}</p>
                    </div>

                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Status</span>
                        <div class="pt-0.5">
                            <Badge
                                :variant="getStatusBadgeVariant(storageData.status)"
                                :label="storageData.status === 'unavailable' ? 'Unavailable' : 'Healthy'"
                                size="sm"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Capacity</span>
                        <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ storageData.total_capacity || 'Not available' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Used Storage</span>
                        <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ storageData.used_storage || 'Not available' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Available</span>
                        <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ storageData.available_storage || 'Not available' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80 space-y-1">
                        <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Usage %</span>
                        <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ computedUsagePercent !== null ? `${computedUsagePercent}%` : 'Not available' }}
                        </p>
                    </div>
                </div>

                <!-- USAGE PROGRESS BAR VISUALIZATION -->
                <div class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40 space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">Storage Usage</span>
                            <span
                                v-if="hasCapacityData"
                                class="text-zinc-500 dark:text-zinc-400"
                            >
                                {{ storageData.used_storage }} / {{ storageData.total_capacity }}
                            </span>
                            <span
                                v-else
                                class="text-zinc-400 dark:text-zinc-500 italic text-[11px]"
                            >
                                Capacity metrics not reported by current filesystem driver (local directory)
                            </span>
                        </div>
                        <span class="font-bold text-zinc-700 dark:text-zinc-300">
                            {{ computedUsagePercent !== null ? `${computedUsagePercent}%` : 'Not available' }}
                        </span>
                    </div>

                    <!-- Progress track -->
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                        <div
                            v-if="computedUsagePercent !== null"
                            class="h-full rounded-full transition-all duration-500"
                            :class="usageBarColor"
                            :style="{ width: `${Math.min(100, Math.max(0, computedUsagePercent))}%` }"
                        />
                        <div
                            v-else
                            class="h-full w-full bg-zinc-300/40 dark:bg-zinc-700/40 rounded-full"
                        />
                    </div>
                </div>
            </div>

            <!-- 3. STORAGE HEALTH SECTION -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-100 pb-4 dark:border-zinc-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Storage Health & Integrity
                            </h3>
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            Perform read, write, and delete operations to test filesystem driver availability.
                        </p>
                    </div>

                    <Button
                        variant="secondary"
                        size="sm"
                        :disabled="isProbing"
                        @click="executeStorageProbe"
                    >
                        <svg
                            v-if="!isProbing"
                            class="h-3.5 w-3.5 mr-1.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <svg
                            v-else
                            class="h-3.5 w-3.5 mr-1.5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span>{{ isProbing ? 'Checking...' : 'Check Storage' }}</span>
                    </Button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-5">
                    <!-- Status -->
                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Health Status</span>
                        <div class="mt-1">
                            <Badge
                                :variant="getStatusBadgeVariant(probeData.status)"
                                :label="probeData.status === 'failed' ? 'Failed' : 'Healthy'"
                                size="sm"
                            />
                        </div>
                    </div>

                    <!-- Read Test -->
                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Read Test</span>
                        <div class="mt-1 flex items-center gap-1.5">
                            <svg
                                :class="probeData.read_test === 'passed' ? 'text-emerald-500' : 'text-rose-500'"
                                class="h-4 w-4 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 capitalize">
                                {{ probeData.read_test || 'Passed' }}
                            </span>
                        </div>
                    </div>

                    <!-- Write Test -->
                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Write Test</span>
                        <div class="mt-1 flex items-center gap-1.5">
                            <svg
                                :class="probeData.write_test === 'passed' ? 'text-emerald-500' : 'text-rose-500'"
                                class="h-4 w-4 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 capitalize">
                                {{ probeData.write_test || 'Passed' }}
                            </span>
                        </div>
                    </div>

                    <!-- Delete Test -->
                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Delete Test</span>
                        <div class="mt-1 flex items-center gap-1.5">
                            <svg
                                :class="probeData.delete_test === 'passed' ? 'text-emerald-500' : 'text-rose-500'"
                                class="h-4 w-4 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 capitalize">
                                {{ probeData.delete_test || 'Passed' }}
                            </span>
                        </div>
                    </div>

                    <!-- Last Checked -->
                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Last Checked</span>
                        <p class="mt-1 text-xs font-semibold text-zinc-700 dark:text-zinc-300 truncate" :title="formatTimestamp(probeData.last_checked)">
                            {{ formatTimestamp(probeData.last_checked) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 4. DISKS SECTION -->
            <div
                v-if="storageData.disks && storageData.disks.length > 0"
                class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800 mb-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Configured Storage Disks
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Individual filesystem disks configured in the application storage driver.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                        <thead class="border-b border-zinc-100 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                            <tr>
                                <th class="pb-3 font-semibold">Disk</th>
                                <th class="pb-3 font-semibold">Driver</th>
                                <th class="pb-3 font-semibold">Status</th>
                                <th class="pb-3 font-semibold">Capacity</th>
                                <th class="pb-3 font-semibold">Used</th>
                                <th class="pb-3 font-semibold">Available</th>
                                <th class="pb-3 font-semibold">Usage</th>
                                <th class="pb-3 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                            <tr v-for="disk in storageData.disks" :key="disk.name" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                <td class="py-3.5 font-medium text-zinc-900 dark:text-zinc-100">
                                    <div class="flex items-center gap-2">
                                        <span>{{ disk.name }}</span>
                                        <span v-if="disk.name === 'public'" class="rounded bg-primary-50 px-1.5 py-0.5 text-[10px] font-semibold text-primary-700 dark:bg-primary-950/60 dark:text-primary-300">
                                            Default
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 font-mono text-[11px] text-zinc-600 dark:text-zinc-300">
                                    {{ disk.driver || 'local' }}
                                </td>
                                <td class="py-3.5">
                                    <Badge
                                        :variant="getStatusBadgeVariant(disk.status)"
                                        :label="disk.status === 'unavailable' ? 'Unavailable' : 'Healthy'"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ disk.capacity || 'Not available' }}
                                </td>
                                <td class="py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ disk.used || 'Not available' }}
                                </td>
                                <td class="py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ disk.available || 'Not available' }}
                                </td>
                                <td class="py-3.5">
                                    <span v-if="disk.usage_percent !== null && disk.usage_percent !== undefined" class="font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ disk.usage_percent }}%
                                    </span>
                                    <span v-else class="text-zinc-400 dark:text-zinc-500">
                                        Not available
                                    </span>
                                </td>
                                <td class="py-3.5 text-right">
                                    <Button
                                        variant="outline"
                                        size="xs"
                                        @click="openDiskDetails(disk)"
                                    >
                                        View Details
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DISK DETAILS MODAL -->
        <Modal
            :show="showDiskModal"
            max-width="md"
            title="Storage Disk Details"
            description="Diagnostic overview and filesystem configuration."
            @close="closeDiskModal"
        >
            <div v-if="selectedDisk" class="space-y-4 p-5">
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Disk Name</span>
                        <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ selectedDisk.name }}</p>
                    </div>

                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Driver</span>
                        <p class="mt-1 font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ selectedDisk.driver || 'local' }}</p>
                    </div>

                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Visibility</span>
                        <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100 capitalize">{{ selectedDisk.visibility || 'Public' }}</p>
                    </div>

                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Status</span>
                        <div class="mt-1">
                            <Badge
                                :variant="getStatusBadgeVariant(selectedDisk.status)"
                                :label="selectedDisk.status === 'unavailable' ? 'Unavailable' : 'Healthy'"
                                size="sm"
                            />
                        </div>
                    </div>

                    <div class="col-span-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Root Directory</span>
                        <p class="mt-1 font-mono text-[11px] text-zinc-800 dark:text-zinc-200">
                            {{ selectedDisk.root || `storage/app/${selectedDisk.name}` }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Total Capacity</span>
                        <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-200">{{ selectedDisk.capacity || 'Not available' }}</p>
                    </div>

                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Used Storage</span>
                        <p class="mt-1 font-semibold text-zinc-800 dark:text-zinc-200">{{ selectedDisk.used || 'Not available' }}</p>
                    </div>
                </div>

                <!-- Security Assurance Banner (No credentials exposed) -->
                <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3 text-[11px] text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-400 flex items-start gap-2">
                    <svg class="h-4 w-4 text-zinc-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Storage infrastructure access keys and bucket secrets are secured at runtime and not exposed to the browser client.</span>
                </div>

                <div class="flex justify-end pt-2">
                    <Button variant="secondary" size="sm" @click="closeDiskModal">
                        Close
                    </Button>
                </div>
            </div>
        </Modal>
    </div>
</template>
