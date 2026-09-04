<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types/tenancy';

const props = defineProps<{
    links?: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number | null;
    data?: {
        links?: PaginationLink[];
        from?: number | null;
        to?: number | null;
        total?: number | null;
    };
}>();

const effectiveLinks = computed(() => props.data?.links ?? props.links ?? []);
const effectiveFrom = computed(() => props.data?.from ?? props.from ?? null);
const effectiveTo = computed(() => props.data?.to ?? props.to ?? null);
const effectiveTotal = computed(() => props.data?.total ?? props.total ?? null);

const isPrevious = (label: string) => label.includes('Previous') || label.includes('&laquo;');
const isNext = (label: string) => label.includes('Next') || label.includes('&raquo;');

const cleanLabel = (label: string) => {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', '')
        .replace('Next', '')
        .trim();
};
</script>

<template>
    <div
        v-if="effectiveLinks && effectiveLinks.length > 0"
        class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400"
    >
        <!-- Result summary (like accounting) -->
        <div class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
            <template v-if="effectiveTotal !== undefined && effectiveTotal !== null">
                Showing
                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ effectiveFrom ?? 0 }}</span>
                to
                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ effectiveTo ?? 0 }}</span>
                of
                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ effectiveTotal ?? 0 }}</span>
                results
            </template>
        </div>

        <!-- Navigation Buttons (like accounting) -->
        <div class="flex items-center gap-1 sm:space-x-1 flex-wrap justify-center">
            <template v-for="(link, index) in effectiveLinks" :key="index">
                <!-- Previous Button -->
                <template v-if="isPrevious(link.label)">
                    <span
                        v-if="!link.url"
                        class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200/80 bg-zinc-50/50 text-xs text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-600 cursor-not-allowed select-none"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="hidden sm:inline">Previous</span>
                    </span>
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                        class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="hidden sm:inline">Previous</span>
                    </Link>
                </template>

                <!-- Next Button -->
                <template v-else-if="isNext(link.label)">
                    <span
                        v-if="!link.url"
                        class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200/80 bg-zinc-50/50 text-xs text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-600 cursor-not-allowed select-none"
                    >
                        <span class="hidden sm:inline">Next</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                        class="inline-flex items-center gap-1 h-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                    >
                        <span class="hidden sm:inline">Next</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </template>

                <!-- Page Number Buttons -->
                <template v-else>
                    <span
                        v-if="!link.url && !link.active"
                        class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-xs text-zinc-400 dark:text-zinc-600 select-none"
                    >
                        ...
                    </span>
                    <span
                        v-else-if="link.active"
                        class="inline-flex items-center justify-center h-8 min-w-8 px-2.5 rounded-md bg-primary-600 font-semibold text-xs text-white shadow-xs shadow-primary-500/25 select-none"
                    >
                        {{ cleanLabel(link.label) }}
                    </span>
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                        class="inline-flex items-center justify-center h-8 min-w-8 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                    >
                        {{ cleanLabel(link.label) }}
                    </Link>
                </template>
            </template>
        </div>
    </div>
</template>
