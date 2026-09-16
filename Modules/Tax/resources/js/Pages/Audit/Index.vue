<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Button, Card, Badge, DataTable, type TableColumn } from '@/components';

interface AuditLogItem {
    id: number;
    event_type: string;
    target_entity: string;
    description: string;
    user_name: string;
    ip_address?: string;
    old_values?: Record<string, any> | null;
    new_values?: Record<string, any> | null;
    created_at: string;
}

const props = defineProps<{
    auditLogs: AuditLogItem[];
    filters: {
        search?: string;
        event_type?: string;
    };
}>();

const selectedEvent = ref(props.filters.event_type || '');
const search = ref(props.filters.search || '');

const applyFilters = () => {
    router.get(
        '/admin/tax/audit',
        {
            search: search.value || undefined,
            event_type: selectedEvent.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const columns: TableColumn[] = [
    { key: 'timestamp', label: 'Timestamp', sortable: true },
    { key: 'event', label: 'Action & Entity' },
    { key: 'description', label: 'Audit Change Details' },
    { key: 'values', label: 'Old vs New Values' },
    { key: 'actor', label: 'Actor / IP' },
];

const getEventBadge = (type: string) => {
    switch (type) {
        case 'rate_scheduled':
            return { label: 'Rate Scheduled', class: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200' };
        case 'exemption_approved':
            return { label: 'Exemption Granted', class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200' };
        case 'rule_created':
            return { label: 'Rule Created', class: 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200' };
        case 'settings_updated':
            return { label: 'Settings Updated', class: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200' };
        default:
            return { label: type.replace('_', ' '), class: 'bg-zinc-100 text-zinc-700' };
    }
};
</script>

<template>
    <OrganizationLayout>
        <Head title="Tax Audit Trail & Historical Logs" />

        <div class="space-y-6 max-w-7xl mx-auto pb-16">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-4">
                <div>
                    <nav class="flex items-center gap-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/dashboard" class="hover:text-zinc-700 dark:hover:text-zinc-300">Dashboard</Link>
                        <span>/</span>
                        <Link href="/admin/tax" class="hover:text-zinc-700 dark:hover:text-zinc-300">Tax</Link>
                        <span>/</span>
                        <span class="text-zinc-900 dark:text-white font-medium">Audit Trail</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                        Tax Audit Trail & Compliance Changes
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Tamper-evident record of all tax rate modifications, rule updates, exemptions granted, and business regime changes.
                    </p>
                </div>
            </div>

            <!-- Filter Card -->
            <Card class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by actor, entity, or description..."
                            class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <select
                            v-model="selectedEvent"
                            @change="applyFilters"
                            class="w-full px-2.5 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                        >
                            <option value="">All Change Events</option>
                            <option value="rate_scheduled">Rate Scheduled / Changed</option>
                            <option value="exemption_approved">Exemption Approved</option>
                            <option value="rule_created">Rule Configured</option>
                            <option value="settings_updated">Settings Updated</option>
                        </select>
                    </div>
                </div>
            </Card>

            <!-- Audit Logs Table -->
            <Card class="p-0 overflow-hidden">
                <DataTable :columns="columns" :data="auditLogs">
                    <template #cell-timestamp="{ row }">
                        <span class="font-mono text-xs text-zinc-500">{{ row.created_at }}</span>
                    </template>

                    <template #cell-event="{ row }">
                        <div>
                            <span :class="['px-2 py-0.5 rounded text-[10px] font-semibold uppercase border', getEventBadge(row.event_type).class]">
                                {{ getEventBadge(row.event_type).label }}
                            </span>
                            <div class="font-semibold text-xs text-zinc-900 dark:text-white mt-1">
                                {{ row.target_entity }}
                            </div>
                        </div>
                    </template>

                    <template #cell-description="{ row }">
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">{{ row.description }}</span>
                    </template>

                    <template #cell-values="{ row }">
                        <div class="space-y-1 font-mono text-[11px]">
                            <div v-if="row.old_values" class="text-rose-600 dark:text-rose-400">
                                <strong>Old:</strong> {{ JSON.stringify(row.old_values) }}
                            </div>
                            <div v-if="row.new_values" class="text-emerald-600 dark:text-emerald-400">
                                <strong>New:</strong> {{ JSON.stringify(row.new_values) }}
                            </div>
                        </div>
                    </template>

                    <template #cell-actor="{ row }">
                        <div class="text-xs">
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ row.user_name }}</div>
                            <div class="font-mono text-[10px] text-zinc-400">{{ row.ip_address || 'Internal' }}</div>
                        </div>
                    </template>
                </DataTable>
            </Card>
        </div>
    </OrganizationLayout>
</template>
