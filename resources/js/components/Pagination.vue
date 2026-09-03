<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types/tenancy';

defineProps<{
    links: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number;
}>();

const cleanLabel = (label: string) => {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', 'Previous')
        .replace('Next', 'Next');
};
</script>

<template>
    <div
        v-if="links.length > 3"
        class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4 px-2 text-sm text-zinc-600 dark:text-zinc-400"
    >
        <div v-if="total !== undefined && from !== undefined && to !== undefined" class="text-xs text-zinc-500 dark:text-zinc-400">
            Showing <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ from ?? 0 }}</span> to
            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ to ?? 0 }}</span> of
            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ total }}</span> results
        </div>

        <nav class="inline-flex items-center -space-x-px rounded-lg border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <template v-for="(link, index) in links" :key="index">
                <span
                    v-if="!link.url"
                    class="relative inline-flex items-center px-3 py-1.5 text-xs font-medium text-zinc-400 dark:text-zinc-600 cursor-not-allowed select-none first:rounded-l-lg last:rounded-r-lg"
                    v-html="cleanLabel(link.label)"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :class="[
                        'relative inline-flex items-center px-3 py-1.5 text-xs font-medium transition-colors first:rounded-l-lg last:rounded-r-lg',
                        link.active
                            ? 'z-10 bg-primary-600 text-white font-semibold shadow-xs shadow-primary-500/25'
                            : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                    ]"
                    v-html="cleanLabel(link.label)"
                />
            </template>
        </nav>
    </div>
</template>
