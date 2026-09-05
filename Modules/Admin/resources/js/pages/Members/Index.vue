<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Pagination from '@/components/Pagination.vue';

interface Member {
    id: number;
    name: string;
    email: string;
    status?: string;
    joined_at?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedMembers {
    data: Member[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
}

const props = defineProps<{
    members: PaginatedMembers;
}>();

const search = ref('');

const filteredMembers = computed(() => {
    if (!search.value.trim()) {
        return props.members.data;
    }
    const q = search.value.toLowerCase();
    return props.members.data.filter(
        (m) => m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q)
    );
});
</script>

<template>
    <OrganizationLayout
        title="Organization Members"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Members' },
        ]"
    >
        <Head title="Organization Members" />

        <div class="space-y-6">
            <!-- Header with Title & Summary -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Members
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        View and manage active accounts belonging to your organization.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        Total: {{ members.total }}
                    </span>
                </div>
            </div>

            <!-- Search & Filters Bar -->
            <div class="flex items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="relative flex-1 max-w-md">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search members by name or email..."
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 pl-10 pr-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100 dark:focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Members Data Table -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-left text-xs dark:divide-zinc-800">
                        <thead class="bg-zinc-50/75 dark:bg-zinc-950/60">
                            <tr>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Member</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Email</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Organization Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="member in filteredMembers"
                                :key="member.id"
                                class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-zinc-900 dark:text-white">
                                                {{ member.name }}
                                            </p>
                                            <p class="text-[11px] text-zinc-400">ID #{{ member.id }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-zinc-600 dark:text-zinc-300 font-mono">
                                    {{ member.email }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <Badge variant="success">Active</Badge>
                                </td>
                            </tr>

                            <tr v-if="filteredMembers.length === 0">
                                <td colspan="3" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">No members found</p>
                                        <p class="mt-1 text-xs text-zinc-400">Try adjusting your search query.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="members.links && members.links.length > 3" class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <Pagination :links="members.links" />
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
