<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import Switch from '@/components/Switch.vue';
import type { HolidayItem, HolidayCategoryOption } from '../../types/holidays';

const props = defineProps<{
    holiday: { data: HolidayItem };
    types?: Array<{ value: string; label: string }>;
}>();

const h = props.holiday.data;

const categoryOptions = computed(() =>
    (props.types || []).map((t) => ({ label: t.label, value: t.value }))
);

const form = useForm({
    name: h.name,
    start_date: h.start_date,
    end_date: h.end_date || '',
    type: h.type,
    description: h.description || '',
    is_recurring: h.is_recurring,
    is_active: h.is_active,
});

const submit = () => {
    form.put(`/admin/hrm/holidays/${h.public_id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Edit Holiday"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Holidays', href: '/admin/hrm/holidays' },
            { label: h.name, href: `/admin/hrm/holidays/${h.public_id}` },
            { label: 'Edit' },
        ]"
    >
        <Head :title="`Edit ${h.name} - HRM`" />

        <div class="px-4 py-6 sm:px-8 max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Edit Holiday
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Update holiday dates, recurrence, and categorization.
                    </p>
                </div>
                <Link :href="`/admin/hrm/holidays/${h.public_id}`">
                    <Button variant="secondary" size="sm">Back</Button>
                </Link>
            </div>

            <form class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Holiday Name <span class="text-rose-500">*</span></label>
                        <TextInput v-model="form.name" :error="form.errors.name" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Category <span class="text-rose-500">*</span></label>
                        <Select
                            v-model="form.type"
                            :options="categoryOptions"
                            placeholder="Select category"
                            :error="form.errors.type"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Start Date <span class="text-rose-500">*</span></label>
                        <DatePicker
                            v-model="form.start_date"
                            placeholder="Select start date"
                            :error="form.errors.start_date"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">End Date</label>
                        <DatePicker
                            v-model="form.end_date"
                            placeholder="Select end date"
                            :error="form.errors.end_date"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Description</label>
                    <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-zinc-300 px-3.5 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"></textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Yearly Recurring</p>
                            <p class="text-[11px] text-zinc-500">Repeats annually on this date.</p>
                        </div>
                        <Switch v-model="form.is_recurring" />
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Active Status</p>
                            <p class="text-[11px] text-zinc-500">Active on the calendar.</p>
                        </div>
                        <Switch v-model="form.is_active" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Link :href="`/admin/hrm/holidays/${h.public_id}`">
                        <Button variant="secondary" type="button">Cancel</Button>
                    </Link>
                    <Button variant="primary" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Update Holiday' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
