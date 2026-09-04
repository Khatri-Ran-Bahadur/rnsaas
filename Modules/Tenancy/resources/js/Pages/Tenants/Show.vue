<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import TextInput from '@/components/TextInput.vue';
import EmptyState from '@/components/EmptyState.vue';
import Dropdown from '@/components/Dropdown.vue';
import type { Tenant } from '@/types/tenancy';

const props = defineProps<{
    tenant: Tenant;
}>();

const activeTab = ref<'overview' | 'members' | 'activity'>('overview');
const isInviteModalOpen = ref(false);
const copiedField = ref<string | null>(null);

const inviteForm = useForm({
    email: '',
    name: '',
});

const submitInvite = () => {
    inviteForm.post(`/tenants/${props.tenant.id}/members/invite`, {
        onSuccess: () => {
            isInviteModalOpen.value = false;
            inviteForm.reset();
        },
    });
};

const handleSuspendMember = (userId: number) => {
    if (confirm('Are you sure you want to suspend this member?')) {
        router.post(`/tenants/${props.tenant.id}/members/${userId}/suspend`);
    }
};

const handleRevokeMember = (userId: number) => {
    if (confirm('Are you sure you want to revoke this membership?')) {
        router.post(`/tenants/${props.tenant.id}/members/${userId}/revoke`);
    }
};

const handleReactivateMember = (userId: number) => {
    router.post(`/tenants/${props.tenant.id}/members/${userId}/reactivate`);
};

const copyToClipboard = (text: string, field: string) => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
        copiedField.value = field;
        setTimeout(() => {
            copiedField.value = null;
        }, 2000);
    }
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AdminLayout
        :title="tenant.name"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Organizations', href: '/admin/tenants' },
            { label: tenant.name },
        ]"
    >
        <!-- Hero Header -->
        <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <!-- Org Avatar & Details -->
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-zinc-900 text-white font-bold text-xl uppercase shadow-sm dark:bg-zinc-100 dark:text-zinc-900">
                        {{ tenant.name.substring(0, 2) }}
                    </div>
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                {{ tenant.name }}
                            </h1>
                            <Badge :variant="tenant.status" />
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <!-- Copyable URL -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 font-mono text-xs bg-zinc-100 text-zinc-700 hover:bg-zinc-200/70 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700/60 transition-colors"
                                title="Click to copy subdomain"
                                @click="copyToClipboard(`https://app.sathisaas.com/${tenant.slug}`, 'url')"
                            >
                                <span>app.sathisaas.com/{{ tenant.slug }}</span>
                                <svg v-if="copiedField === 'url'" class="h-3 w-3 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-3 w-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>

                            <span>•</span>
                            <span>Created on {{ formatDate(tenant.created_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3 self-start sm:self-auto">
                    <Button
                        variant="primary"
                        size="sm"
                        @click="isInviteModalOpen = true"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </template>
                        <span>Invite Member</span>
                    </Button>

                    <Button
                        :href="`/admin/tenants/${tenant.id}/edit`"
                        variant="outline"
                        size="sm"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </template>
                        <span>Edit</span>
                    </Button>
                </div>
            </div>

            <!-- Quick Stats Metric Bar -->
            <div class="mt-6 grid grid-cols-2 gap-4 border-t border-zinc-100 pt-6 sm:grid-cols-4 dark:border-zinc-800">
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Status</p>
                    <p class="text-sm font-semibold capitalize text-zinc-900 dark:text-white">{{ tenant.status }}</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Members</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ tenant.users?.length ?? 0 }} users</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Currency & Region</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ tenant.currency || 'USD' }} • {{ tenant.country_code || 'Global' }}</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Timezone</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate" :title="tenant.timezone">{{ tenant.timezone || 'UTC' }}</p>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <div class="mt-6 border-b border-zinc-200 dark:border-zinc-800">
            <nav class="-mb-px flex space-x-8">
                <button
                    type="button"
                    :class="[
                        'pb-3 text-sm font-medium transition-colors border-b-2 flex items-center gap-2 cursor-pointer',
                        activeTab === 'overview'
                            ? 'border-zinc-900 text-zinc-900 font-semibold dark:border-white dark:text-white'
                            : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = 'overview'"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span>Overview</span>
                </button>

                <button
                    type="button"
                    :class="[
                        'pb-3 text-sm font-medium transition-colors border-b-2 flex items-center gap-2 cursor-pointer',
                        activeTab === 'members'
                            ? 'border-zinc-900 text-zinc-900 font-semibold dark:border-white dark:text-white'
                            : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = 'members'"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Members</span>
                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                        {{ tenant.users?.length ?? 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    :class="[
                        'pb-3 text-sm font-medium transition-colors border-b-2 flex items-center gap-2 cursor-pointer',
                        activeTab === 'activity'
                            ? 'border-zinc-900 text-zinc-900 font-semibold dark:border-white dark:text-white'
                            : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200',
                    ]"
                    @click="activeTab = 'activity'"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Activity & Audit</span>
                </button>
            </nav>
        </div>

        <!-- Tab 1: Overview Content -->
        <div v-if="activeTab === 'overview'" class="mt-6 space-y-6">
            <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-xs divide-y divide-zinc-100 dark:border-zinc-800/80 dark:bg-zinc-900 dark:divide-zinc-800">
                <!-- Section 1: Identity & Business -->
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Organization Details</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Core business profile and categorization.</p>
                        </div>
                        <Button :href="`/admin/tenants/${tenant.id}/edit`" variant="outline" size="xs">
                            Edit Details
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 pt-2">
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Business Name</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">{{ tenant.name }}</p>
                        </div>

                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Routing Slug</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ tenant.slug }}</p>
                        </div>

                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Industry / Sector</p>
                            <p class="mt-1 text-sm font-semibold capitalize text-zinc-900 dark:text-white">{{ tenant.industry || 'Not specified' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Regional & Localization -->
                <div class="p-6">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">Regional & Localization</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Timezone, currency and language preferences.</p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Country Code</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ tenant.country_code || '—' }}</p>
                        </div>

                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Currency</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ tenant.currency || 'USD' }}</p>
                        </div>

                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Timezone</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white truncate">{{ tenant.timezone || 'UTC' }}</p>
                        </div>

                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Default Locale</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ tenant.locale || 'en' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Technical Identifiers -->
                <div class="p-6">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">System & Identifiers</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Platform boundary and audit references.</p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex items-center justify-between rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Public ULID</p>
                                <p class="mt-1 font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ tenant.public_id }}</p>
                            </div>
                            <button
                                type="button"
                                class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-200/60 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                                title="Copy ULID"
                                @click="copyToClipboard(tenant.public_id, 'ulid')"
                            >
                                <svg v-if="copiedField === 'ulid'" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Database ID</p>
                                <p class="mt-1 font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">#{{ tenant.id }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-zinc-400 font-mono">Updated {{ formatDate(tenant.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Members Content -->
        <div v-else-if="activeTab === 'members'" class="mt-6">
            <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-100 p-6 dark:border-zinc-800/80">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                            Organization Members
                        </h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Users and administrators with access to this organization account.
                        </p>
                    </div>

                    <Button
                        variant="primary"
                        size="sm"
                        @click="isInviteModalOpen = true"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </template>
                        <span>Invite Member</span>
                    </Button>
                </div>

                <!-- Members Table -->
                <div v-if="tenant.users && tenant.users.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                        <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-900/60 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-6 py-3.5">User</th>
                                <th scope="col" class="px-6 py-3.5">Email</th>
                                <th scope="col" class="px-6 py-3.5">Membership Status</th>
                                <th scope="col" class="px-6 py-3.5">Joined / Invited</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                            <tr v-for="user in tenant.users" :key="user.id" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-900 text-white text-xs font-semibold uppercase dark:bg-zinc-100 dark:text-zinc-900 shadow-2xs">
                                            {{ (user.name || user.email).charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-zinc-900 dark:text-white">{{ user.name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 font-mono text-xs">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-4">
                                    <Badge :variant="user.pivot?.status || 'active'" />
                                </td>
                                <td class="px-6 py-4 text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                                    {{ formatDate(user.pivot?.joined_at || user.pivot?.invited_at || user.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Dropdown width="w-44" align="right">
                                        <template #trigger="{ isOpen }">
                                            <button
                                                type="button"
                                                class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300 transition-colors"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #default="{ close }">
                                            <div class="py-1">
                                                <button
                                                    v-if="user.pivot?.status === 'active' || user.pivot?.status === 'invited'"
                                                    type="button"
                                                    class="flex w-full items-center px-3 py-1.5 text-xs font-medium text-amber-600 hover:bg-zinc-50 dark:text-amber-400 dark:hover:bg-zinc-800"
                                                    @click="handleSuspendMember(user.id); close()"
                                                >
                                                    Suspend Access
                                                </button>
                                                <button
                                                    v-if="user.pivot?.status === 'suspended' || user.pivot?.status === 'revoked'"
                                                    type="button"
                                                    class="flex w-full items-center px-3 py-1.5 text-xs font-medium text-emerald-600 hover:bg-zinc-50 dark:text-emerald-400 dark:hover:bg-zinc-800"
                                                    @click="handleReactivateMember(user.id); close()"
                                                >
                                                    Reactivate Access
                                                </button>
                                                <button
                                                    v-if="user.pivot?.status !== 'revoked'"
                                                    type="button"
                                                    class="flex w-full items-center px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-zinc-50 dark:text-rose-400 dark:hover:bg-zinc-800"
                                                    @click="handleRevokeMember(user.id); close()"
                                                >
                                                    Revoke Access
                                                </button>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-8">
                    <EmptyState
                        title="No members assigned"
                        description="This organization currently has no associated users or members."
                    >
                        <template #actions>
                            <Button
                                variant="primary"
                                size="sm"
                                @click="isInviteModalOpen = true"
                            >
                                Invite First Member
                            </Button>
                        </template>
                    </EmptyState>
                </div>
            </div>
        </div>

        <!-- Tab 3: Activity & Audit Content -->
        <div v-else-if="activeTab === 'activity'" class="mt-6">
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                        Activity & Audit Log
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        System-recorded lifecycle and operational events for this organization.
                    </p>
                </div>

                <div class="border-t border-zinc-100 dark:border-zinc-800/80 pt-6">
                    <div class="space-y-6">
                        <!-- Initial Creation Entry -->
                        <div class="relative flex gap-4 before:absolute before:left-3.5 before:top-8 before:bottom-0 before:w-px before:bg-zinc-200 dark:before:bg-zinc-800">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 shadow-2xs">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div class="pt-0.5 space-y-1">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Organization Provisioned</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Initial organization registered with public ULID <code class="font-mono font-medium text-zinc-800 dark:text-zinc-200">{{ tenant.public_id }}</code>
                                </p>
                                <span class="inline-block text-[11px] font-mono text-zinc-400">{{ formatDate(tenant.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Invitation Modal Dialog -->
        <Modal
            :show="isInviteModalOpen"
            title="Invite Organization Member"
            description="Send an invitation email to add a new member or administrator to this organization."
            @close="isInviteModalOpen = false"
        >
            <form class="space-y-4" @submit.prevent="submitInvite">
                <div>
                    <TextInput
                        v-model="inviteForm.email"
                        label="Email Address"
                        type="email"
                        placeholder="colleague@example.com"
                        :error="inviteForm.errors.email"
                        required
                        autofocus
                    />
                </div>

                <div>
                    <TextInput
                        v-model="inviteForm.name"
                        label="Full Name (Optional)"
                        placeholder="e.g. John Doe"
                        :error="inviteForm.errors.name"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <Button
                        variant="outline"
                        @click="isInviteModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="inviteForm.processing"
                    >
                        Send Invitation
                    </Button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
