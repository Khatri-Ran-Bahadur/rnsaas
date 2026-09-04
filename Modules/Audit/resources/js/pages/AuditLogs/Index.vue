<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import Pagination from '@/components/Pagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import SearchInput from '@/components/SearchInput.vue';
import FilterButton from '@/components/FilterButton.vue';

export interface TenantSummary {
    id: number;
    name: string;
    slug: string;
}

export interface ActorSummary {
    id?: number;
    name?: string;
    email?: string;
    [key: string]: any;
}

export interface AuditableSummary {
    id?: number;
    [key: string]: any;
}

export interface AuditLog {
    id: number;
    public_id: string;
    tenant_id: number | null;
    actor_type: string | null;
    actor_id: number | null;
    event: string;
    auditable_type: string | null;
    auditable_id: number | null;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    metadata: Record<string, any> | null;
    request_id: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    tenant?: TenantSummary | null;
    actor?: ActorSummary | null;
    auditable?: AuditableSummary | null;
}

export interface PaginatedAuditLogs {
    data: AuditLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface FilterState {
    search?: string;
    event?: string;
    tenant_id?: number | string;
    date_from?: string;
    date_to?: string;
    per_page?: number;
}

const props = defineProps<{
    auditLogs: PaginatedAuditLogs;
    filters?: FilterState;
    events: string[];
    tenants: Array<{ id: number; name: string; slug: string }>;
}>();

// Search & Filter State
const search = ref(props.filters?.search ?? '');
const selectedEvent = ref(props.filters?.event ?? '');
const selectedTenant = ref(props.filters?.tenant_id ? String(props.filters.tenant_id) : '');
const dateFrom = ref(props.filters?.date_from ?? '');
const dateTo = ref(props.filters?.date_to ?? '');
const perPage = ref(props.filters?.per_page ?? 20);
const showFilters = ref(Boolean(props.filters?.event || props.filters?.tenant_id || props.filters?.date_from || props.filters?.date_to));

// Detail Modal State
const selectedLog = ref<AuditLog | null>(null);
const isModalOpen = ref(false);
const copiedField = ref<string | null>(null);

const activeFiltersCount = computed(() => {
    return [
        selectedEvent.value,
        selectedTenant.value,
        dateFrom.value,
        dateTo.value,
    ].filter(Boolean).length;
});

const eventOptions = computed(() => {
    const list = props.events.map((e) => ({ label: e, value: e }));
    return [{ label: 'All Events', value: '' }, ...list];
});

const tenantOptions = computed(() => {
    const list = props.tenants.map((t) => ({
        label: `${t.name} (${t.slug})`,
        value: String(t.id),
    }));
    return [{ label: 'All Organizations', value: '' }, ...list];
});

const applyFilters = () => {
    router.get(
        '/superadmin/audit-logs',
        {
            search: search.value || undefined,
            event: selectedEvent.value || undefined,
            tenant_id: selectedTenant.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            per_page: perPage.value !== 20 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const handlePerPageChange = (newPerPage: number) => {
    perPage.value = newPerPage;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    selectedEvent.value = '';
    selectedTenant.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const openDetailModal = (log: AuditLog) => {
    selectedLog.value = log;
    isModalOpen.value = true;
};

const closeDetailModal = () => {
    isModalOpen.value = false;
    selectedLog.value = null;
};

const copyToClipboard = (text: string, field: string) => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
        copiedField.value = field;
        setTimeout(() => {
            copiedField.value = null;
        }, 2000);
    }
};

const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const formatShortDate = (dateStr: string) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatTimeOnly = (dateStr: string) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const getEventBadgeClass = (eventName: string) => {
    const e = eventName.toLowerCase();
    if (e.includes('canceled') || e.includes('suspended') || e.includes('revoked') || e.includes('expired') || e.includes('failed')) {
        return 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/60';
    }
    if (e.includes('created') || e.includes('activated') || e.includes('paid') || e.includes('received')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/60';
    }
    if (e.includes('tenant') || e.includes('subscription')) {
        return 'bg-primary-50 text-primary-700 border-primary-200/80 dark:bg-primary-950/40 dark:text-primary-300 dark:border-primary-900/60';
    }
    if (e.includes('invite') || e.includes('member')) {
        return 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/60';
    }
    return 'bg-zinc-100 text-zinc-700 border-zinc-200/80 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700';
};

const getShortAuditableType = (typeStr: string | null, id: number | null) => {
    if (!typeStr) return '—';
    const parts = typeStr.split('\\');
    const modelName = parts[parts.length - 1];
    return id ? `${modelName} #${id}` : modelName;
};
</script>

<template>
    <AdminLayout
        title="Audit Logs"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Audit Logs' },
        ]"
    >
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Audit Logs
                    </h1>
                    <span class="inline-flex items-center rounded-md bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200/80 dark:border-zinc-700/80">
                        Read-Only Platform Journal
                    </span>
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Immutable security and audit journal recording all platform events, state mutations, and operational activities.
                </p>
            </div>
        </div>

        <!-- Main Card Container -->
        <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200/90 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <!-- Search & Controls Header -->
            <div class="p-4 sm:p-5 border-b border-zinc-200/90 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-900/60">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="flex-1 max-w-md">
                        <SearchInput
                            v-model="search"
                            placeholder="Search by event, request ID, organization, or actor..."
                            @search="applyFilters"
                            @clear="resetFilters"
                        />
                    </div>

                    <!-- Right Controls -->
                    <div class="flex items-center gap-2.5 self-end sm:self-auto">
                        <!-- Per Page Selector -->
                        <PerPageSelector
                            v-model="perPage"
                            :options="[10, 20, 50, 100]"
                            @change="handlePerPageChange"
                        />

                        <!-- Advanced Filter Toggle Button -->
                        <FilterButton
                            :show-filters="showFilters"
                            :count="activeFiltersCount"
                            @toggle="showFilters = !showFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Expandable Filters Row -->
            <div
                v-if="showFilters"
                class="p-4 sm:p-5 bg-zinc-50/90 border-b border-zinc-200/90 dark:bg-zinc-900/90 dark:border-zinc-800"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <!-- Event Filter -->
                    <div>
                        <Select
                            v-model="selectedEvent"
                            label="Audit Event"
                            :options="eventOptions"
                            placeholder="All Events"
                            searchable
                        />
                    </div>

                    <!-- Organization / Tenant Filter -->
                    <div>
                        <Select
                            v-model="selectedTenant"
                            label="Organization"
                            :options="tenantOptions"
                            placeholder="All Organizations"
                            searchable
                        />
                    </div>

                    <!-- Date From -->
                    <div>
                        <DatePicker
                            v-model="dateFrom"
                            label="From Date"
                            placeholder="Start date (YYYY-MM-DD)"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <DatePicker
                            v-model="dateTo"
                            label="To Date"
                            placeholder="End date (YYYY-MM-DD)"
                        />
                    </div>
                </div>

                <!-- Filter Action Buttons -->
                <div class="mt-4 flex items-center justify-end gap-2.5 pt-3 border-t border-zinc-200/60 dark:border-zinc-800/60">
                    <Button
                        v-if="search || selectedEvent || selectedTenant || dateFrom || dateTo"
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="resetFilters"
                    >
                        Clear Filters
                    </Button>
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        @click="applyFilters"
                    >
                        Apply Filters
                    </Button>
                </div>
            </div>

            <!-- Content Area: Empty State -->
            <div v-if="auditLogs.data.length === 0" class="p-12 text-center">
                <EmptyState
                    title="No Audit Logs Found"
                    description="No platform audit log records match your selected search or filter criteria."
                >
                    <template #actions>
                        <Button
                            v-if="search || selectedEvent || selectedTenant || dateFrom || dateTo"
                            variant="outline"
                            size="sm"
                            @click="resetFilters"
                        >
                            Reset Filters
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <!-- Content Area: Desktop & Mobile Responsive Table -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                    <thead class="border-b border-zinc-200 bg-zinc-100/75 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300">
                        <tr>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Time</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Actor</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Event</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Organization</th>
                            <th scope="col" class="h-11 px-4 text-left align-middle">Auditable</th>
                            <th scope="col" class="h-11 px-4 text-right align-middle">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/80 dark:divide-zinc-800/80">
                        <tr
                            v-for="log in auditLogs.data"
                            :key="log.id"
                            class="cursor-pointer transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40"
                            @click="openDetailModal(log)"
                        >
                            <!-- Time -->
                            <td class="p-4 align-middle whitespace-nowrap">
                                <div class="font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ formatShortDate(log.created_at) }}
                                </div>
                                <div class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500">
                                    {{ formatTimeOnly(log.created_at) }}
                                </div>
                            </td>

                            <!-- Actor -->
                            <td class="p-4 align-middle">
                                <div v-if="log.actor" class="flex items-center gap-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-950/60 dark:text-primary-300">
                                        {{ (log.actor.name ?? 'U').charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate max-w-[140px]" :title="log.actor.name">
                                            {{ log.actor.name ?? 'User' }}
                                        </div>
                                        <div class="text-[11px] text-zinc-400 dark:text-zinc-500 truncate max-w-[140px]" :title="log.actor.email">
                                            {{ log.actor.email ?? `#${log.actor_id}` }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-2 text-xs text-zinc-400 dark:text-zinc-500">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <span class="italic font-medium">System</span>
                                </div>
                            </td>

                            <!-- Event -->
                            <td class="p-4 align-middle whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-mono font-medium"
                                    :class="getEventBadgeClass(log.event)"
                                >
                                    {{ log.event }}
                                </span>
                            </td>

                            <!-- Organization / Tenant -->
                            <td class="p-4 align-middle">
                                <div v-if="log.tenant" class="min-w-0">
                                    <div class="font-medium text-xs text-zinc-900 dark:text-zinc-100 truncate max-w-[150px]" :title="log.tenant.name">
                                        {{ log.tenant.name }}
                                    </div>
                                    <div class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500 truncate max-w-[150px]">
                                        {{ log.tenant.slug }}
                                    </div>
                                </div>
                                <span v-else class="text-xs text-zinc-400 italic">
                                    Platform (Global)
                                </span>
                            </td>

                            <!-- Auditable -->
                            <td class="p-4 align-middle font-mono text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                {{ getShortAuditableType(log.auditable_type, log.auditable_id) }}
                            </td>

                            <!-- Actions -->
                            <td class="p-4 align-middle text-right whitespace-nowrap" @click.stop>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200/90 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700/60 dark:hover:text-white transition-colors cursor-pointer"
                                    title="View detailed audit entry"
                                    @click="openDetailModal(log)"
                                >
                                    <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>Details</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Inside-Card Pagination Footer -->
            <div
                v-if="auditLogs.total > 0"
                class="border-t border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
            >
                <Pagination :data="auditLogs" />
            </div>
        </div>

        <!-- Detail Modal (Strictly Read-Only) -->
        <Modal
            :show="isModalOpen"
            title="Audit Event Log Record"
            description="Immutable platform journal record and execution context."
            max-width="2xl"
            @close="closeDetailModal"
        >
            <div v-if="selectedLog" class="space-y-5">
                <!-- Top Summary Pill / Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200/70 dark:border-zinc-700/60">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Event:</span>
                        <span
                            class="inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-mono font-semibold"
                            :class="getEventBadgeClass(selectedLog.event)"
                        >
                            {{ selectedLog.event }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                        <span>Log ID: #{{ selectedLog.id }}</span>
                        <span v-if="selectedLog.public_id">•</span>
                        <span v-if="selectedLog.public_id" class="truncate max-w-[180px]">{{ selectedLog.public_id }}</span>
                    </div>
                </div>

                <!-- Modal Body: Overview Metadata Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                    <!-- Event Timestamp -->
                    <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-950/50 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="block text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Timestamp</span>
                        <span class="mt-1 block font-mono font-medium text-zinc-900 dark:text-zinc-100">
                            {{ formatDateTime(selectedLog.created_at) }}
                        </span>
                    </div>

                    <!-- Organization -->
                    <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-950/50 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="block text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Organization</span>
                        <div v-if="selectedLog.tenant" class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ selectedLog.tenant.name }}
                            <span class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500">({{ selectedLog.tenant.slug }})</span>
                        </div>
                        <span v-else class="mt-1 block italic text-zinc-400">Platform Global</span>
                    </div>

                    <!-- Actor -->
                    <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-950/50 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="block text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Actor / Triggered By</span>
                        <div v-if="selectedLog.actor" class="mt-1">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ selectedLog.actor.name ?? 'User' }}</span>
                            <span class="block text-[11px] text-zinc-400 dark:text-zinc-500 truncate">{{ selectedLog.actor.email ?? selectedLog.actor_type }}</span>
                        </div>
                        <span v-else class="mt-1 block italic text-zinc-400">System (Automated / Background)</span>
                    </div>

                    <!-- Auditable Entity -->
                    <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-950/50 border border-zinc-100 dark:border-zinc-800/80">
                        <span class="block text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Auditable Resource</span>
                        <span class="mt-1 block font-mono font-medium text-zinc-900 dark:text-zinc-100">
                            {{ getShortAuditableType(selectedLog.auditable_type, selectedLog.auditable_id) }}
                        </span>
                    </div>

                    <!-- Request ID -->
                    <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-950/50 border border-zinc-100 dark:border-zinc-800/80 sm:col-span-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Request ID / Correlation</span>
                            <button
                                v-if="selectedLog.request_id"
                                type="button"
                                class="inline-flex items-center gap-1 font-mono text-[11px] text-primary-600 hover:underline cursor-pointer"
                                @click="copyToClipboard(selectedLog.request_id, 'reqId')"
                            >
                                <span>{{ copiedField === 'reqId' ? 'Copied!' : 'Copy' }}</span>
                            </button>
                        </div>
                        <span class="mt-1 block font-mono text-xs text-zinc-800 dark:text-zinc-200 truncate">
                            {{ selectedLog.request_id || '—' }}
                        </span>
                    </div>

                    <!-- IP Address & User Agent -->
                    <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-950/50 border border-zinc-100 dark:border-zinc-800/80 sm:col-span-2">
                        <div class="flex items-center gap-4">
                            <div>
                                <span class="block text-[11px] font-medium text-zinc-400 uppercase tracking-wider">IP Address</span>
                                <span class="mt-0.5 block font-mono text-xs text-zinc-800 dark:text-zinc-200">
                                    {{ selectedLog.ip_address || '—' }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="block text-[11px] font-medium text-zinc-400 uppercase tracking-wider">User Agent</span>
                                <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400 truncate" :title="selectedLog.user_agent ?? ''">
                                    {{ selectedLog.user_agent || '—' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formatted Values Sections (Old, New, Metadata) -->
                <div class="space-y-4">
                    <!-- Old Values -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Old Values (Prior State)
                            </span>
                            <span v-if="!selectedLog.old_values || Object.keys(selectedLog.old_values).length === 0" class="text-[11px] text-zinc-400 italic">
                                No previous state recorded
                            </span>
                        </div>
                        <div v-if="selectedLog.old_values && Object.keys(selectedLog.old_values).length > 0">
                            <pre class="p-3 rounded-lg bg-zinc-900 text-zinc-100 font-mono text-xs overflow-x-auto max-h-48 border border-zinc-800 shadow-inner select-all">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                        </div>
                        <div v-else class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-950/40 border border-dashed border-zinc-200 dark:border-zinc-800 text-xs text-zinc-400 font-mono">
                            null
                        </div>
                    </div>

                    <!-- New Values -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                New Values (Updated State)
                            </span>
                            <span v-if="!selectedLog.new_values || Object.keys(selectedLog.new_values).length === 0" class="text-[11px] text-zinc-400 italic">
                                No new values recorded
                            </span>
                        </div>
                        <div v-if="selectedLog.new_values && Object.keys(selectedLog.new_values).length > 0">
                            <pre class="p-3 rounded-lg bg-zinc-900 text-zinc-100 font-mono text-xs overflow-x-auto max-h-48 border border-zinc-800 shadow-inner select-all">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                        </div>
                        <div v-else class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-950/40 border border-dashed border-zinc-200 dark:border-zinc-800 text-xs text-zinc-400 font-mono">
                            null
                        </div>
                    </div>

                    <!-- Contextual Metadata -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Metadata & Context
                            </span>
                            <span v-if="!selectedLog.metadata || Object.keys(selectedLog.metadata).length === 0" class="text-[11px] text-zinc-400 italic">
                                None
                            </span>
                        </div>
                        <div v-if="selectedLog.metadata && Object.keys(selectedLog.metadata).length > 0">
                            <pre class="p-3 rounded-lg bg-zinc-900 text-zinc-100 font-mono text-xs overflow-x-auto max-h-48 border border-zinc-800 shadow-inner select-all">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                        </div>
                        <div v-else class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-950/40 border border-dashed border-zinc-200 dark:border-zinc-800 text-xs text-zinc-400 font-mono">
                            null
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer (Strictly Read-Only: Close button only) -->
            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    @click="closeDetailModal"
                >
                    Close
                </Button>
            </template>
        </Modal>
    </AdminLayout>
</template>
