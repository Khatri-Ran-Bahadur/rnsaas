<script setup lang="ts">
withDefaults(
    defineProps<{
        divided?: boolean;
    }>(),
    {
        divided: true,
    }
);
</script>

<template>
    <div
        :class="[
            'w-full overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900',
            divided ? 'divide-y divide-zinc-200 dark:divide-zinc-800' : '',
        ]"
    >
        <slot />
    </div>
</template>
