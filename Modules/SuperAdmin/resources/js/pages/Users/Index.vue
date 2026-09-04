<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StatsCard from '@/components/StatsCard.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Select from '@/components/Select.vue';
import Pagination from '@/components/Pagination.vue';
import EmptyState from '@/components/EmptyState.vue';
import SearchInput from '@/components/SearchInput.vue';

export interface RoleItem {
    id: number;
    name: string;
}

export interface UserItem {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    tenants_count: number;
    created_at: string;
    roles: RoleItem[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedUsers {
    data: UserItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface Filters {
    search?: string;
    role?: string;
}

const props = defineProps<{
    users: PaginatedUsers;
    roles: RoleItem[];
    filters?: Filters;
}>();

// Filter States
const search = ref(props.filters?.search ?? '');
const selectedRole = ref(props.filters?.role ?? '');
const isFiltering = ref(false);

const roleOptions = computed(() => [
    { label: 'All Roles', value: '' },
    ...props.roles.map((r) => ({ label: r.name, value: r.name })),
]);

const hasActiveFilters = computed(() => {
    return Boolean(search.value || selectedRole.value);
});

const applyFilters = () => {
    isFiltering.value = true;
    router.get(
        '/superadmin/users',
        {
            search: search.value || undefined,
            role: selectedRole.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        }
    );
};

const handleRoleChange = (value: string | number) => {
    selectedRole.value = String(value);
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
    applyFilters();
};

// Utilities
const getInitials = (name: string): string => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

const formatDate = (dateStr?: string | null): string => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getRoleBadgeVariant = (roleName: string) => {
    switch (roleName.toLowerCase()) {
        case 'superadmin':
        case 'admin':
            return 'active';
        case 'manager':
            return 'pending';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <AdminLayout
        title="Platform Users"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Users' },
        ]"
    >
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Platform Users
                    </h1>
                    <span
                        class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700"
                    >
                        {{ users.total }} Total
                    </span>
                </div>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Manage and monitor registered SathiSaaS accounts.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span
                    v-if="hasActiveFilters"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-primary-500 animate-pulse" />
                    Filters active ({{ users.total }} results)
                </span>
            </div>
        </div>

        <!-- Subtle Summary Metric Cards (Based strictly on verified available props) -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <StatsCard
                title="Total Platform Users"
                :value="users.total"
                subtitle="Registered accounts"
                badge-color="blue"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </template>
            </StatsCard>

            <StatsCard
                title="Configured Roles"
                :value="roles.length"
                subtitle="System authorization roles"
                badge-color="indigo"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </template>
            </StatsCard>

            <StatsCard
                title="Current Page Records"
                :value="users.data.length"
                :subtitle="`Showing page ${users.current_page} of ${users.last_page}`"
                badge-color="emerald"
            >
                <template #icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </template>
            </StatsCard>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="mt-6 rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="flex-1 min-w-[240px] max-w-lg">
                    <SearchInput
                        v-model="search"
                        placeholder="Search by name or email..."
                        @search="applyFilters"
                        @clear="applyFilters"
                    />
                </div>

                <!-- Filters & Actions -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Role Filter Dropdown -->
                    <div class="w-48">
                        <Select
                            :model-value="selectedRole"
                            :options="roleOptions"
                            placeholder="All roles"
                            @update:model-value="handleRoleChange"
                        />
                    </div>

                    <!-- Clear Filters Button -->
                    <Button
                        v-if="hasActiveFilters"
                        variant="outline"
                        size="sm"
                        class="h-10 text-xs gap-1.5"
                        @click="clearFilters"
                    >
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Clear Filters</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="mt-6 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
            <!-- Desktop Table View -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                    <thead class="border-b border-zinc-200/80 bg-zinc-50/75 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="py-3.5 pl-5 pr-3">User</th>
                            <th scope="col" class="px-3 py-3.5">Role</th>
                            <th scope="col" class="px-3 py-3.5">Organizations</th>
                            <th scope="col" class="px-3 py-3.5">Verification</th>
                            <th scope="col" class="px-3 py-3.5">Joined</th>
                            <th scope="col" class="py-3.5 pl-3 pr-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/80 dark:divide-zinc-800">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition-colors"
                        >
                            <!-- User Name & Email with Avatar -->
                            <td class="py-4 pl-5 pr-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-xs font-bold text-primary-700 ring-1 ring-primary-600/20 dark:bg-primary-950/50 dark:text-primary-300 dark:ring-primary-500/30 shadow-2xs"
                                    >
                                        {{ getInitials(user.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-zinc-900 dark:text-white truncate">
                                            {{ user.name }}
                                        </div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Roles -->
                            <td class="px-3 py-4">
                                <div v-if="user.roles && user.roles.length > 0" class="flex flex-wrap gap-1.5">
                                    <Badge
                                        v-for="role in user.roles"
                                        :key="role.id"
                                        :variant="getRoleBadgeVariant(role.name)"
                                        :label="role.name"
                                    />
                                </div>
                                <span v-else class="text-xs text-zinc-400 dark:text-zinc-500 italic">
                                    No role
                                </span>
                            </td>

                            <!-- Organizations (Tenants Count) -->
                            <td class="px-3 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800/80 dark:text-zinc-300"
                                >
                                    <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span>{{ user.tenants_count }} {{ user.tenants_count === 1 ? 'organization' : 'organizations' }}</span>
                                </span>
                            </td>

                            <!-- Verification Status -->
                            <td class="px-3 py-4">
                                <Badge
                                    v-if="user.email_verified_at"
                                    variant="active"
                                    label="Verified"
                                />
                                <Badge
                                    v-else
                                    variant="neutral"
                                    label="Unverified"
                                />
                            </td>

                            <!-- Joined Date -->
                            <td class="px-3 py-4 text-xs text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                {{ formatDate(user.created_at) }}
                            </td>

                            <!-- Action Area: Link to dedicated user details page -->
                            <td class="py-4 pl-3 pr-5 text-right">
                                <Link
                                    :href="`/superadmin/users/${user.id}`"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700/60 dark:hover:text-white transition-colors shadow-2xs"
                                    title="View User Details"
                                >
                                    <svg class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>View</span>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="users.data.length === 0" class="p-8">
                <EmptyState
                    :title="hasActiveFilters ? 'No users found' : 'No users registered yet'"
                    :description="hasActiveFilters ? 'Try changing your search keywords or role filter.' : 'Platform users will appear here once accounts are registered.'"
                >
                    <template #icon>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </template>
                    <template v-if="hasActiveFilters" #actions>
                        <Button variant="outline" size="sm" @click="clearFilters">
                            <span>Reset Filters</span>
                        </Button>
                    </template>
                </EmptyState>
            </div>

            <!-- Card Footer Pagination -->
            <div
                v-if="users.total > 0"
                class="border-t border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
            >
                <Pagination :data="users" />
            </div>
        </div>
    </AdminLayout>
</template>
