<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Button from '@/components/Button.vue';

interface PageItem {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    show_in_header: boolean;
    show_in_footer: boolean;
    sort_order: number;
    updated_at: string;
}

const props = defineProps<{
    pages: PageItem[];
}>();

const deletePage = (page: PageItem) => {
    if (confirm(`Are you sure you want to delete "${page.title}"?`)) {
        router.delete(`/superadmin/pages/${page.id}`);
    }
};
</script>

<template>
    <SuperAdminLayout>
        <Head title="Custom Pages - SuperAdmin" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>SuperAdmin</span>
                        <span>/</span>
                        <span class="text-zinc-800 font-medium dark:text-zinc-200">CMS & Pages</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Custom Pages & CMS
                    </h1>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        Create, publish, and manage public content pages, legal terms, and landing pages.
                    </p>
                </div>

                <Link
                    href="/superadmin/pages/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs shadow-primary-500/25 hover:bg-primary-700 transition-all"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create New Page</span>
                </Link>
            </div>

            <!-- Pages Table -->
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">All Published & Draft Pages</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="py-3 px-6">Title</th>
                                <th class="py-3 px-4">URL Slug</th>
                                <th class="py-3 px-4 text-center">Nav Placement</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Sort Order</th>
                                <th class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 text-zinc-700 dark:divide-zinc-800 dark:text-zinc-300">
                            <tr
                                v-for="p in pages"
                                :key="p.id"
                                class="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors"
                            >
                                <td class="py-4 px-6 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ p.title }}
                                </td>
                                <td class="py-4 px-4 font-mono text-xs text-zinc-500">
                                    /page/{{ p.slug }}
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 text-xs">
                                        <span v-if="p.show_in_header" class="rounded-md bg-blue-50 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                            Header
                                        </span>
                                        <span v-if="p.show_in_footer" class="rounded-md bg-zinc-100 px-2 py-0.5 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            Footer
                                        </span>
                                        <span v-if="!p.show_in_header && !p.show_in_footer" class="text-zinc-400">
                                            Direct Only
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span
                                        :class="[
                                            p.is_published
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
                                                : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold'
                                        ]"
                                    >
                                        {{ p.is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center text-xs font-mono text-zinc-500">
                                    {{ p.sort_order }}
                                </td>
                                <td class="py-4 px-6 text-right space-x-3">
                                    <a
                                        :href="`/page/${p.slug}`"
                                        target="_blank"
                                        class="text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-white"
                                    >
                                        View
                                    </a>
                                    <Link
                                        :href="`/superadmin/pages/${p.id}/edit`"
                                        class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        @click="deletePage(p)"
                                        class="text-xs font-medium text-rose-600 hover:underline dark:text-rose-400 cursor-pointer"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
