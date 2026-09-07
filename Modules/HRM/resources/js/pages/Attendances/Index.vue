<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import HRMDetailDrawer from '../../components/common/HRMDetailDrawer.vue';
import HRMStatusBadge from '../../components/common/HRMStatusBadge.vue';
import AttendanceStats from '../../components/attendances/AttendanceStats.vue';
import AttendanceFilters from '../../components/attendances/AttendanceFilters.vue';
import AttendanceTable from '../../components/attendances/AttendanceTable.vue';
import AttendanceCalendarView from '../../components/attendances/AttendanceCalendarView.vue';
import AttendanceCorrectionsView from '../../components/attendances/AttendanceCorrectionsView.vue';
import AttendanceImportWorkflow from '../../components/attendances/AttendanceImportWorkflow.vue';
import AttendanceExceptionsView from '../../components/attendances/AttendanceExceptionsView.vue';
import type { AttendanceIndexProps, AttendanceItem } from '../../types/attendances';

const props = defineProps<AttendanceIndexProps>();

type TabKey = 'daily' | 'calendar' | 'corrections' | 'import' | 'exceptions';
const activeTab = ref<TabKey>('daily');

const tabs: Array<{ key: TabKey; label: string }> = [
    { key: 'daily', label: 'Daily Attendance' },
    { key: 'calendar', label: 'Attendance Calendar' },
    { key: 'corrections', label: 'Corrections' },
    { key: 'import', label: 'Excel Import' },
    { key: 'exceptions', label: 'Exceptions' },
];

// Selected row inspection drawer state
const inspectedRecord = ref<AttendanceItem | null>(null);
const showDrawer = ref(false);

const openInspect = (item: AttendanceItem) => {
    inspectedRecord.value = item;
    showDrawer.value = true;
};

// Filter handling
const filters = ref({
    search: props.filters?.search || '',
    tenant_staff_id: props.filters?.tenant_staff_id || null,
    status: props.filters?.status || null,
    source: props.filters?.source || null,
    date: props.filters?.date || null,
    per_page: props.filters?.per_page || 20,
});

const applyFilters = () => {
    router.get(
        route('admin.hrm.attendances.index'),
        {
            search: filters.value.search || undefined,
            tenant_staff_id: filters.value.tenant_staff_id || undefined,
            status: filters.value.status || undefined,
            source: filters.value.source || undefined,
            date: filters.value.date || undefined,
            per_page: filters.value.per_page,
        },
        { preserveState: true, replace: true },
    );
};

const handleResetFilters = () => {
    filters.value.search = '';
    filters.value.tenant_staff_id = null;
    filters.value.status = null;
    filters.value.source = null;
    filters.value.date = null;
    applyFilters();
};

const handleToggleStatus = (item: AttendanceItem) => {
    router.patch(
        route('admin.hrm.attendances.toggle-status', item.public_id),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Attendance Management" />

    <OrganizationLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <!-- Top Header -->
            <HRMPageHeader
                title="Attendance Hub"
                subtitle="Track daily employee punches, approve corrections, and monitor attendance anomalies."
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Attendance' },
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.hrm.attendances.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Record Attendance
                    </Link>
                </template>
            </HRMPageHeader>

            <!-- Navigation Tabs -->
            <div class="mb-6 flex border-b border-zinc-200 dark:border-zinc-800 gap-6 overflow-x-auto">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    type="button"
                    :class="[
                        'pb-3 text-xs font-semibold whitespace-nowrap transition-colors border-b-2',
                        activeTab === t.key
                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                            : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = t.key"
                >
                    {{ t.label }}
                </button>
            </div>

            <!-- TAB 1: DAILY ATTENDANCE -->
            <div v-if="activeTab === 'daily'">
                <!-- KPI Metric Cards -->
                <AttendanceStats :stats="stats" />

                <!-- Filter Controls -->
                <AttendanceFilters
                    v-model:search="filters.search"
                    v-model:staff-id="filters.tenant_staff_id"
                    v-model:status="filters.status"
                    v-model:source="filters.source"
                    v-model:date="filters.date"
                    :staff-members="staffMembers"
                    @update:search="applyFilters"
                    @update:staff-id="applyFilters"
                    @update:status="applyFilters"
                    @update:source="applyFilters"
                    @update:date="applyFilters"
                    @reset="handleResetFilters"
                />

                <!-- Table -->
                <AttendanceTable
                    :attendances="attendances"
                    :per-page="filters.per_page"
                    @update:per-page="filters.per_page = $event; applyFilters()"
                    @toggle-status="handleToggleStatus"
                    @inspect="openInspect"
                />
            </div>

            <!-- TAB 2: CALENDAR -->
            <div v-else-if="activeTab === 'calendar'">
                <AttendanceCalendarView
                    :attendances="attendances.data"
                    @select-day="(_, items) => { if (items.length > 0) openInspect(items[0]); }"
                />
            </div>

            <!-- TAB 3: CORRECTIONS -->
            <div v-else-if="activeTab === 'corrections'">
                <AttendanceCorrectionsView />
            </div>

            <!-- TAB 4: EXCEL IMPORT -->
            <div v-else-if="activeTab === 'import'">
                <AttendanceImportWorkflow />
            </div>

            <!-- TAB 5: EXCEPTIONS -->
            <div v-else-if="activeTab === 'exceptions'">
                <AttendanceExceptionsView />
            </div>
        </div>

        <!-- Detail Inspection Slide-out Drawer -->
        <HRMDetailDrawer
            :show="showDrawer"
            :title="inspectedRecord ? `${inspectedRecord.staff?.name || 'Staff'} - Attendance` : 'Attendance Detail'"
            :subtitle="inspectedRecord ? `Date: ${inspectedRecord.attendance_date}` : ''"
            @close="showDrawer = false"
        >
            <div v-if="inspectedRecord" class="space-y-5 text-xs">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">
                            {{ (inspectedRecord.staff?.name || 'S').charAt(0) }}
                        </div>
                        <div>
                            <p class="font-bold text-sm text-slate-900 dark:text-white">
                                {{ inspectedRecord.staff?.name }}
                            </p>
                            <p class="text-slate-500 dark:text-zinc-400 font-mono">
                                #{{ inspectedRecord.staff?.employee_code }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Check In Time</span>
                        <span class="font-mono font-medium text-slate-900 dark:text-white">{{ inspectedRecord.check_in || '—' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Check Out Time</span>
                        <span class="font-mono font-medium text-slate-900 dark:text-white">{{ inspectedRecord.check_out || '—' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Total Worked</span>
                        <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ inspectedRecord.worked_hours }} hours ({{ inspectedRecord.worked_minutes }}m)</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Late Minutes</span>
                        <span class="font-medium text-amber-600">{{ inspectedRecord.late_minutes }}m</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Early Departure</span>
                        <span class="font-medium text-rose-600">{{ inspectedRecord.early_leave_minutes }}m</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Status</span>
                        <HRMStatusBadge :status="inspectedRecord.status?.value" :label="inspectedRecord.status?.label" />
                    </div>
                    <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-slate-500">Source</span>
                        <span class="uppercase font-mono text-[10px]">{{ inspectedRecord.source?.label }}</span>
                    </div>
                </div>

                <div v-if="inspectedRecord.notes" class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                    <span class="font-semibold text-slate-700 dark:text-zinc-200 block mb-1">Notes:</span>
                    <p class="text-slate-600 dark:text-zinc-400">{{ inspectedRecord.notes }}</p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Link
                        v-if="inspectedRecord"
                        :href="route('admin.hrm.attendances.edit', inspectedRecord.public_id)"
                        class="rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500"
                    >
                        Edit Record
                    </Link>
                </div>
            </template>
        </HRMDetailDrawer>
    </OrganizationLayout>
</template>
