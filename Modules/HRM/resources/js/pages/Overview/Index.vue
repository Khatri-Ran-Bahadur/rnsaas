<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import HRMStatCard from '../../components/common/HRMStatCard.vue';
import HRMStatusBadge from '../../components/common/HRMStatusBadge.vue';
import type { HRMOverviewStats, AttendanceBreakdownItem, RecentHRActivityItem } from '../../types/overview';

// TODO BACKEND CONTRACT: Overview aggregation endpoint
const dateRange = ref<'today' | 'week' | 'month' | 'custom'>('today');

const stats = ref<HRMOverviewStats>({
    totalEmployees: 48,
    presentToday: 41,
    absentToday: 3,
    onLeaveToday: 2,
    lateToday: 4,
    pendingLeaves: 5,
    pendingCorrections: 2,
    overtimeHoursToday: 14.5,
});

const breakdown = ref<AttendanceBreakdownItem[]>([
    { label: 'Present', count: 41, percentage: 85, color: 'bg-emerald-500' },
    { label: 'Late', count: 4, percentage: 8, color: 'bg-amber-500' },
    { label: 'Absent', count: 3, percentage: 6, color: 'bg-rose-500' },
    { label: 'On Leave', count: 2, percentage: 4, color: 'bg-purple-500' },
]);

const recentActivities = ref<RecentHRActivityItem[]>([
    {
        id: 'act-1',
        type: 'leave_approved',
        title: 'Annual Leave Approved',
        description: 'Approved 3 days leave for Pooja Thapa (EMP-0034)',
        actor: 'Admin',
        timestamp: '15 mins ago',
    },
    {
        id: 'act-2',
        type: 'attendance_corrected',
        title: 'Attendance Punch Corrected',
        description: 'Updated check-in to 09:00 for Aarav Sharma',
        actor: 'HR Manager',
        timestamp: '1 hour ago',
    },
    {
        id: 'act-3',
        type: 'import_completed',
        title: 'Biometric Attendance Imported',
        description: 'Imported 108 valid records from fingerprint logs',
        actor: 'System',
        timestamp: '3 hours ago',
    },
    {
        id: 'act-4',
        type: 'overtime_approved',
        title: 'Overtime Approved',
        description: 'Approved 2.5h overtime for Bikash Rana',
        actor: 'Admin',
        timestamp: '5 hours ago',
    },
]);
</script>

<template>
    <OrganizationLayout
        title="HRM Overview"
        :breadcrumbs="[
            { label: 'HRM' },
            { label: 'Overview' },
        ]"
    >
        <Head title="HRM Overview" />

        <div class="w-full space-y-6">
            <!-- Header with Date Range Selectors -->
            <HRMPageHeader
                title="HRM Overview"
                subtitle="High-level dashboard monitoring daily attendance, workforce activity, and pending approvals."
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Overview' },
                ]"
            >
                <template #actions>
                    <!-- Date Range Switcher -->
                    <div class="flex items-center rounded-lg border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900 shadow-xs">
                        <button
                            v-for="r in ['today', 'week', 'month', 'custom'] as const"
                            :key="r"
                            type="button"
                            :class="[
                                'rounded-md px-3 py-1 text-xs font-semibold capitalize transition-colors',
                                dateRange === r
                                    ? 'bg-indigo-600 text-white shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white',
                            ]"
                            @click="dateRange = r"
                        >
                            {{ r === 'today' ? 'Today' : r === 'week' ? 'This Week' : r === 'month' ? 'This Month' : 'Custom' }}
                        </button>
                    </div>
                </template>
            </HRMPageHeader>

            <!-- 8 KPI Cards Grid -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-4">
                <HRMStatCard
                    title="Total Employees"
                    :value="stats.totalEmployees"
                    color="indigo"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </template>
                </HRMStatCard>

                <HRMStatCard
                    title="Present Today"
                    :value="stats.presentToday"
                    change="85%"
                    trend="up"
                    color="emerald"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </HRMStatCard>

                <HRMStatCard
                    title="Absent Today"
                    :value="stats.absentToday"
                    color="rose"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                </HRMStatCard>

                <HRMStatCard
                    title="On Leave"
                    :value="stats.onLeaveToday"
                    color="purple"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </template>
                </HRMStatCard>

                <HRMStatCard
                    title="Late Today"
                    :value="stats.lateToday"
                    color="amber"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </HRMStatCard>

                <HRMStatCard
                    title="Pending Leaves"
                    :value="stats.pendingLeaves"
                    color="amber"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </template>
                </HRMStatCard>

                <HRMStatCard
                    title="Corrections Queue"
                    :value="stats.pendingCorrections"
                    color="indigo"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </template>
                </HRMStatCard>

                <HRMStatCard
                    title="Overtime Hours"
                    :value="`${stats.overtimeHoursToday}h`"
                    color="sky"
                >
                    <template #icon>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </HRMStatCard>
            </div>

            <!-- Middle Section: Attendance Breakdown & Action Center -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left 2 Cols: Attendance Breakdown -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-zinc-200/80 pb-4 dark:border-zinc-800">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Today's Attendance Distribution
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">
                                Real-time ratio of workforce punch presence
                            </p>
                        </div>
                        <Link
                            href="/admin/hrm/attendances"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                        >
                            View Live Punch Board →
                        </Link>
                    </div>

                    <!-- Progress Bar Representation -->
                    <div class="mt-6 flex h-4 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div
                            v-for="b in breakdown"
                            :key="b.label"
                            :class="[b.color, 'h-full transition-all duration-500']"
                            :style="{ width: `${b.percentage}%` }"
                            :title="`${b.label}: ${b.percentage}%`"
                        />
                    </div>

                    <!-- Breakdown items -->
                    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div
                            v-for="b in breakdown"
                            :key="b.label"
                            class="rounded-lg border border-zinc-100 bg-zinc-50/70 p-3 dark:border-zinc-800 dark:bg-zinc-950/40"
                        >
                            <div class="flex items-center gap-2">
                                <span :class="[b.color, 'h-2.5 w-2.5 rounded-full']" />
                                <span class="text-xs font-medium text-slate-600 dark:text-zinc-400">{{ b.label }}</span>
                            </div>
                            <div class="mt-2 flex items-baseline justify-between">
                                <span class="text-lg font-bold text-slate-900 dark:text-white">{{ b.count }}</span>
                                <span class="text-xs font-medium text-slate-500">{{ b.percentage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right 1 Col: Quick Action Shortcuts -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">
                        HR Operations
                    </h3>
                    <div class="space-y-2.5 text-xs">
                        <Link
                            href="/admin/hrm/attendances/create"
                            class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 font-medium text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60 transition-colors"
                        >
                            <span>Record Punch Manually</span>
                            <span class="text-indigo-600 font-bold">+</span>
                        </Link>
                        <Link
                            href="/admin/hrm/leaves/create"
                            class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 font-medium text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60 transition-colors"
                        >
                            <span>Apply for Leave</span>
                            <span class="text-indigo-600 font-bold">+</span>
                        </Link>
                        <Link
                            href="/admin/hrm/overtimes/create"
                            class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 font-medium text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60 transition-colors"
                        >
                            <span>Log Overtime Hours</span>
                            <span class="text-indigo-600 font-bold">+</span>
                        </Link>
                        <Link
                            href="/admin/hrm/attendances"
                            class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 font-medium text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60 transition-colors"
                        >
                            <span>Excel Attendance Import</span>
                            <span class="text-indigo-600 font-bold">↗</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent HR Activity Audit Trail -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white border-b border-zinc-200/80 pb-3 dark:border-zinc-800 mb-4">
                    Recent HR Activity
                </h3>

                <div class="divide-y divide-zinc-200/80 dark:divide-zinc-800 text-xs">
                    <div
                        v-for="act in recentActivities"
                        :key="act.id"
                        class="flex items-center justify-between py-3 hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30 px-2 rounded-lg transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 font-bold dark:bg-indigo-950 dark:text-indigo-400">
                                ⚡
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">
                                    {{ act.title }}
                                </p>
                                <p class="text-slate-500 dark:text-zinc-400">
                                    {{ act.description }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="font-medium text-slate-700 dark:text-zinc-300 block">
                                {{ act.actor }}
                            </span>
                            <span class="text-[11px] text-slate-400">{{ act.timestamp }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
