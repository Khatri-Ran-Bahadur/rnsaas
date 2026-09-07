<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import TextInput from '@/components/TextInput.vue';
import Switch from '@/components/Switch.vue';
import type { AttendanceItem } from '../../types/attendances';

const props = defineProps<{
    attendance: {
        data: AttendanceItem;
    };
}>();

const record = computed(() => props.attendance.data);

const form = useForm({
    tenant_staff_id: record.value.created_by || 1, // mapped staff
    attendance_date: record.value.attendance_date,
    check_in: record.value.check_in || '09:00',
    check_out: record.value.check_out || '17:00',
    late_minutes: record.value.late_minutes,
    early_leave_minutes: record.value.early_leave_minutes,
    overtime_minutes: record.value.overtime_minutes,
    status: record.value.status?.value || 'present',
    source: record.value.source?.value || 'manual',
    notes: record.value.notes || '',
    is_active: record.value.is_active,
});

const statusOptions = [
    { value: 'present', label: 'Present' },
    { value: 'absent', label: 'Absent' },
    { value: 'late', label: 'Late' },
    { value: 'half_day', label: 'Half Day' },
    { value: 'on_leave', label: 'On Leave' },
    { value: 'holiday', label: 'Holiday' },
    { value: 'week_off', label: 'Week Off' },
];

const sourceOptions = [
    { value: 'manual', label: 'Manual' },
    { value: 'web', label: 'Web' },
    { value: 'mobile', label: 'Mobile' },
    { value: 'biometric', label: 'Biometric' },
    { value: 'import', label: 'Import' },
];

const workedHoursPreview = computed(() => {
    if (!form.check_in || !form.check_out) return '0.0';
    const [h1, m1] = form.check_in.split(':').map(Number);
    const [h2, m2] = form.check_out.split(':').map(Number);
    if (isNaN(h1) || isNaN(m1) || isNaN(h2) || isNaN(m2)) return '0.0';
    let minutes = (h2 * 60 + m2) - (h1 * 60 + m1);
    if (minutes < 0) minutes += 24 * 60;
    return (minutes / 60).toFixed(1);
});

const submit = () => {
    form.put(route('admin.hrm.attendances.update', record.value.public_id));
};
</script>

<template>
    <Head title="Edit Attendance" />

    <OrganizationLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <HRMPageHeader
                :title="`Edit Attendance - ${record.staff?.name || 'Staff'}`"
                subtitle="Modify punch times, status, or attendance notes."
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Attendance', href: route('admin.hrm.attendances.index') },
                    { label: record.attendance_date },
                    { label: 'Edit' },
                ]"
            />

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5">
                    <div class="flex items-center justify-between border-b border-zinc-200/80 pb-3 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                {{ (record.staff?.name || 'S').charAt(0) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ record.staff?.name }}
                                </h3>
                                <p class="text-xs text-slate-400 font-mono">
                                    #{{ record.staff?.employee_code }}
                                </p>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-600 dark:text-zinc-400">Active</span>
                            <Switch v-model="form.is_active" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Date Picker -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Attendance Date <span class="text-rose-500">*</span>
                            </label>
                            <DatePicker
                                v-model="form.attendance_date"
                                placeholder="Select date"
                            />
                            <p v-if="form.errors.attendance_date" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.attendance_date }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Attendance Status <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                v-model="form.status"
                                :options="statusOptions"
                                placeholder="Select status"
                            />
                            <p v-if="form.errors.status" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Check In -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Check In Time (HH:mm)
                            </label>
                            <TextInput
                                v-model="form.check_in"
                                type="time"
                                class="w-full"
                            />
                            <p v-if="form.errors.check_in" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.check_in }}
                            </p>
                        </div>

                        <!-- Check Out -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Check Out Time (HH:mm)
                            </label>
                            <TextInput
                                v-model="form.check_out"
                                type="time"
                                class="w-full"
                            />
                            <p v-if="form.errors.check_out" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.check_out }}
                            </p>
                        </div>

                        <!-- Worked Duration Display -->
                        <div class="sm:col-span-2 rounded-lg bg-indigo-50/60 p-3 dark:bg-indigo-950/30 flex items-center justify-between border border-indigo-100 dark:border-indigo-900/40">
                            <span class="text-xs text-indigo-900 dark:text-indigo-200">
                                Recalculated Duration:
                            </span>
                            <span class="text-sm font-bold text-indigo-700 dark:text-indigo-300">
                                {{ workedHoursPreview }} Hours
                            </span>
                        </div>

                        <!-- Late Minutes -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Late Minutes
                            </label>
                            <TextInput
                                v-model.number="form.late_minutes"
                                type="number"
                                min="0"
                                class="w-full"
                            />
                        </div>

                        <!-- Early Leave Minutes -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Early Leave Minutes
                            </label>
                            <TextInput
                                v-model.number="form.early_leave_minutes"
                                type="number"
                                min="0"
                                class="w-full"
                            />
                        </div>

                        <!-- Overtime Minutes -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Overtime Minutes
                            </label>
                            <TextInput
                                v-model.number="form.overtime_minutes"
                                type="number"
                                min="0"
                                class="w-full"
                            />
                        </div>

                        <!-- Source -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Punch Source
                            </label>
                            <Select
                                v-model="form.source"
                                :options="sourceOptions"
                                placeholder="Select source"
                            />
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Notes / Remarks
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                placeholder="Optional attendance notes..."
                                class="w-full rounded-lg border border-zinc-300 p-2.5 text-xs text-slate-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.hrm.attendances.index')"
                        class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Saving...' : 'Update Attendance' }}
                    </button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
