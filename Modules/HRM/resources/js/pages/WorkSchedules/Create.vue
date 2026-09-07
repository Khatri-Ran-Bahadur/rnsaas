<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import Switch from '@/components/Switch.vue';

const props = defineProps<{
    timezones?: string[];
}>();

const timezoneOptions = computed(() => {
    const list = props.timezones && props.timezones.length > 0
        ? props.timezones
        : ['UTC', 'Asia/Kathmandu', 'Asia/Kolkata', 'America/New_York', 'Europe/London'];
    return list.map((tz) => ({ label: tz, value: tz }));
});

const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const form = useForm({
    name: '',
    description: '',
    timezone: 'UTC',
    default_start_time: '09:00',
    default_end_time: '17:00',
    break_minutes: 60,
    late_grace_minutes: 15,
    early_leave_grace_minutes: 15,
    days: Array.from({ length: 7 }, (_, i) => ({
        day_of_week: i + 1,
        day_name: dayNames[i],
        is_working_day: i < 5, // Mon-Fri working, Sat-Sun off by default
        start_time: '09:00',
        end_time: '17:00',
        break_minutes: 60,
    })),
});

const submit = () => {
    form.post('/admin/hrm/work-schedules', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Create Work Schedule"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Work Schedules', href: '/admin/hrm/work-schedules' },
            { label: 'Create' },
        ]"
    >
        <Head title="Create Work Schedule - HRM" />

        <div class="w-full space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Create Work Schedule
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Define daily operating hours and weekend configurations for staff.
                    </p>
                </div>
                <Link href="/admin/hrm/work-schedules">
                    <Button variant="secondary" size="sm">Cancel</Button>
                </Link>
            </div>

            <form class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-6" @submit.prevent="submit">
                <!-- Schedule Basic Info -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Schedule Name <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <TextInput v-model="form.name" placeholder="e.g. Standard Office Hours (9-5)" :error="form.errors.name" />
                    </div>

                    <div>
                        <Select
                            v-model="form.timezone"
                            label="Timezone"
                            :options="timezoneOptions"
                            placeholder="Select timezone..."
                            :error="form.errors.timezone"
                            searchable
                            required
                        />
                    </div>
                </div>

                <!-- Operating Hours & Break Defaults -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Start Time</label>
                        <input v-model="form.default_start_time" type="time" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">End Time</label>
                        <input v-model="form.default_end_time" type="time" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Break (Mins)</label>
                        <input v-model.number="form.break_minutes" type="number" min="0" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Grace (Mins)</label>
                        <input v-model.number="form.late_grace_minutes" type="number" min="0" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    </div>
                </div>

                <!-- Weekly Working Days Schedule -->
                <div class="border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-500 mb-3">Weekly Schedule (7 Days)</h3>
                    <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                        <div
                            v-for="day in form.days"
                            :key="day.day_of_week"
                            class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-center gap-3 sm:w-40">
                                <Switch v-model="day.is_working_day" />
                                <span class="text-xs font-semibold" :class="day.is_working_day ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 line-through'">
                                    {{ day.day_name }}
                                </span>
                            </div>

                            <div v-if="day.is_working_day" class="flex flex-wrap items-center gap-3 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-zinc-400 text-[11px]">From:</span>
                                    <input v-model="day.start_time" type="time" class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-zinc-400 text-[11px]">To:</span>
                                    <input v-model="day.end_time" type="time" class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-zinc-400 text-[11px]">Break:</span>
                                    <input v-model.number="day.break_minutes" type="number" min="0" class="w-16 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                                    <span class="text-[11px] text-zinc-400">m</span>
                                </div>
                            </div>
                            <div v-else class="text-xs text-zinc-400 italic">
                                Non-working day (Weekend / Off)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Link href="/admin/hrm/work-schedules">
                        <Button variant="secondary" type="button">Cancel</Button>
                    </Link>
                    <Button variant="primary" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Schedule' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
