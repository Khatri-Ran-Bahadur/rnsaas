<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StatsCard from '@/components/StatsCard.vue';
import Card from '@/components/Card.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Pagination from '@/components/Pagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import SearchInput from '@/components/SearchInput.vue';
import Select from '@/components/Select.vue';

// ─── TypeScript Interfaces ──────────────────────────────────────────────────

export interface SecurityOverview {
    successful_logins_today: number;
    failed_logins_today: number;
    logouts_today: number;
    lockouts_today: number;
    password_reset_requests_today: number;
    password_resets_today: number;
    active_sessions: number;
}

export interface ActorItem {
    id: number;
    name: string;
    email: string;
}

export interface SecurityEventMetadata {
    guard?: string;
    remember?: boolean;
    identifier?: string;
    user_found?: boolean;
    path?: string;
    method?: string;
    session_id?: string;
    revoked_user_id?: number;
    session_ip?: string;
    [key: string]: unknown;
}

export interface SecurityEventItem {
    id: string;
    event: string;
    actor: ActorItem | null;
    ip_address: string | null;
    user_agent: string | null;
    metadata: SecurityEventMetadata | null;
    created_at: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface SessionUser {
    name: string | null;
    email: string | null;
}

export interface ActiveSessionItem {
    id: string;
    user_id: number;
    user: SessionUser;
    ip_address: string | null;
    user_agent: string | null;
    last_activity: number | string;
    is_current?: boolean;
}

export interface SecurityFilters {
    search?: string | null;
    event?: string | null;
    per_page?: number;
}

export interface Props {
    overview?: SecurityOverview;
    loginActivity: PaginatedData<SecurityEventItem>;
    recentEvents?: SecurityEventItem[];
    activeSessions: PaginatedData<ActiveSessionItem>;
    authenticationEvents?: string[];
    filters?: SecurityFilters;
}

const props = withDefaults(defineProps<Props>(), {
    overview: () => ({
        successful_logins_today: 0,
        failed_logins_today: 0,
        logouts_today: 0,
        lockouts_today: 0,
        password_reset_requests_today: 0,
        password_resets_today: 0,
        active_sessions: 0,
    }),
    recentEvents: () => [],
    authenticationEvents: () => [],
    filters: () => ({ search: '', event: '', per_page: 20 }),
});

const page = usePage();

// ─── Filter & Search State ──────────────────────────────────────────────────

const search = ref(props.filters?.search ?? '');
const selectedEvent = ref(props.filters?.event ?? '');
const isSearching = ref(false);

const eventOptions = computed(() => [
    { label: 'All Authentication Events', value: '' },
    ...(props.authenticationEvents || []).map((evt) => ({
        label: getEventConfig(evt).label,
        value: evt,
    })),
]);

const hasActiveFilters = computed(() => Boolean(search.value || selectedEvent.value));

const applyFilters = () => {
    isSearching.value = true;
    router.get(
        '/admin/security',
        {
            search: search.value || undefined,
            event: selectedEvent.value || undefined,
            per_page: props.filters?.per_page !== 20 ? props.filters?.per_page : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isSearching.value = false;
            },
        },
    );
};

const handleEventChange = (val: string | number) => {
    selectedEvent.value = String(val);
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    selectedEvent.value = '';
    applyFilters();
};

// ─── Session Revocation Modal State ─────────────────────────────────────────

const sessionToRevoke = ref<ActiveSessionItem | null>(null);
const showRevokeModal = ref(false);
const isRevoking = ref(false);

const canRevokeSessions = computed(() => {
    const authUser = page.props.auth?.user as Record<string, unknown> | null;
    if (!authUser) return false;

    if (authUser.can && typeof authUser.can === 'object') {
        const canObj = authUser.can as Record<string, boolean>;
        if ('security.sessions.revoke' in canObj) {
            return Boolean(canObj['security.sessions.revoke']);
        }
    }

    if (Array.isArray(authUser.permissions)) {
        return authUser.permissions.some(
            (p: unknown) =>
                p === 'security.sessions.revoke' ||
                (typeof p === 'object' && p !== null && (p as { name?: string }).name === 'security.sessions.revoke'),
        );
    }

    if (Array.isArray(authUser.all_permissions)) {
        return authUser.all_permissions.includes('security.sessions.revoke');
    }

    const authPermissions = (page.props.auth as Record<string, unknown>)?.permissions;
    if (Array.isArray(authPermissions)) {
        return authPermissions.includes('security.sessions.revoke');
    }

    return true;
});

const isCurrentSession = (session: ActiveSessionItem): boolean => {
    if (session.is_current) return true;
    const currentUserId = page.props.auth?.user?.id;
    if (currentUserId && session.user_id === currentUserId && session.is_current) {
        return true;
    }
    return Boolean(session.is_current);
};

const openRevokeModal = (session: ActiveSessionItem) => {
    sessionToRevoke.value = session;
    showRevokeModal.value = true;
};

const closeRevokeModal = () => {
    if (isRevoking.value) return;
    showRevokeModal.value = false;
    sessionToRevoke.value = null;
};

const confirmRevokeSession = () => {
    if (!sessionToRevoke.value || isRevoking.value) return;
    isRevoking.value = true;

    router.post(
        `/admin/security/sessions/${sessionToRevoke.value.id}/revoke`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closeRevokeModal();
            },
            onFinish: () => {
                isRevoking.value = false;
            },
        },
    );
};

// ─── Format & Presentation Helpers ──────────────────────────────────────────

interface EventConfig {
    label: string;
    variant: 'active' | 'pending' | 'suspended' | 'cancelled' | 'invited' | 'revoked' | 'default' | 'neutral';
    iconColor: string;
}

function getEventConfig(event: string): EventConfig {
    switch (event) {
        case 'auth.login.success':
            return {
                label: 'Login Successful',
                variant: 'active',
                iconColor: 'text-emerald-600 dark:text-emerald-400',
            };
        case 'auth.login.failed':
            return {
                label: 'Login Failed',
                variant: 'cancelled',
                iconColor: 'text-rose-600 dark:text-rose-400',
            };
        case 'auth.logout':
            return {
                label: 'Logout',
                variant: 'neutral',
                iconColor: 'text-zinc-500 dark:text-zinc-400',
            };
        case 'auth.lockout':
            return {
                label: 'Account Lockout',
                variant: 'cancelled',
                iconColor: 'text-rose-600 dark:text-rose-400',
            };
        case 'auth.password.reset.requested':
            return {
                label: 'Password Reset Requested',
                variant: 'pending',
                iconColor: 'text-amber-600 dark:text-amber-400',
            };
        case 'auth.password.reset.completed':
            return {
                label: 'Password Reset Completed',
                variant: 'active',
                iconColor: 'text-emerald-600 dark:text-emerald-400',
            };
        case 'security.session.revoked':
            return {
                label: 'Session Revoked',
                variant: 'cancelled',
                iconColor: 'text-rose-600 dark:text-rose-400',
            };
        default:
            return {
                label: event
                    .replace(/^auth\./, '')
                    .replace(/^security\./, '')
                    .replace(/[._]/g, ' ')
                    .replace(/\b\w/g, (c) => c.toUpperCase()),
                variant: 'neutral',
                iconColor: 'text-zinc-500 dark:text-zinc-400',
            };
    }
}

function parseUserAgent(ua: string | null | undefined): { browser: string; os: string; full: string } {
    if (!ua || ua.trim() === '') {
        return { browser: 'Unknown', os: 'Unknown', full: 'Unknown Device' };
    }

    let os = 'Unknown OS';
    if (/macintosh|mac os x/i.test(ua)) os = 'macOS';
    else if (/windows|win32/i.test(ua)) os = 'Windows';
    else if (/android/i.test(ua)) os = 'Android';
    else if (/ipad|tablet/i.test(ua)) os = 'iPad';
    else if (/iphone/i.test(ua)) os = 'iPhone';
    else if (/linux/i.test(ua)) os = 'Linux';

    let browser = 'Unknown Browser';
    if (/edg/i.test(ua)) browser = 'Edge';
    else if (/opr|opera/i.test(ua)) browser = 'Opera';
    else if (/chrome|crios/i.test(ua)) browser = 'Chrome';
    else if (/firefox|fxios/i.test(ua)) browser = 'Firefox';
    else if (/safari/i.test(ua)) browser = 'Safari';

    return {
        browser,
        os,
        full: `${browser} · ${os}`,
    };
}

function formatRelativeTime(dateInput: Date | string | number | null | undefined): string {
    if (!dateInput) return '—';
    const date = typeof dateInput === 'object' ? dateInput : new Date(dateInput);
    if (isNaN(date.getTime())) return '—';

    const now = new Date();
    const diffSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffSeconds < 60) return 'Just now';
    if (diffSeconds < 3600) return `${Math.floor(diffSeconds / 60)}m ago`;
    if (diffSeconds < 86400) return `${Math.floor(diffSeconds / 3600)}h ago`;
    if (diffSeconds < 604800) return `${Math.floor(diffSeconds / 86400)}d ago`;

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(dateInput: Date | string | number | null | undefined): string {
    if (!dateInput) return '—';
    const date = typeof dateInput === 'object' ? dateInput : new Date(dateInput);
    if (isNaN(date.getTime())) return '—';

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function formatSessionActivity(timestamp: number | string | null | undefined): string {
    if (!timestamp) return '—';
    const num = typeof timestamp === 'string' ? Number(timestamp) : timestamp;
    if (isNaN(num)) return String(timestamp);
    const date = num < 10000000000 ? new Date(num * 1000) : new Date(num);
    return formatRelativeTime(date);
}

function formatSessionDateTime(timestamp: number | string | null | undefined): string {
    if (!timestamp) return '—';
    const num = typeof timestamp === 'string' ? Number(timestamp) : timestamp;
    if (isNaN(num)) return String(timestamp);
    const date = num < 10000000000 ? new Date(num * 1000) : new Date(num);
    return formatDateTime(date);
}

const getInitials = (name?: string | null): string => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};
</script>

<template>
    <AdminLayout
        title="Security Center"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Security Center' },
        ]"
    >
        <!-- ─── Page Header ──────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Security Center
                    </h1>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        System Guard Active
                    </span>
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Monitor authentication, sessions and security activity.
                </p>
            </div>

            <!-- Active Sessions Status Pill -->
            <div class="flex items-center gap-3">
                <span
                    v-if="hasActiveFilters"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 dark:text-primary-400"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-primary-500" />
                    Filters Active ({{ loginActivity.total }} matching)
                </span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                >
                    <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Updated Realtime
                </span>
            </div>
        </div>

        <!-- ─── 1. Security Overview Cards (6 Metrics) ─────────────────────── -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <!-- 1. Successful Logins Today -->
            <StatsCard
                title="Successful Logins"
                :value="overview.successful_logins_today"
                subtitle="Authentications today"
                badge-color="emerald"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- 2. Failed Logins Today -->
            <StatsCard
                title="Failed Logins"
                :value="overview.failed_logins_today"
                subtitle="Invalid attempts today"
                badge-color="rose"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- 3. Active Sessions -->
            <StatsCard
                title="Active Sessions"
                :value="overview.active_sessions"
                subtitle="Concurrent user sessions"
                badge-color="blue"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- 4. Lockouts Today -->
            <StatsCard
                title="Lockouts Today"
                :value="overview.lockouts_today"
                subtitle="Throttled rate limits"
                badge-color="amber"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- 5. Password Reset Requests -->
            <StatsCard
                title="Reset Requests"
                :value="overview.password_reset_requests_today"
                subtitle="Delivery requests today"
                badge-color="indigo"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </template>
            </StatsCard>

            <!-- 6. Password Resets Completed -->
            <StatsCard
                title="Resets Completed"
                :value="overview.password_resets_today"
                subtitle="Passwords updated today"
                badge-color="zinc"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </template>
            </StatsCard>
        </div>

        <!-- ─── 2. Active Sessions Card ──────────────────────────────────── -->
        <div class="mt-8">
            <Card :no-padding="true">
                <template #header>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Active Sessions
                                </h2>
                                <span
                                    class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
                                >
                                    {{ activeSessions.total }} Sessions
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Live user sessions across web guards and authenticated devices.
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Active Sessions Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="border-b border-zinc-200 bg-zinc-50/75 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-semibold text-zinc-700 dark:text-zinc-300">User</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Email</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Browser / Device</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-zinc-700 dark:text-zinc-300">IP Address</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Last Activity</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                                <th scope="col" class="px-6 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            <!-- Empty State -->
                            <tr v-if="activeSessions.data.length === 0">
                                <td colspan="7" class="p-8">
                                    <EmptyState
                                        title="No active sessions found"
                                        description="No active sessions are recorded in the database session storage."
                                    >
                                        <template #icon>
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </template>
                                    </EmptyState>
                                </td>
                            </tr>

                            <!-- Sessions List -->
                            <tr
                                v-for="session in activeSessions.data"
                                :key="session.id"
                                class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                            >
                                <!-- User -->
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-[11px] font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                        >
                                            {{ getInitials(session.user?.name) }}
                                        </div>
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ session.user?.name || 'Unknown User' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-3.5 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ session.user?.email || '—' }}
                                </td>

                                <!-- Browser / Device -->
                                <td class="px-6 py-3.5 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        {{ parseUserAgent(session.user_agent).full }}
                                    </span>
                                </td>

                                <!-- IP Address -->
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <span class="font-mono text-[11px] text-zinc-600 dark:text-zinc-400">
                                        {{ session.ip_address || '—' }}
                                    </span>
                                </td>

                                <!-- Last Activity -->
                                <td class="px-6 py-3.5 whitespace-nowrap text-zinc-600 dark:text-zinc-400" :title="formatSessionDateTime(session.last_activity)">
                                    {{ formatSessionActivity(session.last_activity) }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <Badge
                                        v-if="isCurrentSession(session)"
                                        variant="active"
                                        label="Current Session"
                                    />
                                    <Badge
                                        v-else
                                        variant="neutral"
                                        label="Active"
                                    />
                                </td>

                                <!-- Action -->
                                <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                    <span
                                        v-if="isCurrentSession(session)"
                                        class="text-xs italic text-zinc-400 dark:text-zinc-500"
                                    >
                                        This device
                                    </span>
                                    <Button
                                        v-else-if="canRevokeSessions"
                                        variant="danger"
                                        size="xs"
                                        @click="openRevokeModal(session)"
                                    >
                                        Revoke
                                    </Button>
                                    <span
                                        v-else
                                        class="text-xs text-zinc-400 dark:text-zinc-500"
                                    >
                                        Protected
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sessions Pagination -->
                <template #footer>
                    <Pagination
                        v-if="activeSessions.links && activeSessions.total > activeSessions.per_page"
                        :data="activeSessions"
                    />
                </template>
            </Card>
        </div>

        <!-- ─── 3. Grid: Login Activity & Recent Events ───────────────────── -->
        <div class="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-3">
            <!-- Left 2 Cols: Login Activity Table -->
            <div class="xl:col-span-2">
                <Card :no-padding="true">
                    <template #header>
                        <div class="space-y-4 w-full">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2.5">
                                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                            Login Activity
                                        </h2>
                                        <span
                                            class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                        >
                                            {{ loginActivity.total }} Events
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Detailed trail of platform authentications and credential verification.
                                    </p>
                                </div>

                                <Button
                                    v-if="hasActiveFilters"
                                    variant="ghost"
                                    size="xs"
                                    @click="clearFilters"
                                >
                                    Clear Filters
                                </Button>
                            </div>

                            <!-- Filter & Search Controls -->
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2">
                                <!-- Search Input -->
                                <div class="flex-1 min-w-[200px]">
                                    <SearchInput
                                        v-model="search"
                                        placeholder="Search by user, email, IP..."
                                        :show-button="false"
                                        @search="applyFilters"
                                        @clear="applyFilters"
                                    />
                                </div>

                                <!-- Event Filter Select -->
                                <div class="w-full sm:w-64">
                                    <Select
                                        v-model="selectedEvent"
                                        :options="eventOptions"
                                        placeholder="All Authentication Events"
                                        @change="handleEventChange"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="border-b border-zinc-200 bg-zinc-50/75 dark:border-zinc-800 dark:bg-zinc-800/40">
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-semibold text-zinc-700 dark:text-zinc-300">User</th>
                                    <th scope="col" class="px-5 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Event</th>
                                    <th scope="col" class="px-5 py-3 font-semibold text-zinc-700 dark:text-zinc-300">IP Address</th>
                                    <th scope="col" class="px-5 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Device / Browser</th>
                                    <th scope="col" class="px-5 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Time</th>
                                    <th scope="col" class="px-5 py-3 font-semibold text-zinc-700 dark:text-zinc-300">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                <!-- Empty State -->
                                <tr v-if="loginActivity.data.length === 0">
                                    <td colspan="6" class="p-8">
                                        <EmptyState
                                            title="No login activity found"
                                            :description="hasActiveFilters
                                                ? 'No authentications matched the search criteria or selected event filter.'
                                                : 'No login activity has been recorded yet.'"
                                        >
                                            <template #icon>
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                                </svg>
                                            </template>
                                            <template v-if="hasActiveFilters" #actions>
                                                <Button variant="secondary" size="sm" @click="clearFilters">
                                                    Reset Filters
                                                </Button>
                                            </template>
                                        </EmptyState>
                                    </td>
                                </tr>

                                <!-- Event Row -->
                                <tr
                                    v-for="activity in loginActivity.data"
                                    :key="activity.id"
                                    class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30"
                                >
                                    <!-- User -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold"
                                                :class="activity.actor
                                                    ? 'bg-primary-100 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300'
                                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'"
                                            >
                                                {{ getInitials(activity.actor?.name || activity.metadata?.identifier) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-zinc-900 dark:text-white">
                                                    {{ activity.actor?.name || activity.metadata?.identifier || 'Anonymous' }}
                                                </p>
                                                <p class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">
                                                    {{ activity.actor?.email || (activity.metadata?.user_found === false ? 'Unregistered Account' : '—') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Event Badge -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <Badge
                                            :variant="getEventConfig(activity.event).variant"
                                            :label="getEventConfig(activity.event).label"
                                        />
                                    </td>

                                    <!-- IP Address -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="font-mono text-[11px] text-zinc-600 dark:text-zinc-400">
                                            {{ activity.ip_address || '—' }}
                                        </span>
                                    </td>

                                    <!-- Device / Browser -->
                                    <td class="px-5 py-3.5 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                                        {{ parseUserAgent(activity.user_agent).full }}
                                    </td>

                                    <!-- Time -->
                                    <td class="px-5 py-3.5 whitespace-nowrap text-zinc-600 dark:text-zinc-400" :title="formatDateTime(activity.created_at)">
                                        {{ formatRelativeTime(activity.created_at) }}
                                    </td>

                                    <!-- Details (Safe Metadata Only) -->
                                    <td class="px-5 py-3.5 text-zinc-500 dark:text-zinc-400">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span
                                                v-if="activity.metadata?.guard"
                                                class="inline-flex items-center rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                                            >
                                                guard: {{ activity.metadata.guard }}
                                            </span>
                                            <span
                                                v-if="activity.metadata?.user_found === false"
                                                class="inline-flex items-center rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"
                                            >
                                                user not found
                                            </span>
                                            <span
                                                v-if="activity.metadata?.remember"
                                                class="inline-flex items-center rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"
                                            >
                                                remembered
                                            </span>
                                            <span
                                                v-if="activity.metadata?.path"
                                                class="inline-flex items-center rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-mono text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 truncate max-w-[120px]"
                                                :title="String(activity.metadata.path)"
                                            >
                                                {{ activity.metadata.path }}
                                            </span>
                                            <span
                                                v-if="!activity.metadata || Object.keys(activity.metadata).length === 0"
                                                class="text-zinc-400"
                                            >
                                                —
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <template #footer>
                        <Pagination :data="loginActivity" />
                    </template>
                </Card>
            </div>

            <!-- Right 1 Col: Recent Security Events Timeline -->
            <div class="xl:col-span-1">
                <Card :no-padding="true">
                    <template #header>
                        <div class="flex items-center justify-between w-full">
                            <div>
                                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Recent Security Events
                                </h2>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    Latest chronological security traces.
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                            >
                                {{ recentEvents.length }} Events
                            </span>
                        </div>
                    </template>

                    <!-- Events Feed List -->
                    <div class="p-5">
                        <div v-if="recentEvents.length === 0" class="py-8">
                            <EmptyState
                                title="No security events"
                                description="No recent security events found."
                            >
                                <template #icon>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </template>
                            </EmptyState>
                        </div>

                        <div v-else class="relative space-y-4">
                            <!-- Activity Item -->
                            <div
                                v-for="(eventItem, index) in recentEvents"
                                :key="eventItem.id || index"
                                class="relative flex items-start gap-3 rounded-xl border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800/80 dark:bg-zinc-800/30 transition-all hover:border-zinc-200 dark:hover:border-zinc-700"
                            >
                                <!-- Event Status Dot -->
                                <div
                                    class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-white shadow-2xs dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-700/80"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        :class="getEventConfig(eventItem.event).iconColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            v-if="eventItem.event.includes('success')"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                        <path
                                            v-else-if="eventItem.event.includes('failed') || eventItem.event.includes('lockout')"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                        <path
                                            v-else-if="eventItem.event.includes('reset')"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                        />
                                        <path
                                            v-else
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>

                                <!-- Details -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ getEventConfig(eventItem.event).label }}
                                        </p>
                                        <span
                                            class="text-[10px] text-zinc-400 dark:text-zinc-500 whitespace-nowrap"
                                            :title="formatDateTime(eventItem.created_at)"
                                        >
                                            {{ formatRelativeTime(eventItem.created_at) }}
                                        </span>
                                    </div>

                                    <p class="mt-0.5 text-[11px] text-zinc-600 dark:text-zinc-400 truncate">
                                        {{ eventItem.actor?.name || eventItem.metadata?.identifier || 'Anonymous User' }}
                                    </p>

                                    <div class="mt-1.5 flex items-center gap-2 text-[10px] text-zinc-500 dark:text-zinc-400 font-mono">
                                        <span v-if="eventItem.ip_address">{{ eventItem.ip_address }}</span>
                                        <span v-if="eventItem.user_agent">• {{ parseUserAgent(eventItem.user_agent).browser }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>

        <!-- ─── 4. Revoke Session Confirmation Modal ───────────────────────── -->
        <Modal
            :show="showRevokeModal"
            title="Revoke User Session"
            description="Terminate active session and force sign-out."
            max-width="md"
            @close="closeRevokeModal"
        >
            <div class="space-y-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    Are you sure you want to revoke this session? The user will be signed out from that device.
                </p>

                <!-- Session Preview Card -->
                <div
                    v-if="sessionToRevoke"
                    class="rounded-xl border border-zinc-200/80 bg-zinc-50/75 p-4 text-xs dark:border-zinc-800 dark:bg-zinc-800/40 space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-zinc-500 dark:text-zinc-400">User</span>
                        <span class="font-medium text-zinc-900 dark:text-white">
                            {{ sessionToRevoke.user?.name || 'Unknown' }} ({{ sessionToRevoke.user?.email || '—' }})
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-zinc-500 dark:text-zinc-400">Device</span>
                        <span class="text-zinc-800 dark:text-zinc-200">
                            {{ parseUserAgent(sessionToRevoke.user_agent).full }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-zinc-500 dark:text-zinc-400">IP Address</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">
                            {{ sessionToRevoke.ip_address || '—' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-zinc-500 dark:text-zinc-400">Last Active</span>
                        <span class="text-zinc-800 dark:text-zinc-200">
                            {{ formatSessionActivity(sessionToRevoke.last_activity) }}
                        </span>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    variant="outline"
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
                    @click="confirmRevokeSession"
                >
                    Revoke Session
                </Button>
            </template>
        </Modal>
    </AdminLayout>
</template>
