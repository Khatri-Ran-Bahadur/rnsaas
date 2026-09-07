<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        status: string;
        label?: string;
        size?: 'sm' | 'md';
    }>(),
    {
        label: '',
        size: 'sm',
    },
);

const displayLabel = computed(() => {
    if (props.label) {
        return props.label;
    }
    return props.status.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
});

const badgeStyles = computed(() => {
    const s = props.status.toLowerCase();
    switch (s) {
        // Attendance statuses
        case 'present':
        case 'approved':
        case 'active':
        case 'completed':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/60';
        case 'absent':
        case 'rejected':
        case 'terminated':
        case 'failed':
        case 'critical':
            return 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800/60';
        case 'late':
        case 'pending':
        case 'high':
            return 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800/60';
        case 'half_day':
        case 'medium':
        case 'processing':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800/60';
        case 'on_leave':
        case 'holiday':
            return 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800/60';
        case 'week_off':
        case 'inactive':
        case 'low':
        case 'ignored':
        default:
            return 'bg-zinc-100 text-zinc-700 border-zinc-200/80 dark:bg-zinc-800/80 dark:text-zinc-300 dark:border-zinc-700/60';
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border font-medium transition-colors',
            size === 'md' ? 'px-2.5 py-1 text-xs' : 'px-2 py-0.5 text-[11px]',
            badgeStyles,
        ]"
    >
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-75" />
        <span>{{ displayLabel }}</span>
    </span>
</template>
