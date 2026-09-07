<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    currentSection?: string;
}>();

const navItems = [
    {
        id: 'general',
        label: 'General',
        href: '/superadmin/settings',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    },
    {
        id: 'security',
        label: 'Security',
        href: '/superadmin/security',
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    },
    {
        id: 'notifications',
        label: 'Notifications',
        href: '/superadmin/settings?tab=mail',
        icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    },
    {
        id: 'file-system',
        label: 'File System',
        href: '/superadmin/settings/file-system',
        icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
        badge: 'Storage',
    },
];
</script>

<template>
    <div class="border-b border-zinc-200 dark:border-zinc-800 mb-8">
        <nav class="-mb-px flex space-x-1 sm:space-x-4 overflow-x-auto pb-1" aria-label="Settings Navigation">
            <Link
                v-for="item in navItems"
                :key="item.id"
                :href="item.href"
                :class="[
                    item.id === (currentSection || 'file-system')
                        ? 'border-primary-600 text-primary-600 dark:border-primary-500 dark:text-primary-400 font-semibold'
                        : 'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-700 dark:hover:text-zinc-200 font-medium',
                    'group inline-flex items-center gap-2 border-b-2 py-3 px-3 text-xs sm:text-sm whitespace-nowrap transition-all duration-150',
                ]"
            >
                <svg
                    class="h-4 w-4 shrink-0 transition-colors"
                    :class="[
                        item.id === (currentSection || 'file-system')
                            ? 'text-primary-600 dark:text-primary-400'
                            : 'text-zinc-400 group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300',
                    ]"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                </svg>
                <span>{{ item.label }}</span>
                <span
                    v-if="item.badge"
                    class="ml-1 rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border border-primary-200 dark:border-primary-800/60"
                >
                    {{ item.badge }}
                </span>
            </Link>
        </nav>
    </div>
</template>
