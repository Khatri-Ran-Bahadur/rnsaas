<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Button from '@/components/Button.vue';
import Switch from '@/components/Switch.vue';
import TextInput from '@/components/TextInput.vue';

interface Template {
    id: number;
    name: string;
    slug: string;
    type: string;
    subject: string;
    content: string;
    variables: string[];
    is_active: boolean;
}

const props = defineProps<{
    template: Template;
}>();

const form = useForm({
    subject: props.template.subject || '',
    content: props.template.content || '',
    is_active: props.template.is_active,
});

const insertVariable = (variableName: string) => {
    const tag = `{${variableName}}`;
    form.content += ` ${tag}`;
};

const submit = () => {
    form.put(`/superadmin/notification-templates/${props.template.id}`);
};
</script>

<template>
    <SuperAdminLayout>
        <Head :title="`Edit ${template.name} - Notification Templates`" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Breadcrumbs & Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <Link href="/superadmin/notification-templates" class="hover:underline">Templates</Link>
                        <span>/</span>
                        <span class="text-zinc-800 font-medium dark:text-zinc-200">{{ template.name }}</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Edit Notification Template
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/superadmin/notification-templates"
                        class="rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 shadow-2xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Back
                    </Link>
                    <Button :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Saving...' : 'Save Template' }}
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Main Editor Area (8 cols) -->
                <div class="space-y-6 lg:col-span-8">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5">
                        <TextInput
                            v-model="form.subject"
                            label="Email Subject Line"
                            placeholder="e.g. Invoice #{invoice_number} from {company_name}"
                            hint="You can insert variable tags like {user_name} or {company_name} in the subject."
                            :error="form.errors.subject"
                        />

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                    Message Body (HTML Supported)
                                </label>
                            </div>
                            <textarea
                                v-model="form.content"
                                rows="12"
                                class="flex w-full rounded-lg border border-zinc-200 bg-white p-3.5 font-mono text-xs text-zinc-900 shadow-2xs transition-all hover:border-zinc-300 focus:border-primary-500 focus:outline-none dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="Write email body HTML..."
                            />
                            <p v-if="form.errors.content" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.content }}
                            </p>
                        </div>
                    </div>

                    <!-- Live Content Preview Card -->
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Live HTML Preview</h3>
                        <div
                            class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-5 prose prose-sm max-w-none dark:border-zinc-800 dark:bg-zinc-950 dark:prose-invert"
                            v-html="form.content"
                        />
                    </div>
                </div>

                <!-- Right Sidebar: Variables & Status (4 cols) -->
                <div class="space-y-6 lg:col-span-4">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Available Merge Tags
                        </h3>
                        <p class="text-xs text-zinc-500">
                            Click any variable placeholder to append it into the message content.
                        </p>

                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <button
                                v-for="v in template.variables || []"
                                :key="v"
                                type="button"
                                @click="insertVariable(v)"
                                class="inline-flex items-center rounded-lg border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-mono font-medium text-primary-700 hover:bg-primary-100 dark:border-primary-800/60 dark:bg-primary-950/50 dark:text-primary-300 cursor-pointer transition-colors"
                                :title="`Click to insert {${v}}`"
                            >
                                {{ '{' + v + '}' }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-4">
                            Settings & Status
                        </h3>
                        <Switch
                            v-model="form.is_active"
                            label="Template Active"
                            description="When disabled, system notifications for this event will not be dispatched."
                        />
                    </div>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
