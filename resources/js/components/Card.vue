<script setup lang="ts">
withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        noPadding?: boolean;
        hover?: boolean;
    }>(),
    {
        title: undefined,
        description: undefined,
        noPadding: false,
        hover: false,
    }
);
</script>

<template>
    <div
        :class="[
            'rounded-2xl border border-zinc-200/80 bg-white shadow-[0_1px_3px_0_rgba(0,0,0,0.04),0_1px_2px_-1px_rgba(0,0,0,0.04)] transition-all duration-200 dark:border-zinc-800/80 dark:bg-zinc-900 dark:shadow-none',
            hover ? 'hover:shadow-[0_4px_14px_0_rgba(0,0,0,0.06),0_2px_4px_-1px_rgba(0,0,0,0.06)] hover:border-zinc-300/90 dark:hover:border-zinc-700/90' : '',
        ]"
    >
        <!-- Header -->
        <div
            v-if="title || description || $slots.header || $slots.actions"
            class="flex items-center justify-between border-b border-zinc-100 p-5 sm:p-6 dark:border-zinc-800/80"
        >
            <div class="space-y-0.5">
                <slot name="header">
                    <h3 v-if="title" class="text-base font-semibold text-zinc-900 dark:text-white">
                        <slot name="title">{{ title }}</slot>
                    </h3>
                    <p v-if="description" class="text-xs text-zinc-500 dark:text-zinc-400">
                        <slot name="description">{{ description }}</slot>
                    </p>
                </slot>
            </div>

            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <!-- Body -->
        <div :class="noPadding ? '' : 'p-5 sm:p-6'">
            <slot />
        </div>

        <!-- Footer -->
        <div
            v-if="$slots.footer"
            class="border-t border-zinc-100 bg-zinc-50/50 p-4 sm:px-6 rounded-b-2xl dark:border-zinc-800/80 dark:bg-zinc-950/40"
        >
            <slot name="footer" />
        </div>
    </div>
</template>
