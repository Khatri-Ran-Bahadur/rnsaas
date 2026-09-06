<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';
import Dropdown from '@/components/Dropdown.vue';
import Pagination from '@/components/Pagination.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface TenantRole {
    id: number;
    name: string;
    slug: string;
}

interface InvitationItem {
    id: number;
    tenant_id: number;
    user_id: number;
    role_id: number | null;
    status: 'invited' | 'active' | 'suspended' | 'revoked';
    invited_at: string | null;
    expires_at: string | null;
    invitation_token: string | null;
    invitation_url: string | null;
    is_expired: boolean;
    user: User;
    role?: TenantRole | null;
    invited_by?: User | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedInvitations {
    data: InvitationItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

const props = defineProps<{
    invitations: PaginatedInvitations;
    roles: TenantRole[];
    filters: {
        search?: string;
        status?: string;
        per_page?: number;
    };
}>();

const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? props.invitations.per_page ?? 15);
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        '/admin/invitations',
        {
            search: search.value.trim() || undefined,
            status: selectedStatus.value || undefined,
            per_page: perPage.value !== 15 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
            },
        }
    );
};

const onSearchInput = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
};

const resetAllFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    applyFilters();
};

// Modals
const isInviteModalOpen = ref(false);
const isRevokeModalOpen = ref(false);
const activeInvitation = ref<InvitationItem | null>(null);
const copiedId = ref<number | null>(null);

const inviteForm = useForm({
    email: '',
    name: '',
    role_id: props.roles.length > 0 ? props.roles[0].id : '',
});

const openInviteModal = () => {
    inviteForm.reset();
    inviteForm.clearErrors();
    if (props.roles.length > 0) {
        inviteForm.role_id = props.roles[0].id;
    }
    isInviteModalOpen.value = true;
};

const submitInvite = () => {
    inviteForm.post('/admin/invitations', {
        onSuccess: () => {
            isInviteModalOpen.value = false;
            inviteForm.reset();
        },
    });
};

const resendInvitation = (invitation: InvitationItem) => {
    router.post(`/admin/invitations/${invitation.id}/resend`, {}, {
        preserveScroll: true,
    });
};

const openRevokeModal = (invitation: InvitationItem) => {
    activeInvitation.value = invitation;
    isRevokeModalOpen.value = true;
};

const confirmRevoke = () => {
    if (!activeInvitation.value) return;
    router.delete(`/admin/invitations/${activeInvitation.value.id}/revoke`, {
        onSuccess: () => {
            isRevokeModalOpen.value = false;
            activeInvitation.value = null;
        },
    });
};

const copyLink = async (invitation: InvitationItem) => {
    if (!invitation.invitation_url) return;
    try {
        await navigator.clipboard.writeText(invitation.invitation_url);
        copiedId.value = invitation.id;
        setTimeout(() => {
            copiedId.value = null;
        }, 2500);
    } catch {
        // clipboard unavailable
    }
};

const formatDate = (dateStr?: string | null): string => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <OrganizationLayout
        title="Invitations"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Users & Access' },
            { label: 'Invitations' },
        ]"
    >
        <Head title="Organization Invitations" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Invitations
                    </h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Send and manage member invitation links to join this organization.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        Total: {{ invitations.total }}
                    </span>

                    <Button variant="primary" size="sm" @click="openInviteModal">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Invite Member
                    </Button>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Search Input -->
                    <div class="relative lg:col-span-2">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name or email..."
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 pl-10 pr-4 py-2 text-xs text-zinc-900 placeholder-zinc-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100 dark:focus:border-indigo-500"
                            @input="onSearchInput"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select
                            v-model="selectedStatus"
                            class="w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                            @change="applyFilters"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="expired">Expired</option>
                            <option value="active">Accepted / Active</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>

                    <div v-if="search || selectedStatus" class="flex items-center">
                        <button
                            type="button"
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 cursor-pointer"
                            @click="resetAllFilters"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Invitations Table -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-left text-xs dark:divide-zinc-800">
                        <thead class="bg-zinc-50/75 dark:bg-zinc-950/60">
                            <tr>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Invitee</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Role</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Invited By</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Sent Date</th>
                                <th class="px-6 py-3.5 font-semibold text-zinc-700 dark:text-zinc-300">Expires</th>
                                <th class="px-6 py-3.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="inv in invitations.data"
                                :key="inv.id"
                                class="transition-colors hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-zinc-900 dark:text-white">
                                            {{ inv.user.name }}
                                        </p>
                                        <p class="font-mono text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ inv.user.email }}
                                        </p>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ inv.role?.name ?? '—' }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <Badge v-if="inv.status === 'active'" variant="success">
                                        Accepted
                                    </Badge>
                                    <Badge v-else-if="inv.status === 'revoked'" variant="default">
                                        Revoked
                                    </Badge>
                                    <Badge v-else-if="inv.is_expired" variant="danger">
                                        Expired
                                    </Badge>
                                    <Badge v-else variant="warning">
                                        Pending
                                    </Badge>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-zinc-600 dark:text-zinc-300">
                                    {{ inv.invited_by?.name ?? 'System' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                    {{ formatDate(inv.invited_at) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                    {{ formatDate(inv.expires_at) }}
                                </td>

                                <!-- Actions Dropdown -->
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <Dropdown align="right" width="w-48">
                                            <template #trigger>
                                                <button
                                                    type="button"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors cursor-pointer"
                                                    title="Actions"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                    </svg>
                                                </button>
                                            </template>
                                            <template #default="{ close }">
                                                <div class="py-1 text-xs">
                                                    <!-- Copy Invitation Link -->
                                                    <button
                                                        v-if="inv.status === 'invited' && inv.invitation_url"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="copyLink(inv); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                        </svg>
                                                        <span>{{ copiedId === inv.id ? 'Copied!' : 'Copy Link' }}</span>
                                                    </button>

                                                    <!-- Resend Invitation -->
                                                    <button
                                                        v-if="inv.status === 'invited'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="resendInvitation(inv); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        <span>Resend Invitation</span>
                                                    </button>

                                                    <!-- Revoke Invitation -->
                                                    <button
                                                        v-if="inv.status === 'invited'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 transition-colors rounded-md text-left cursor-pointer"
                                                        @click="openRevokeModal(inv); close()"
                                                    >
                                                        <svg class="h-4 w-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        <span>Revoke Invitation</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="invitations.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">No invitations found</p>
                                        <p class="mt-1 text-xs text-zinc-400">
                                            {{ search || selectedStatus ? 'Try adjusting your filters.' : 'Send your first member invitation to get started.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="flex flex-col items-center justify-between gap-4 border-t border-zinc-200 px-6 py-4 sm:flex-row dark:border-zinc-800">
                    <PerPageSelector
                        v-model="perPage"
                        @change="applyFilters"
                    />

                    <div v-if="invitations.links && invitations.links.length > 3">
                        <Pagination :links="invitations.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Invite Member Modal -->
        <Modal
            :show="isInviteModalOpen"
            title="Invite Member to Organization"
            max-width="md"
            @close="isInviteModalOpen = false"
        >
            <form @submit.prevent="submitInvite" class="space-y-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    The recipient will receive an invitation token valid for 7 days to join this organization.
                </p>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Email Address <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="inviteForm.email"
                        type="email"
                        required
                        placeholder="colleague@example.com"
                        class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                    />
                    <p v-if="inviteForm.errors.email" class="mt-1 text-xs text-rose-500">
                        {{ inviteForm.errors.email }}
                    </p>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Invitee Name <span class="text-zinc-400 font-normal">(Optional)</span>
                    </label>
                    <input
                        v-model="inviteForm.name"
                        type="text"
                        placeholder="e.g. Alex Smith"
                        class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                    />
                    <p v-if="inviteForm.errors.name" class="mt-1 text-xs text-rose-500">
                        {{ inviteForm.errors.name }}
                    </p>
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Organization Role <span class="text-rose-500">*</span>
                    </label>
                    <select
                        v-model="inviteForm.role_id"
                        required
                        class="mt-1.5 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 px-3.5 py-2 text-xs text-zinc-900 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-950/60 dark:text-zinc-100"
                    >
                        <option v-for="r in roles" :key="r.id" :value="r.id">
                            {{ r.name }}
                        </option>
                    </select>
                    <p v-if="inviteForm.errors.role_id" class="mt-1 text-xs text-rose-500">
                        {{ inviteForm.errors.role_id }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" type="button" @click="isInviteModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="primary" size="sm" type="submit" :disabled="inviteForm.processing">
                        {{ inviteForm.processing ? 'Sending...' : 'Send Invitation' }}
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Revoke Invitation Modal -->
        <Modal
            :show="isRevokeModalOpen"
            title="Revoke Invitation"
            max-width="md"
            @close="isRevokeModalOpen = false"
        >
            <div class="space-y-4">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    Are you sure you want to revoke the invitation for <strong v-if="activeInvitation">{{ activeInvitation.user.email }}</strong>?
                    The invitation link will immediately become invalid.
                </p>
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <Button variant="outline" size="sm" @click="isRevokeModalOpen = false">
                        Cancel
                    </Button>
                    <Button variant="danger" size="sm" @click="confirmRevoke">
                        Revoke Invitation
                    </Button>
                </div>
            </div>
        </Modal>
    </OrganizationLayout>
</template>
