<script setup lang="ts">
withDefaults(
    defineProps<{
        striped?: boolean;
        hoverable?: boolean;
    }>(),
    {
        striped: false,
        hoverable: true,
    }
);
</script>

<template>
    <div class="w-full overflow-x-auto">
        <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-400">
                <slot name="head" />
            </thead>
            <tbody
                :class="[
                    'divide-y divide-zinc-200 dark:divide-zinc-800',
                    striped ? 'divide-y-0 [&>tr:nth-child(even)]:bg-zinc-50/50 dark:[&>tr:nth-child(even)]:bg-zinc-800/30' : '',
                    hoverable ? '[&>tr]:transition-colors [&>tr:hover]:bg-zinc-50/80 dark:[&>tr:hover]:bg-zinc-800/50' : '',
                ]"
            >
                <slot />
            </tbody>
            <tfoot v-if="$slots.foot" class="border-t border-zinc-200 bg-zinc-50 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-400">
                <slot name="foot" />
            </tfoot>
        </table>
    </div>
</template>
