<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: 'active' | 'scheduled' | 'expired' | 'draft' | 'inactive' | string;
    effectiveFrom?: string | null;
    effectiveUntil?: string | null;
    size?: 'sm' | 'md';
}>();

const badgeConfig = computed(() => {
    switch (props.status) {
        case 'active':
            return {
                label: 'Active Now',
                icon: '●',
                classes: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                dotClass: 'text-emerald-500',
            };
        case 'scheduled':
            return {
                label: props.effectiveFrom ? `Scheduled (${props.effectiveFrom})` : 'Scheduled Future',
                icon: '⏳',
                classes: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                dotClass: 'text-indigo-500',
            };
        case 'expired':
            return {
                label: props.effectiveUntil ? `Expired (${props.effectiveUntil})` : 'Expired Past',
                icon: '✕',
                classes: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700',
                dotClass: 'text-zinc-400',
            };
        case 'draft':
            return {
                label: 'Draft',
                icon: '✎',
                classes: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                dotClass: 'text-amber-500',
            };
        default:
            return {
                label: props.status,
                icon: '○',
                classes: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700',
                dotClass: 'text-zinc-400',
            };
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full font-semibold border tracking-tight',
            size === 'md' ? 'px-3 py-1 text-xs' : 'px-2.5 py-0.5 text-[11px]',
            badgeConfig.classes
        ]"
        :title="effectiveFrom ? `Valid: ${effectiveFrom} ~ ${effectiveUntil || 'Present'}` : ''"
    >
        <span :class="badgeConfig.dotClass" class="text-[10px] leading-none">{{ badgeConfig.icon }}</span>
        <span>{{ badgeConfig.label }}</span>
    </span>
</template>
