<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import Dropdown from '@/components/Dropdown.vue';
import DatePicker from '@/components/DatePicker.vue';

type ReportTab = 'attendance' | 'leave' | 'overtime' | 'employee' | 'summary';
const activeReport = ref<ReportTab>('attendance');

const fromDate = ref('2026-09-01');
const toDate = ref('2026-09-07');

const exportReport = (format: 'excel' | 'csv' | 'pdf') => {
    alert(`Generating ${format.toUpperCase()} export for ${activeReport.value} report...`);
};
</script>

<template>
    <Head title="HR Reports Center" />

    <OrganizationLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <HRMPageHeader
                title="HR Reports & Analytics"
                subtitle="Generate and export comprehensive reports for attendance, leaves, overtime, and headcount."
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Reports' },
                ]"
            >
                <template #actions>
                    <!-- Export Dropdown -->
                    <Dropdown align="right" width="w-40">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export Report
                            </button>
                        </template>

                        <template #default="{ close }">
                            <div class="py-1 text-xs">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    @click="close(); exportReport('excel')"
                                >
                                    Excel (.xlsx)
                                </button>
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    @click="close(); exportReport('csv')"
                                >
                                    CSV (.csv)
                                </button>
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    @click="close(); exportReport('pdf')"
                                >
                                    PDF Document (.pdf)
                                </button>
                            </div>
                        </template>
                    </Dropdown>
                </template>
            </HRMPageHeader>

            <!-- Report Navigation Tabs -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-800 gap-6 overflow-x-auto text-xs font-semibold">
                <button
                    v-for="r in [
                        { key: 'attendance', label: 'Attendance Report' },
                        { key: 'leave', label: 'Leave Report' },
                        { key: 'overtime', label: 'Overtime Report' },
                        { key: 'employee', label: 'Employee Roster' },
                        { key: 'summary', label: 'HR Executive Summary' },
                    ] as const"
                    :key="r.key"
                    type="button"
                    :class="[
                        'pb-3 whitespace-nowrap transition-colors border-b-2',
                        activeReport === r.key
                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                            : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200',
                    ]"
                    @click="activeReport = r.key"
                >
                    {{ r.label }}
                </button>
            </div>

            <!-- Report Filter Bar -->
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <span class="font-medium text-slate-600 dark:text-zinc-400">Date Range:</span>
                    <div class="w-36">
                        <DatePicker v-model="fromDate" placeholder="From" />
                    </div>
                    <span class="text-slate-400">to</span>
                    <div class="w-36">
                        <DatePicker v-model="toDate" placeholder="To" />
                    </div>
                </div>

                <div class="text-xs text-slate-500">
                    Showing aggregated stats for active organization
                </div>
            </div>

            <!-- ATTENDANCE REPORT TAB -->
            <div v-if="activeReport === 'attendance'" class="space-y-4">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Total Shifts Logged</span>
                        <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">248</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">On-Time Arrival %</span>
                        <p class="mt-1 text-xl font-bold text-emerald-600">92.4%</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Worked Hours</span>
                        <p class="mt-1 text-xl font-bold text-indigo-600">1,940h</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Unexcused Absences</span>
                        <p class="mt-1 text-xl font-bold text-rose-600">6</p>
                    </div>
                </div>
            </div>

            <!-- LEAVE REPORT TAB -->
            <div v-else-if="activeReport === 'leave'" class="space-y-4">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Total Applications</span>
                        <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">32</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Approved Days</span>
                        <p class="mt-1 text-xl font-bold text-emerald-600">64.5</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Rejected Requests</span>
                        <p class="mt-1 text-xl font-bold text-rose-600">3</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Pending Approvals</span>
                        <p class="mt-1 text-xl font-bold text-amber-600">5</p>
                    </div>
                </div>
            </div>

            <!-- OVERTIME REPORT TAB -->
            <div v-else-if="activeReport === 'overtime'" class="space-y-4">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Total Overtime Hours</span>
                        <p class="mt-1 text-xl font-bold text-indigo-600">84.5h</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Approved Claims</span>
                        <p class="mt-1 text-xl font-bold text-emerald-600">18</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Pending Review</span>
                        <p class="mt-1 text-xl font-bold text-amber-600">4</p>
                    </div>
                </div>
            </div>

            <!-- EMPLOYEE REPORT TAB -->
            <div v-else-if="activeReport === 'employee'" class="space-y-4">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Active Staff</span>
                        <p class="mt-1 text-xl font-bold text-emerald-600">48</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Suspended</span>
                        <p class="mt-1 text-xl font-bold text-amber-600">1</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Terminated</span>
                        <p class="mt-1 text-xl font-bold text-rose-600">2</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <span class="text-xs text-slate-500 dark:text-zinc-400">Total Profiles</span>
                        <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">51</p>
                    </div>
                </div>
            </div>

            <!-- HR EXECUTIVE SUMMARY TAB -->
            <div v-else class="space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">
                        Executive Workforce Health Index
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mb-6">
                        Organization attendance compliance rate is at 94.2% across departments this period.
                    </p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-xs">
                        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500">Attendance Compliance</span>
                            <p class="mt-1 text-xl font-bold text-emerald-600">94.2%</p>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500">Average Worked Per Day</span>
                            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-white">7.8 Hours</p>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <span class="text-slate-500">Leave Utilization Rate</span>
                            <p class="mt-1 text-xl font-bold text-indigo-600">6.4%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
