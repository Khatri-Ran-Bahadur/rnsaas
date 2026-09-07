<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import Switch from '@/components/Switch.vue';

const props = defineProps<{
    types?: Array<{ value: string; label: string }>;
}>();

const categoryOptions = computed(() =>
    (props.types || []).map((t) => ({ label: t.label, value: t.value }))
);

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    type: 'public',
    description: '',
    is_recurring: true,
});

const submit = () => {
    form.post('/admin/hrm/holidays', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Add Holiday"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Holidays', href: '/admin/hrm/holidays' },
            { label: 'Create' },
        ]"
    >
        <Head title="Create Holiday - HRM" />

        <div class="px-4 py-6 sm:px-8 max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Create Organization Holiday
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Add a single-day or multi-day holiday to the organization calendar.
                    </p>
                </div>
                <Link href="/admin/hrm/holidays">
                    <Button variant="secondary" size="sm">Cancel</Button>
                </Link>
            </div>

            <form class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Holiday Name <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <TextInput v-model="form.name" placeholder="e.g. New Year's Day, Dashain Festival" :error="form.errors.name" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Holiday Category <span class="text-rose-500 font-bold">*</span>
                        </label>
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
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Start Date <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <DatePicker
                            v-model="form.start_date"
                            placeholder="Select start date"
                            :error="form.errors.start_date"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            End Date (Optional for multi-day)
                        </label>
                        <DatePicker
                            v-model="form.end_date"
                            placeholder="Select end date"
                            :error="form.errors.end_date"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Description (Optional)</label>
                    <textarea v-model="form.description" rows="3" placeholder="Context or greetings for this holiday..." class="w-full rounded-lg border border-zinc-300 px-3.5 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"></textarea>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <div>
                        <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Yearly Recurring Holiday</p>
                        <p class="text-[11px] text-zinc-500">Automatically repeat this holiday every year on the same date.</p>
                    </div>
                    <Switch v-model="form.is_recurring" />
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Link href="/admin/hrm/holidays">
                        <Button variant="secondary" type="button">Cancel</Button>
                    </Link>
                    <Button variant="primary" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Holiday' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
