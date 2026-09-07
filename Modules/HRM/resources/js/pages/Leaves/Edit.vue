<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import HRMPageHeader from '../../components/common/HRMPageHeader.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import Switch from '@/components/Switch.vue';
import type { LeaveItem } from '../../types/leaves';

const props = defineProps<{
    leave: {
        data: LeaveItem;
    };
}>();

const record = computed(() => props.leave.data);

const form = useForm({
    tenant_staff_id: record.value.created_by || 1,
    leave_type: record.value.leave_type?.value || 'annual',
    start_date: record.value.start_date,
    end_date: record.value.end_date,
    reason: record.value.reason || '',
    is_active: record.value.is_active,
});

const leaveTypeOptions = [
    { value: 'annual', label: 'Annual Leave' },
    { value: 'sick', label: 'Sick Leave' },
    { value: 'casual', label: 'Casual Leave' },
    { value: 'unpaid', label: 'Unpaid Leave' },
    { value: 'maternity', label: 'Maternity Leave' },
    { value: 'paternity', label: 'Paternity Leave' },
    { value: 'other', label: 'Other Leave' },
];

const totalDaysPreview = computed(() => {
    if (!form.start_date || !form.end_date) return 1;
    const start = new Date(form.start_date);
    const end = new Date(form.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24)) + 1;
    return diff > 0 ? diff : 1;
});

const submit = () => {
    form.put(route('admin.hrm.leaves.update', record.value.public_id));
};
</script>

<template>
    <Head title="Edit Leave Request" />

    <OrganizationLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <HRMPageHeader
                :title="`Edit Leave Request - ${record.staff?.name || 'Staff'}`"
                subtitle="Modify request dates, leave type, or reason."
                :breadcrumbs="[
                    { label: 'HRM' },
                    { label: 'Leave', href: route('admin.hrm.leaves.index') },
                    { label: record.public_id },
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

                        <!-- Active Switch -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-600 dark:text-zinc-400">Active</span>
                            <Switch v-model="form.is_active" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Leave Type -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Leave Type <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                v-model="form.leave_type"
                                :options="leaveTypeOptions"
                                placeholder="Select category"
                            />
                            <p v-if="form.errors.leave_type" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.leave_type }}
                            </p>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Start Date <span class="text-rose-500">*</span>
                            </label>
                            <DatePicker
                                v-model="form.start_date"
                                placeholder="Start Date"
                            />
                            <p v-if="form.errors.start_date" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.start_date }}
                            </p>
                        </div>

                        <!-- End Date -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                End Date <span class="text-rose-500">*</span>
                            </label>
                            <DatePicker
                                v-model="form.end_date"
                                placeholder="End Date"
                            />
                            <p v-if="form.errors.end_date" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.end_date }}
                            </p>
                        </div>

                        <!-- Recalculated Total Days -->
                        <div class="sm:col-span-2 rounded-lg bg-indigo-50/60 p-3.5 dark:bg-indigo-950/30 flex items-center justify-between border border-indigo-100 dark:border-indigo-900/40">
                            <div>
                                <span class="text-xs font-medium text-indigo-900 dark:text-indigo-200 block">
                                    Recalculated Duration:
                                </span>
                                <span class="text-[11px] text-indigo-700/80 dark:text-indigo-300/80">
                                    {{ form.start_date }} to {{ form.end_date }}
                                </span>
                            </div>
                            <span class="text-base font-bold text-indigo-700 dark:text-indigo-300">
                                {{ totalDaysPreview }} {{ totalDaysPreview === 1 ? 'Day' : 'Days' }}
                            </span>
                        </div>

                        <!-- Reason -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 dark:text-zinc-300 mb-1.5">
                                Reason
                            </label>
                            <textarea
                                v-model="form.reason"
                                rows="3"
                                placeholder="Reason for leave..."
                                class="w-full rounded-lg border border-zinc-300 p-2.5 text-xs text-slate-900 placeholder-zinc-400 focus:border-indigo-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            />
                            <p v-if="form.errors.reason" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.reason }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.hrm.leaves.index')"
                        class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Saving...' : 'Update Leave Request' }}
                    </button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
