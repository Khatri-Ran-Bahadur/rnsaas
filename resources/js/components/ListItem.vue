<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        href?: string;
        clickable?: boolean;
    }>(),
    {
        title: undefined,
        description: undefined,
        href: undefined,
        clickable: false,
    }
);
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        class="flex items-center justify-between p-4 transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50"
    >
        <div class="flex items-center gap-3 overflow-hidden">
            <div v-if="$slots.icon" class="shrink-0 text-zinc-500 dark:text-zinc-400">
                <slot name="icon" />
            </div>

            <div class="truncate">
                <slot>
                    <p v-if="title" class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                        {{ title }}
                    </p>
                    <p v-if="description" class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                        {{ description }}
                    </p>
                </slot>
            </div>
        </div>

        <div v-if="$slots.action" class="shrink-0 ml-4">
            <slot name="action" />
        </div>
    </Link>

    <div
        v-else
        :class="[
            'flex items-center justify-between p-4',
            clickable ? 'cursor-pointer transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50' : '',
        ]"
    >
        <div class="flex items-center gap-3 overflow-hidden">
            <div v-if="$slots.icon" class="shrink-0 text-zinc-500 dark:text-zinc-400">
                <slot name="icon" />
            </div>

            <div class="truncate">
                <slot>
                    <p v-if="title" class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                        {{ title }}
                    </p>
                    <p v-if="description" class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                        {{ description }}
                    </p>
                </slot>
            </div>
        </div>

        <div v-if="$slots.action" class="shrink-0 ml-4">
            <slot name="action" />
        </div>
    </div>
</template>
