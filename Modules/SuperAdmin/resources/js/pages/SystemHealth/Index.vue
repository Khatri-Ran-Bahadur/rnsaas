<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Card from '@/components/Card.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import EmptyState from '@/components/EmptyState.vue';

export interface HealthItem {
    status?: 'healthy' | 'warning' | 'critical' | 'unknown' | string;
    label?: string;
    message?: string;
    details?: Record<string, any> | string | null;
}

export interface SystemHealthData {
    application?: HealthItem;
    laravel?: HealthItem;
    php?: HealthItem;
    database?: HealthItem;
    cache?: HealthItem;
    storage?: HealthItem;
    queue?: HealthItem;
    checked_at?: string;
}

interface Props {
    health?: SystemHealthData | null;
}

const props = withDefaults(defineProps<Props>(), {
    health: null,
});

const page = usePage();

// Reactive local health data (allows in-place refresh without full-page reloads)
const currentHealth = ref<SystemHealthData | null>(props.health);

// Refresh Button State: 'idle' | 'loading' | 'success' | 'failure'
type RefreshStatus = 'idle' | 'loading' | 'success' | 'failure';
const refreshStatus = ref<RefreshStatus>('idle');

// Expanded Details State per card
const expandedCards = ref<Record<string, boolean>>({});

const toggleDetails = (serviceKey: string) => {
    expandedCards.value[serviceKey] = !expandedCards.value[serviceKey];
};

// Overall System Status Calculation
const overallStatus = computed<'healthy' | 'warning' | 'critical' | 'unknown'>(() => {
    if (!currentHealth.value) return 'unknown';

    const items: HealthItem[] = [
        currentHealth.value.application,
        currentHealth.value.laravel,
        currentHealth.value.php,
        currentHealth.value.database,
        currentHealth.value.cache,
        currentHealth.value.storage,
        currentHealth.value.queue,
    ].filter((item): item is HealthItem => Boolean(item));

    if (items.length === 0) return 'unknown';

    const normalized = items.map((item) => (item.status || 'unknown').toLowerCase().trim());

    if (normalized.some((s) => ['critical', 'down', 'error', 'failed'].includes(s))) {
        return 'critical';
    }

    if (normalized.some((s) => ['warning', 'degraded'].includes(s))) {
        return 'warning';
    }

    if (normalized.every((s) => ['healthy', 'ok', 'up', 'passing'].includes(s))) {
        return 'healthy';
    }

    return 'unknown';
});

// Overall Status Metadata
const overallStatusMeta = computed(() => {
    switch (overallStatus.value) {
        case 'healthy':
            return {
                title: 'All Systems Operational',
                badgeLabel: 'Healthy',
                badgeVariant: 'active' as const,
                description: 'All SathiSaaS core infrastructure services, databases, and platform runtimes are operating normally.',
                containerBorder: 'border-emerald-500/30 dark:border-emerald-500/20',
                bgGradient: 'from-emerald-50/70 to-white dark:from-emerald-950/20 dark:to-zinc-900',
                indicatorBg: 'bg-emerald-500',
                indicatorPing: 'bg-emerald-400',
            };
        case 'warning':
            return {
                title: 'Degraded System Performance',
                badgeLabel: 'Warning',
                badgeVariant: 'pending' as const,
                description: 'One or more services reported warnings or degraded responses. Review the health cards below.',
                containerBorder: 'border-amber-500/30 dark:border-amber-500/20',
                bgGradient: 'from-amber-50/70 to-white dark:from-amber-950/20 dark:to-zinc-900',
                indicatorBg: 'bg-amber-500',
                indicatorPing: 'bg-amber-400',
            };
        case 'critical':
            return {
                title: 'Critical Infrastructure Outage',
                badgeLabel: 'Critical',
                badgeVariant: 'cancelled' as const,
                description: 'One or more platform services are failing or unreachable. Immediate attention required.',
                containerBorder: 'border-rose-500/30 dark:border-rose-500/20',
                bgGradient: 'from-rose-50/70 to-white dark:from-rose-950/20 dark:to-zinc-900',
                indicatorBg: 'bg-rose-500',
                indicatorPing: 'bg-rose-400',
            };
        case 'unknown':
        default:
            return {
                title: 'Status Pending Verification',
                badgeLabel: 'Unknown',
                badgeVariant: 'neutral' as const,
                description: 'System health data is currently incomplete or pending verification from service telemetry.',
                containerBorder: 'border-zinc-200 dark:border-zinc-800',
                bgGradient: 'from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900',
                indicatorBg: 'bg-zinc-400',
                indicatorPing: 'bg-zinc-300',
            };
    }
});

// Format Last Checked Timestamp
const formattedLastChecked = computed(() => {
    if (!currentHealth.value?.checked_at) {
        return 'Not checked yet';
    }
    try {
        const date = new Date(currentHealth.value.checked_at);
        if (isNaN(date.getTime())) {
            return currentHealth.value.checked_at;
        }
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return currentHealth.value.checked_at;
    }
});

// Health Services Configuration
interface ServiceCardConfig {
    key: keyof Omit<SystemHealthData, 'checked_at'>;
    name: string;
    defaultLabel: string;
    defaultMessage: string;
    iconPath: string;
}

const serviceCards: ServiceCardConfig[] = [
    {
        key: 'application',
        name: 'Application',
        defaultLabel: 'SathiSaaS Web Engine',
        defaultMessage: 'Application core operational',
        iconPath: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
    },
    {
        key: 'laravel',
        name: 'Laravel',
        defaultLabel: 'Framework Kernel',
        defaultMessage: 'Framework healthy',
        iconPath: 'M13 10V3L4 14h7v7l9-11h-7z',
    },
    {
        key: 'php',
        name: 'PHP',
        defaultLabel: 'Runtime Engine',
        defaultMessage: 'PHP CLI & FPM healthy',
        iconPath: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
    },
    {
        key: 'database',
        name: 'Database',
        defaultLabel: 'Primary Relational DB',
        defaultMessage: 'Connection healthy',
        iconPath: 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
    },
    {
        key: 'cache',
        name: 'Cache',
        defaultLabel: 'Fast In-Memory Cache',
        defaultMessage: 'Read/write healthy',
        iconPath: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
    },
    {
        key: 'storage',
        name: 'Storage',
        defaultLabel: 'Filesystem & Media Storage',
        defaultMessage: 'Default disk writable',
        iconPath: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    },
    {
        key: 'queue',
        name: 'Queue',
        defaultLabel: 'Asynchronous Job Workers',
        defaultMessage: '0 failed jobs',
        iconPath: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    },
];

// Helper to determine status badge mapping
const getStatusBadge = (status?: string) => {
    const s = (status || 'unknown').toLowerCase().trim();
    if (['healthy', 'ok', 'up', 'passing'].includes(s)) {
        return { variant: 'active' as const, label: 'Healthy' };
    }
    if (['warning', 'degraded'].includes(s)) {
        return { variant: 'pending' as const, label: 'Warning' };
    }
    if (['critical', 'down', 'error', 'failed'].includes(s)) {
        return { variant: 'cancelled' as const, label: 'Critical' };
    }
    return { variant: 'neutral' as const, label: 'Unknown' };
};

// Security filter: prevent exposing sensitive keys (passwords, secrets, credentials, tokens)
const SENSITIVE_KEY_REGEX = /(password|secret|key|token|auth|credential|cert|salt|hash|bearer)/i;

const filterSafeDetails = (details: Record<string, any> | string | null | undefined): Array<{ key: string; value: string }> => {
    if (!details) return [];
    if (typeof details !== 'object') {
        return [{ key: 'Output', value: String(details) }];
    }

    const safePairs: Array<{ key: string; value: string }> = [];

    Object.entries(details).forEach(([rawKey, val]) => {
        if (SENSITIVE_KEY_REGEX.test(rawKey)) {
            return;
        }

        let formattedVal = '';
        if (typeof val === 'boolean') {
            formattedVal = val ? 'Yes' : 'No';
        } else if (val === null || val === undefined) {
            formattedVal = 'None';
        } else if (typeof val === 'object') {
            formattedVal = JSON.stringify(val);
        } else {
            formattedVal = String(val);
        }

        // Format snake_case or camelCase key to readable words
        const humanKey = rawKey
            .replace(/_/g, ' ')
            .replace(/([A-Z])/g, ' $1')
            .replace(/^./, (str) => str.toUpperCase())
            .trim();

        safePairs.push({ key: humanKey, value: formattedVal });
    });

    return safePairs;
};

// Refresh Action Handler
const handleRefresh = async () => {
    if (refreshStatus.value === 'loading') return;

    refreshStatus.value = 'loading';

    // Resolve URL based on current routing prefix
    const isSuperAdminPrefix = window.location.pathname.startsWith('/superadmin');
    const checkEndpoint = isSuperAdminPrefix
        ? '/superadmin/system-health/check'
        : '/admin/system-health/check';

    try {
        const csrfToken = (page.props as any)?.csrf_token || '';

        const response = await fetch(checkEndpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data && data.health) {
                currentHealth.value = data.health;
            } else {
                // If endpoint returns generic response, reload Inertia props
                router.reload({
                    only: ['health'],
                    onSuccess: (pageProps: any) => {
                        if (pageProps.props.health) {
                            currentHealth.value = pageProps.props.health;
                        }
                    },
                });
            }
            refreshStatus.value = 'success';
            setTimeout(() => {
                refreshStatus.value = 'idle';
            }, 2500);
        } else if (response.status === 404) {
            // Check endpoint not yet wired on backend: perform Inertia partial reload gracefully
            router.reload({
                only: ['health'],
                onSuccess: (pageProps: any) => {
                    if (pageProps.props.health) {
                        currentHealth.value = pageProps.props.health;
                    }
                    refreshStatus.value = 'success';
                    setTimeout(() => {
                        refreshStatus.value = 'idle';
                    }, 2500);
                },
                onError: () => {
                    refreshStatus.value = 'failure';
                    setTimeout(() => {
                        refreshStatus.value = 'idle';
                    }, 3000);
                },
            });
        } else {
            refreshStatus.value = 'failure';
            setTimeout(() => {
                refreshStatus.value = 'idle';
            }, 3000);
        }
    } catch {
        // Network fallback
        router.reload({
            only: ['health'],
            onSuccess: (pageProps: any) => {
                if (pageProps.props.health) {
                    currentHealth.value = pageProps.props.health;
                }
                refreshStatus.value = 'success';
                setTimeout(() => {
                    refreshStatus.value = 'idle';
                }, 2500);
            },
            onError: () => {
                refreshStatus.value = 'failure';
                setTimeout(() => {
                    refreshStatus.value = 'idle';
                }, 3000);
            },
        });
    }
};
</script>

<template>
    <AdminLayout
        title="System Health"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'System Health' },
        ]"
    >
        <Head title="System Health - SuperAdmin" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        System Health
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Monitor the health of the SathiSaaS application, infrastructure services, and platform dependencies.
                    </p>
                </div>

                <!-- Top Refresh Button -->
                <div class="flex items-center gap-3">
                    <Button
                        variant="primary"
                        size="sm"
                        :disabled="refreshStatus === 'loading'"
                        @click="handleRefresh"
                    >
                        <!-- Idle Icon -->
                        <svg
                            v-if="refreshStatus === 'idle'"
                            class="h-4 w-4 mr-1.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>

                        <!-- Loading Icon -->
                        <svg
                            v-else-if="refreshStatus === 'loading'"
                            class="h-4 w-4 mr-1.5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>

                        <!-- Success Check Icon -->
                        <svg
                            v-else-if="refreshStatus === 'success'"
                            class="h-4 w-4 mr-1.5 text-emerald-300"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>

                        <!-- Failure Icon -->
                        <svg
                            v-else-if="refreshStatus === 'failure'"
                            class="h-4 w-4 mr-1.5 text-rose-300"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>

                        <!-- Button Label -->
                        <span>
                            {{
                                refreshStatus === 'loading'
                                    ? 'Checking...'
                                    : refreshStatus === 'success'
                                    ? 'Updated'
                                    : refreshStatus === 'failure'
                                    ? 'Unable to check'
                                    : 'Refresh'
                            }}
                        </span>
                    </Button>
                </div>
            </div>

            <!-- TOP SUMMARY CARD: System Status -->
            <div
                :class="[
                    'relative overflow-hidden rounded-2xl border bg-gradient-to-br p-6 shadow-xs transition-all',
                    overallStatusMeta.containerBorder,
                    overallStatusMeta.bgGradient,
                ]"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-3">
                            <!-- Animated Status Dot Indicator -->
                            <div class="relative flex h-3.5 w-3.5 items-center justify-center">
                                <span
                                    v-if="overallStatus === 'healthy'"
                                    :class="['absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping', overallStatusMeta.indicatorPing]"
                                />
                                <span :class="['relative inline-flex h-3 w-3 rounded-full', overallStatusMeta.indicatorBg]" />
                            </div>

                            <h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                System Status: {{ overallStatusMeta.title }}
                            </h2>

                            <!-- Calculated Badge -->
                            <Badge
                                :variant="overallStatusMeta.badgeVariant"
                                :label="overallStatusMeta.badgeLabel"
                                size="md"
                            />
                        </div>

                        <p class="text-xs text-zinc-600 dark:text-zinc-400 max-w-2xl">
                            {{ overallStatusMeta.description }}
                        </p>
                    </div>

                    <!-- Last Checked Timestamp & Action -->
                    <div class="flex flex-col items-start sm:items-end gap-1 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-zinc-200/60 dark:border-zinc-800">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            Telemetry Timestamp
                        </span>
                        <div class="flex items-center gap-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Last checked: {{ formattedLastChecked }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPTY / ERROR STATE (When health telemetry is unavailable) -->
            <div v-if="!currentHealth || Object.keys(currentHealth).length === 0">
                <EmptyState
                    title="System health information is currently unavailable."
                    description="No telemetry has been received from the platform health checker. Click below to initiate an infrastructure check."
                >
                    <template #icon>
                        <svg class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </template>
                    <template #actions>
                        <Button
                            variant="primary"
                            size="sm"
                            :disabled="refreshStatus === 'loading'"
                            @click="handleRefresh"
                        >
                            {{ refreshStatus === 'loading' ? 'Checking...' : 'Check System Health' }}
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <!-- HEALTH CARDS GRID (3-column desktop, 2-column tablet, 1-column mobile) -->
            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="service in serviceCards"
                    :key="service.key"
                    class="flex flex-col rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:border-zinc-300 dark:border-zinc-800/80 dark:bg-zinc-900 dark:hover:border-zinc-700/80"
                >
                    <!-- Card Header: Icon + Name + Badge -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <!-- Service Icon -->
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" :d="service.iconPath" />
                                </svg>
                            </div>

                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ service.name }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ currentHealth[service.key]?.label || service.defaultLabel }}
                                </p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <Badge
                            :variant="getStatusBadge(currentHealth[service.key]?.status).variant"
                            :label="getStatusBadge(currentHealth[service.key]?.status).label"
                            size="sm"
                        />
                    </div>

                    <!-- Short Status Message -->
                    <div class="mt-4 flex-1">
                        <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            {{ currentHealth[service.key]?.message || service.defaultMessage }}
                        </p>
                    </div>

                    <!-- Expandable Details Section -->
                    <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between text-xs font-semibold text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
                            @click="toggleDetails(service.key)"
                        >
                            <span>{{ expandedCards[service.key] ? 'Hide Diagnostics' : 'View Diagnostics' }}</span>
                            <svg
                                class="h-4 w-4 transition-transform duration-200"
                                :class="{ 'rotate-180': expandedCards[service.key] }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Details Accordion Content -->
                        <div v-show="expandedCards[service.key]" class="mt-3 space-y-2">
                            <div
                                v-if="filterSafeDetails(currentHealth[service.key]?.details).length > 0"
                                class="space-y-1.5 rounded-xl bg-zinc-50 p-3 text-xs dark:bg-zinc-950/60 border border-zinc-100 dark:border-zinc-800/80"
                            >
                                <div
                                    v-for="(item, idx) in filterSafeDetails(currentHealth[service.key]?.details)"
                                    :key="idx"
                                    class="flex items-center justify-between gap-2 overflow-hidden"
                                >
                                    <span class="font-medium text-zinc-500 dark:text-zinc-400 truncate">{{ item.key }}</span>
                                    <span class="font-mono text-[11px] font-semibold text-zinc-800 dark:text-zinc-200 truncate">{{ item.value }}</span>
                                </div>
                            </div>
                            <div
                                v-else
                                class="rounded-xl bg-zinc-50 p-2.5 text-center text-[11px] text-zinc-400 dark:bg-zinc-950/40 dark:text-zinc-500 border border-zinc-100 dark:border-zinc-800/50"
                            >
                                No extra diagnostic telemetry reported
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
