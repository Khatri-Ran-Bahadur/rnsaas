<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

export type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'success';
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariant;
        size?: ButtonSize;
        type?: 'button' | 'submit' | 'reset';
        loading?: boolean;
        disabled?: boolean;
        block?: boolean;
        href?: string;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        loading: false,
        disabled: false,
        block: false,
        href: undefined,
    }
);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'px-2.5 py-1 text-xs gap-1.5 rounded-md';
        case 'sm':
            return 'px-3 py-1.5 text-xs font-medium gap-2 rounded-lg';
        case 'lg':
            return 'px-5 py-3 text-base font-semibold gap-2.5 rounded-xl';
        case 'md':
        default:
            return 'px-4 py-2.5 text-sm font-medium gap-2 rounded-lg';
    }
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'secondary':
            return 'bg-zinc-100 text-zinc-900 hover:bg-zinc-200/80 active:bg-zinc-200 border border-zinc-200/60 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700/80 dark:border-zinc-700/50';
        case 'outline':
            return 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 active:bg-zinc-100 shadow-2xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800/80';
        case 'ghost':
            return 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 active:bg-zinc-200/60 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100';
        case 'danger':
            return 'bg-rose-600 text-white hover:bg-rose-700 active:bg-rose-800 shadow-xs focus:ring-rose-500 dark:bg-rose-600 dark:hover:bg-rose-700';
        case 'success':
            return 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 shadow-xs focus:ring-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-700';
        case 'primary':
        default:
            return 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800 shadow-xs shadow-primary-500/25 focus:ring-primary-500';
    }
});

const baseClasses = computed(() => [
    'inline-flex items-center justify-center font-medium transition-all duration-150 select-none cursor-pointer focus:outline-hidden focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-zinc-950',
    sizeClasses.value,
    variantClasses.value,
    props.block ? 'w-full' : '',
    (props.disabled || props.loading) ? 'opacity-50 cursor-not-allowed pointer-events-none' : '',
]);
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :class="baseClasses"
    >
        <svg
            v-if="loading"
            class="h-4 w-4 animate-spin shrink-0"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>

        <slot name="prefix" />
        <slot />
        <slot name="suffix" />
    </Link>

    <button
        v-else
        :type="type"
        :disabled="disabled || loading"
        :class="baseClasses"
    >
        <svg
            v-if="loading"
            class="h-4 w-4 animate-spin shrink-0"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>

        <slot name="prefix" />
        <slot />
        <slot name="suffix" />
    </button>
</template>
