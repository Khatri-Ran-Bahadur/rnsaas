<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Breadcrumb {
    label: string;
    href?: string;
}

withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        breadcrumbs?: Breadcrumb[];
    }>(),
    {
        subtitle: '',
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <!-- Breadcrumbs -->
            <nav v-if="breadcrumbs.length > 0" class="mb-1.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-zinc-400">
                <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
                    <Link
                        v-if="crumb.href"
                        :href="crumb.href"
                        class="transition-colors hover:text-indigo-600 dark:hover:text-indigo-400"
                    >
                        {{ crumb.label }}
                    </Link>
                    <span v-else class="text-slate-800 font-medium dark:text-zinc-200">
                        {{ crumb.label }}
                    </span>
                    <span v-if="idx < breadcrumbs.length - 1" class="text-slate-400 dark:text-zinc-600">/</span>
                </template>
            </nav>

            <!-- Title & Subtitle -->
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                {{ title }}
            </h1>
            <p v-if="subtitle" class="mt-1 text-xs text-slate-500 sm:text-sm dark:text-zinc-400">
                {{ subtitle }}
            </p>
        </div>

        <!-- Action Buttons Slot -->
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2.5">
            <slot name="actions" />
        </div>
    </div>
</template>
