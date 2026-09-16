<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';

interface Template {
    id: number;
    name: string;
    slug: string;
    type: string;
    subject: string;
    content: string;
    variables: string[];
    is_active: boolean;
    updated_at: string;
}

const props = defineProps<{
    templates: Template[];
}>();

const toggleActive = (template: Template) => {
    router.post(`/superadmin/notification-templates/${template.id}/toggle`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <SuperAdminLayout>
        <Head title="Notification & Email Templates - SuperAdmin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>SuperAdmin</span>
                        <span>/</span>
                        <span class="text-zinc-800 font-medium dark:text-zinc-200">Notification Templates</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Notification & Email Templates
                    </h1>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        Manage system-wide transactional emails, notifications, and dynamic merge tags.
                    </p>
                </div>
            </div>

            <!-- Templates Card Grid / Table -->
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">System Templates</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Total of {{ templates.length }} notification templates configured.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-3 px-6">Template Name</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4">Subject Line</th>
                                <th class="py-3 px-4">Variables</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 text-zinc-700 dark:divide-zinc-800 dark:text-zinc-300">
                            <tr
                                v-for="t in templates"
                                :key="t.id"
                                class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors"
                            >
                                <td class="py-4 px-6">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ t.name }}</div>
                                    <div class="text-xs font-mono text-zinc-400">{{ t.slug }}</div>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ t.type.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 max-w-xs truncate text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ t.subject }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        <span
                                            v-for="v in (t.variables || []).slice(0, 3)"
                                            :key="v"
                                            class="rounded-md bg-primary-50 px-1.5 py-0.5 text-[11px] font-mono text-primary-700 dark:bg-primary-950/40 dark:text-primary-400"
                                        >
                                            {{ '{' + v + '}' }}
                                        </span>
                                        <span v-if="(t.variables || []).length > 3" class="text-[11px] text-zinc-400 self-center">
                                            +{{ (t.variables || []).length - 3 }} more
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <button
                                        type="button"
                                        @click="toggleActive(t)"
                                        :class="[
                                            t.is_active
                                                ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400'
                                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400',
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold cursor-pointer transition-colors'
                                        ]"
                                    >
                                        {{ t.is_active ? 'Active' : 'Disabled' }}
                                    </button>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <Link
                                        :href="`/superadmin/notification-templates/${t.id}/edit`"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline"
                                    >
                                        <span>Edit Template</span>
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
