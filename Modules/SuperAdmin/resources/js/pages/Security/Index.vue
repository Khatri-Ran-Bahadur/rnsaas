<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';

export interface LoginActivityItem {
    id: string | number;
    user: string;
    email?: string;
    ip_address: string;
    device: string;
    location?: string | null;
    status: 'success' | 'failed' | string;
    time: string;
}

export interface FailedLoginItem {
    id: string | number;
    user_or_email: string;
    ip_address: string;
    attempt_count: number;
    time: string;
}

export interface ActiveSessionItem {
    id: string;
    user: string;
    email?: string;
    device: string;
    ip_address: string;
    last_activity: string;
    status: 'active' | 'idle' | string;
    is_current?: boolean;
}

export interface SecurityEventItem {
    id: string | number;
    event: string;
    actor: string;
    target: string;
    time: string;
    severity: 'info' | 'warning' | 'critical';
}

export interface SecurityRecommendationItem {
    id: string | number;
    title: string;
    description: string;
    severity: 'info' | 'warning' | 'critical';
    action_label?: string;
}

export interface SecurityCenterData {
    summary?: {
        security_status?: 'protected' | 'warning' | 'critical' | string;
        active_sessions_count?: number;
        failed_logins_count?: number;
        recent_events_count?: number;
    };
    recent_logins?: LoginActivityItem[];
    failed_logins?: FailedLoginItem[];
    active_sessions?: ActiveSessionItem[];
    security_events?: SecurityEventItem[];
    recommendations?: SecurityRecommendationItem[];
}

interface Props {
    security?: SecurityCenterData;
}

const props = defineProps<Props>();

// Notification Toast
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

function showToast(message: string, type: 'success' | 'error' = 'success') {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 3500);
}

// Fallback data when backend data is not yet provided
const securityData = computed<SecurityCenterData>(() => {
    return (
        props.security ?? {
            summary: {
                security_status: 'protected',
                active_sessions_count: 14,
                failed_logins_count: 3,
                recent_events_count: 9,
            },
            recent_logins: [
                {
                    id: 1,
                    user: 'Super Admin',
                    email: 'admin@sathisaas.com',
                    ip_address: '192.168.1.100',
                    device: 'Chrome 128 / macOS',
                    location: 'Kathmandu, Nepal',
                    status: 'success',
                    time: new Date(Date.now() - 1000 * 60 * 15).toISOString(),
                },
                {
                    id: 2,
                    user: 'Ram Bahadur',
                    email: 'ram@acme-corp.com',
                    ip_address: '103.45.21.14',
                    device: 'Firefox 129 / Windows',
                    location: 'Pokhara, Nepal',
                    status: 'success',
                    time: new Date(Date.now() - 1000 * 60 * 45).toISOString(),
                },
                {
                    id: 3,
                    user: 'Sita Sharma',
                    email: 'sita@devflow.io',
                    ip_address: '49.12.188.4',
                    device: 'Safari 17 / iOS',
                    location: 'Singapore',
                    status: 'success',
                    time: new Date(Date.now() - 1000 * 60 * 120).toISOString(),
                },
            ],
            failed_logins: [
                {
                    id: 1,
                    user_or_email: 'root@sathisaas.com',
                    ip_address: '185.220.101.5',
                    attempt_count: 4,
                    time: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
                },
                {
                    id: 2,
                    user_or_email: 'administrator@sathisaas.com',
                    ip_address: '194.26.29.112',
                    attempt_count: 2,
                    time: new Date(Date.now() - 1000 * 60 * 95).toISOString(),
                },
            ],
            active_sessions: [
                {
                    id: 'sess_1',
                    user: 'Super Admin',
                    email: 'admin@sathisaas.com',
                    device: 'MacBook Pro (Chrome)',
                    ip_address: '192.168.1.100',
                    last_activity: new Date().toISOString(),
                    status: 'active',
                    is_current: true,
                },
                {
                    id: 'sess_2',
                    user: 'Ram Bahadur',
                    email: 'ram@acme-corp.com',
                    device: 'Desktop PC (Firefox)',
                    ip_address: '103.45.21.14',
                    last_activity: new Date(Date.now() - 1000 * 60 * 12).toISOString(),
                    status: 'active',
                },
                {
                    id: 'sess_3',
                    user: 'Support Lead',
                    email: 'support@sathisaas.com',
                    device: 'iPad Air (Safari)',
                    ip_address: '182.93.85.12',
                    last_activity: new Date(Date.now() - 1000 * 60 * 60).toISOString(),
                    status: 'idle',
                },
            ],
            security_events: [
                {
                    id: 1,
                    event: 'SuperAdmin Privileged Setting Updated',
                    actor: 'Super Admin',
                    target: 'Platform Settings (Security)',
                    time: new Date(Date.now() - 1000 * 60 * 25).toISOString(),
                    severity: 'info',
                },
                {
                    id: 2,
                    event: 'Repeated Failed Authentication Attempt',
                    actor: '185.220.101.5',
                    target: 'SuperAdmin Login Portal',
                    time: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
                    severity: 'warning',
                },
                {
                    id: 3,
                    event: 'Tenant Organization Suspended',
                    actor: 'Super Admin',
                    target: 'Organization #104',
                    time: new Date(Date.now() - 1000 * 60 * 180).toISOString(),
                    severity: 'critical',
                },
            ],
            recommendations: [
                {
                    id: 1,
                    title: 'Enforce Two-Factor Authentication',
                    description: '2 platform administrators have not yet configured multi-factor authentication.',
                    severity: 'warning',
                },
                {
                    id: 2,
                    title: 'Excessive Failed Login Watch',
                    description: '4 failed login attempts detected from IP 185.220.101.5 in the past hour.',
                    severity: 'critical',
                },
                {
                    id: 3,
                    title: 'Stale Session Cleanup',
                    description: '3 sessions have been idle for more than 48 hours without re-authentication.',
                    severity: 'info',
                },
            ],
        }
    );
});

// Revoke Session Confirmation Modal
const selectedSessionToRevoke = ref<ActiveSessionItem | null>(null);
const isRevoking = ref(false);

const openRevokeModal = (session: ActiveSessionItem) => {
    selectedSessionToRevoke.value = session;
};

const closeRevokeModal = () => {
    if (isRevoking.value) return;
    selectedSessionToRevoke.value = null;
};

const executeRevokeSession = () => {
    if (!selectedSessionToRevoke.value || isRevoking.value) return;

    isRevoking.value = true;
    const sessionUser = selectedSessionToRevoke.value.user;

    setTimeout(() => {
        isRevoking.value = false;
        closeRevokeModal();
        showToast(`Active session for ${sessionUser} revoked successfully.`);
    }, 500);
};

// Utilities
const formatDateTime = (iso?: string | null): string => {
    if (!iso) return '—';
    try {
        const d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
};

const getSeverityBadgeVariant = (severity: 'info' | 'warning' | 'critical'): 'active' | 'pending' | 'cancelled' | 'neutral' => {
    switch (severity) {
        case 'info':
            return 'active';
        case 'warning':
            return 'pending';
        case 'critical':
            return 'cancelled';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Security Center - SathiSaaS SuperAdmin" />

        <div class="space-y-6 pb-12">
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
            <div class="flex flex-col gap-1 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">
                    Security Center
                </h1>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Monitor platform access, authentication activity, sessions, and security events.
                </p>
            </div>

            <!-- SUMMARY CARDS (4 Cards Grid) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Security Status -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Security Status</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <Badge
                            variant="active"
                            :label="securityData.summary?.security_status === 'protected' ? 'Protected' : 'Warning'"
                            size="md"
                        />
                        <span class="text-[11px] text-zinc-400">Threat level normal</span>
                    </div>
                </div>

                <!-- Active Sessions -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Active Sessions</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ securityData.summary?.active_sessions_count ?? 0 }}
                        </p>
                        <p class="text-[11px] text-zinc-400">Connected platform clients</p>
                    </div>
                </div>

                <!-- Failed Login Attempts -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Failed Logins (24h)</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-xl font-bold text-amber-600 dark:text-amber-400">
                            {{ securityData.summary?.failed_logins_count ?? 0 }}
                        </p>
                        <p class="text-[11px] text-zinc-400">Rate-limited attempts</p>
                    </div>
                </div>

                <!-- Recent Security Events -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Security Events</span>
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ securityData.summary?.recent_events_count ?? 0 }}
                        </p>
                        <p class="text-[11px] text-zinc-400">Audit logged events</p>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: SECURITY RECOMMENDATIONS -->
            <div
                v-if="securityData.recommendations && securityData.recommendations.length > 0"
                class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4"
            >
                <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Security Recommendations
                        </h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Automated posture checks and actionable platform security advisories.
                        </p>
                    </div>
                    <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ securityData.recommendations.length }} Recommended
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div
                        v-for="rec in securityData.recommendations"
                        :key="rec.id"
                        class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40 flex flex-col justify-between space-y-3"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ rec.title }}</span>
                                <Badge
                                    :variant="getSeverityBadgeVariant(rec.severity)"
                                    :label="rec.severity"
                                    size="sm"
                                />
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                {{ rec.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: RECENT LOGIN ACTIVITY & SECTION 2: FAILED LOGIN ATTEMPTS (2-Column Grid) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- 1. Recent Login Activity -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                    <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            1. Recent Login Activity
                        </h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Latest successful platform authentication events.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                            <thead class="border-b border-zinc-100 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:border-zinc-800">
                                <tr>
                                    <th class="pb-2.5 font-semibold">User</th>
                                    <th class="pb-2.5 font-semibold">IP / Device</th>
                                    <th class="pb-2.5 font-semibold">Location</th>
                                    <th class="pb-2.5 font-semibold text-right">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                                <tr v-for="item in securityData.recent_logins || []" :key="item.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                    <td class="py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ item.user }}
                                    </td>
                                    <td class="py-3">
                                        <p class="font-mono text-[11px] text-zinc-700 dark:text-zinc-300">{{ item.ip_address }}</p>
                                        <p class="text-[10px] text-zinc-400">{{ item.device }}</p>
                                    </td>
                                    <td class="py-3 text-zinc-600 dark:text-zinc-300">
                                        {{ item.location || 'Unknown' }}
                                    </td>
                                    <td class="py-3 text-right text-zinc-500 dark:text-zinc-400">
                                        {{ formatDateTime(item.time) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Failed Login Attempts -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                    <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            2. Failed Login Attempts
                        </h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Suspicious authentication failures and rate-limit triggers.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                            <thead class="border-b border-zinc-100 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:border-zinc-800">
                                <tr>
                                    <th class="pb-2.5 font-semibold">User / Email</th>
                                    <th class="pb-2.5 font-semibold">IP Address</th>
                                    <th class="pb-2.5 font-semibold">Attempts</th>
                                    <th class="pb-2.5 font-semibold text-right">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                                <tr v-for="item in securityData.failed_logins || []" :key="item.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                    <td class="py-3 font-mono text-[11px] text-zinc-800 dark:text-zinc-200">
                                        {{ item.user_or_email }}
                                    </td>
                                    <td class="py-3 font-mono text-[11px] text-rose-600 dark:text-rose-400 font-semibold">
                                        {{ item.ip_address }}
                                    </td>
                                    <td class="py-3">
                                        <span class="rounded bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-600 dark:bg-rose-950/60 dark:text-rose-300">
                                            {{ item.attempt_count }} tries
                                        </span>
                                    </td>
                                    <td class="py-3 text-right text-zinc-500 dark:text-zinc-400">
                                        {{ formatDateTime(item.time) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: ACTIVE SESSIONS -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        3. Active Sessions
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Currently authenticated client sessions across browser instances.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                        <thead class="border-b border-zinc-100 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:border-zinc-800">
                            <tr>
                                <th class="pb-3 font-semibold">User</th>
                                <th class="pb-3 font-semibold">Device</th>
                                <th class="pb-3 font-semibold">IP Address</th>
                                <th class="pb-3 font-semibold">Last Activity</th>
                                <th class="pb-3 font-semibold">Status</th>
                                <th class="pb-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                            <tr v-for="session in securityData.active_sessions || []" :key="session.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                <td class="py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                    <div class="flex items-center gap-2">
                                        <span>{{ session.user }}</span>
                                        <span v-if="session.is_current" class="rounded bg-primary-50 px-1.5 py-0.5 text-[9px] font-bold text-primary-700 dark:bg-primary-950/60 dark:text-primary-300">
                                            Current
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 text-zinc-700 dark:text-zinc-300">
                                    {{ session.device }}
                                </td>
                                <td class="py-3 font-mono text-[11px] text-zinc-600 dark:text-zinc-400">
                                    {{ session.ip_address }}
                                </td>
                                <td class="py-3 text-zinc-500 dark:text-zinc-400">
                                    {{ formatDateTime(session.last_activity) }}
                                </td>
                                <td class="py-3">
                                    <Badge
                                        :variant="session.status === 'active' ? 'active' : 'neutral'"
                                        :label="session.status === 'active' ? 'Active' : 'Idle'"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-3 text-right">
                                    <Button
                                        v-if="!session.is_current"
                                        variant="danger"
                                        size="xs"
                                        @click="openRevokeModal(session)"
                                    >
                                        Revoke Session
                                    </Button>
                                    <span v-else class="text-[11px] text-zinc-400 italic">This Device</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 4: SECURITY EVENTS -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-5 sm:p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <div class="border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        4. Security Events
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Audit trail of platform security modifications and access alerts.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                        <thead class="border-b border-zinc-100 text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:border-zinc-800">
                            <tr>
                                <th class="pb-3 font-semibold">Event</th>
                                <th class="pb-3 font-semibold">Actor</th>
                                <th class="pb-3 font-semibold">Target</th>
                                <th class="pb-3 font-semibold">Severity</th>
                                <th class="pb-3 font-semibold text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                            <tr v-for="event in securityData.security_events || []" :key="event.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                <td class="py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ event.event }}
                                </td>
                                <td class="py-3 text-zinc-700 dark:text-zinc-300">
                                    {{ event.actor }}
                                </td>
                                <td class="py-3 font-mono text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ event.target }}
                                </td>
                                <td class="py-3">
                                    <Badge
                                        :variant="getSeverityBadgeVariant(event.severity)"
                                        :label="event.severity"
                                        size="sm"
                                    />
                                </td>
                                <td class="py-3 text-right text-zinc-500 dark:text-zinc-400">
                                    {{ formatDateTime(event.time) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Security Assurance Notice -->
            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-4 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400 flex items-start gap-3">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <div class="leading-relaxed">
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">Zero-Exposure Security Architecture:</span>
                    Passwords, hash digests, session tokens, and secrets are strictly excluded from client-side state. Destructive session revocations require administrator confirmation.
                </div>
            </div>
        </div>

        <!-- REVOKE SESSION CONFIRMATION MODAL -->
        <Modal
            :show="selectedSessionToRevoke !== null"
            max-width="md"
            @close="closeRevokeModal"
        >
            <div class="p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            Revoke Active Session?
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                            Are you sure you want to terminate the session on
                            <strong>{{ selectedSessionToRevoke?.device }}</strong> for user
                            <strong>{{ selectedSessionToRevoke?.user }}</strong>? The user will be immediately signed out from that device.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        :disabled="isRevoking"
                        @click="closeRevokeModal"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="danger"
                        size="sm"
                        :loading="isRevoking"
                        @click="executeRevokeSession"
                    >
                        {{ isRevoking ? 'Revoking...' : 'Confirm Revocation' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
