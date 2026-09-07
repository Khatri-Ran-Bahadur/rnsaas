<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import Switch from '@/components/Switch.vue';
import type { OvertimeItem } from '../../types/overtimes';

const props = defineProps<{
    overtime: { data: OvertimeItem } | OvertimeItem;
    staff_members: Array<{ id: number; public_id: string; name: string; employee_code: string }>;
    types: Array<{ value: string; label: string }>;
}>();

const item = computed<OvertimeItem>(() => ('data' in props.overtime ? props.overtime.data : props.overtime));

const staffOptions = computed(() =>
    (props.staff_members || []).map((s) => ({
        label: `${s.name} (${s.employee_code})`,
        value: s.id,
    }))
);

const typeOptions = computed(() =>
    (props.types || []).map((t) => ({
        label: t.label,
        value: t.value,
    }))
);

// Find matching staff id from staff_members or overtime.tenant_staff_id or item.staff?.id
const staffId = computed(() => {
    if (item.value.tenant_staff_id) return item.value.tenant_staff_id;
    if (item.value.staff?.id) return item.value.staff.id;
    const match = props.staff_members.find(
        (s) => s.public_id === item.value.staff?.public_id || s.employee_code === item.value.staff?.employee_code
    );
    return match ? match.id : (props.staff_members[0]?.id ?? '');
});

const form = useForm({
    tenant_staff_id: staffId.value,
    date: item.value.date,
    start_time: item.value.start_time,
    end_time: item.value.end_time,
    type: item.value.type,
    rate_multiplier: Number(item.value.rate_multiplier),
    reason: item.value.reason ?? '',
    is_active: Boolean(item.value.is_active),
});

// Live calculated duration
const calculatedDuration = computed(() => {
    if (!form.start_time || !form.end_time) return null;
    const [startH, startM] = form.start_time.split(':').map(Number);
    const [endH, endM] = form.end_time.split(':').map(Number);
    if (isNaN(startH) || isNaN(startM) || isNaN(endH) || isNaN(endM)) return null;

    let startMinutes = startH * 60 + startM;
    let endMinutes = endH * 60 + endM;

    if (endMinutes <= startMinutes) {
        endMinutes += 24 * 60; // Overnight
    }

    const diff = endMinutes - startMinutes;
    const hours = (diff / 60).toFixed(1);
    return {
        minutes: diff,
        hours,
    };
});

const submit = () => {
    form.put(`/admin/hrm/overtimes/${item.value.public_id}`);
};
</script>

<template>
    <OrganizationLayout
        title="Edit Overtime"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Overtime', href: '/admin/hrm/overtimes' },
            { label: item.staff?.name ?? 'Record', href: `/admin/hrm/overtimes/${item.public_id}` },
            { label: 'Edit' },
        ]"
    >
        <Head title="Edit Overtime - HRM" />

        <div class="w-full space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Edit Overtime Record
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Modify logged hours, shift type, or status for this employee.
                    </p>
                </div>
                <Link :href="`/admin/hrm/overtimes/${item.public_id}`">
                    <Button variant="secondary" size="sm">Cancel</Button>
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Main Form Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <!-- Employee selection -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Employee <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                v-model="form.tenant_staff_id"
                                :options="staffOptions"
                                placeholder="Select an employee"
                                searchable
                                :error="form.errors.tenant_staff_id"
                            />
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Overtime Date <span class="text-rose-500">*</span>
                            </label>
                            <DatePicker
                                v-model="form.date"
                                placeholder="Select overtime date"
                                :error="form.errors.date"
                            />
                        </div>

                        <!-- Overtime Type -->
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Overtime Category <span class="text-rose-500">*</span>
                            </label>
                            <Select
                                v-model="form.type"
                                :options="typeOptions"
                                placeholder="Select overtime category"
                                :error="form.errors.type"
                            />
                        </div>

                        <!-- Start Time -->
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Start Time (24h) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.start_time"
                                type="time"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-mono text-zinc-800 focus:border-zinc-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                                required
                            />
                            <p v-if="form.errors.start_time" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.start_time }}
                            </p>
                        </div>

                        <!-- End Time -->
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                End Time (24h) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.end_time"
                                type="time"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-mono text-zinc-800 focus:border-zinc-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                                required
                            />
                            <p v-if="form.errors.end_time" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.end_time }}
                            </p>
                        </div>

                        <!-- Duration Live preview banner -->
                        <div v-if="calculatedDuration" class="sm:col-span-2 rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-800/60 flex items-center justify-between border border-zinc-200 dark:border-zinc-700/60">
                            <span class="text-zinc-600 dark:text-zinc-400">Calculated Overtime Duration:</span>
                            <span class="font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                {{ calculatedDuration.hours }} Hours ({{ calculatedDuration.minutes }} Minutes)
                            </span>
                        </div>

                        <!-- Rate Multiplier -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Rate Multiplier <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model.number="form.rate_multiplier"
                                    type="number"
                                    step="0.1"
                                    min="0.1"
                                    max="10"
                                    class="w-32 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-mono text-zinc-800 focus:border-zinc-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                                    required
                                />
                                <span class="text-xs text-zinc-500">e.g. 1.5x (time and a half), 2.0x (double time)</span>
                            </div>
                            <p v-if="form.errors.rate_multiplier" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.rate_multiplier }}
                            </p>
                        </div>

                        <!-- Reason -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                Reason / Justification
                            </label>
                            <textarea
                                v-model="form.reason"
                                rows="3"
                                placeholder="Explain why overtime was worked..."
                                class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-xs text-zinc-800 focus:border-zinc-500 focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                            ></textarea>
                            <p v-if="form.errors.reason" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.reason }}
                            </p>
                        </div>

                        <!-- Active Toggle -->
                        <div class="sm:col-span-2 flex items-center justify-between border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <div>
                                <h4 class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">Active Record</h4>
                                <p class="text-[11px] text-zinc-500">Inactive records are excluded from payroll reports.</p>
                            </div>
                            <Switch v-model="form.is_active" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="`/admin/hrm/overtimes/${item.public_id}`">
                        <Button variant="secondary" size="md">Cancel</Button>
                    </Link>
                    <Button variant="primary" size="md" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Updating...' : 'Update Overtime' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
