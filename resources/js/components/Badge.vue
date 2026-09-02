<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'active' | 'pending' | 'suspended' | 'cancelled' | 'invited' | 'revoked' | 'default' | 'neutral';
        size?: 'sm' | 'md';
        label?: string;
    }>(),
    {
        variant: 'default',
        size: 'sm',
        label: undefined,
    },
);

const styles = computed(() => {
    switch (props.variant) {
        case 'active':
            return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30';
        case 'pending':
        case 'invited':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/30';
        case 'suspended':
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20 dark:bg-orange-950/40 dark:text-orange-300 dark:ring-orange-500/30';
        case 'cancelled':
        case 'revoked':
            return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/30';
        case 'neutral':
        case 'default':
        default:
            return 'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-500/10 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700';
    }
});

const dotColor = computed(() => {
    switch (props.variant) {
        case 'active':
            return 'bg-emerald-500';
        case 'pending':
        case 'invited':
            return 'bg-amber-500';
        case 'suspended':
            return 'bg-orange-500';
        case 'cancelled':
        case 'revoked':
            return 'bg-rose-500';
        case 'neutral':
        case 'default':
        default:
            return 'bg-zinc-400';
    }
});

const formattedLabel = computed(() => {
    if (props.label) return props.label;
    if (props.variant) {
        return props.variant.charAt(0).toUpperCase() + props.variant.slice(1);
    }
    return '';
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 font-medium rounded-full',
            size === 'sm' ? 'px-2.5 py-0.5 text-xs' : 'px-3 py-1 text-sm',
            styles,
        ]"
    >
        <span :class="['h-1.5 w-1.5 rounded-full shrink-0', dotColor]" />
        <slot>{{ formattedLabel }}</slot>
    </span>
</template>
