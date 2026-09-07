<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Switch from '@/components/Switch.vue';

const form = useForm({
    name: '',
    code: '',
    description: '',
    start_time: '09:00',
    end_time: '18:00',
    break_minutes: 60,
    late_grace_minutes: 15,
    early_leave_grace_minutes: 15,
    is_overnight: false,
});

const submit = () => {
    form.post('/admin/hrm/shifts', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Create Shift"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Shifts', href: '/admin/hrm/shifts' },
            { label: 'Create' },
        ]"
    >
        <Head title="Create Shift - HRM" />

        <div class="px-4 py-6 sm:px-8 max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Create Work Shift
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Define shift times, break intervals, and overnight configurations.
                    </p>
                </div>
                <Link href="/admin/hrm/shifts">
                    <Button variant="secondary" size="sm">Cancel</Button>
                </Link>
            </div>

            <form class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Shift Name <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <TextInput v-model="form.name" placeholder="e.g. Morning Shift, General Shift" :error="form.errors.name" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Shift Code <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <TextInput v-model="form.code" placeholder="e.g. MORN, GEN, NIGHT" :error="form.errors.code" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Description (Optional)
                    </label>
                    <TextInput v-model="form.description" placeholder="Brief description of who this shift applies to..." :error="form.errors.description" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Start Time <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input v-model="form.start_time" type="time" class="w-full rounded-lg border border-zinc-300 px-3.5 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        <p v-if="form.errors.start_time" class="text-xs text-rose-500 mt-1">{{ form.errors.start_time }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            End Time <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input v-model="form.end_time" type="time" class="w-full rounded-lg border border-zinc-300 px-3.5 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        <p v-if="form.errors.end_time" class="text-xs text-rose-500 mt-1">{{ form.errors.end_time }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Break Minutes</label>
                        <input v-model.number="form.break_minutes" type="number" min="0" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Late Grace (Mins)</label>
                        <input v-model.number="form.late_grace_minutes" type="number" min="0" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Early Leave Grace</label>
                        <input v-model.number="form.early_leave_grace_minutes" type="number" min="0" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <div>
                        <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Overnight Shift</p>
                        <p class="text-[11px] text-zinc-500">Enable if the shift crosses midnight (ends on the next calendar day).</p>
                    </div>
                    <Switch v-model="form.is_overnight" />
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Link href="/admin/hrm/shifts">
                        <Button variant="secondary" type="button">Cancel</Button>
                    </Link>
                    <Button variant="primary" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Shift' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
