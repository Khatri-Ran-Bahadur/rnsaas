<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';
import Modal from '@/components/Modal.vue';

interface Branch {
    id: number;
    public_id: string;
    tenant_id: number;
    name: string;
    code: string;
    status: 'active' | 'inactive';
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country_code: string | null;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    branch: Branch;
}>();

const showDeactivateModal = ref(false);
const isDeactivating = ref(false);

const showActivateModal = ref(false);
const isActivating = ref(false);

const copiedField = ref<string | null>(null);

const copyToClipboard = (text: string, fieldName: string) => {
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(text);
    copiedField.value = fieldName;
    setTimeout(() => {
        copiedField.value = null;
    }, 2000);
};

const confirmDeactivate = () => {
    isDeactivating.value = true;
    router.patch(
        `/admin/branches/${props.branch.public_id}/deactivate`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showDeactivateModal.value = false;
            },
            onFinish: () => {
                isDeactivating.value = false;
            },
        }
    );
};

const confirmActivate = () => {
    isActivating.value = true;
    router.patch(
        `/admin/branches/${props.branch.public_id}/activate`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showActivateModal.value = false;
            },
            onFinish: () => {
                isActivating.value = false;
            },
        }
    );
};

const formatDateTime = (dateStr: string) => {
    if (!dateStr) return '—';
    try {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};

const getFullAddress = () => {
    const parts = [
        props.branch.address_line_1,
        props.branch.address_line_2,
        props.branch.city,
        props.branch.state,
        props.branch.postal_code,
        props.branch.country_code,
    ].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : 'No physical address configured';
};
</script>

<template>
    <OrganizationLayout
        :title="branch.name"
        :breadcrumbs="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Branches', href: '/admin/branches' },
            { label: branch.name },
        ]"
    >
        <Head :title="`${branch.name} - Branch Details`" />

        <div class="mx-auto max-w-5xl space-y-6">
            <!-- Executive Hero Card -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="p-6">
                    <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center">
                        <!-- Left Info -->
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 font-bold text-indigo-700 ring-1 ring-indigo-500/15 shadow-xs dark:bg-indigo-950/70 dark:text-indigo-300 dark:ring-indigo-500/30">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                        {{ branch.name }}
                                    </h1>
                                    <Badge
                                        :variant="branch.status === 'active' ? 'active' : 'neutral'"
                                        size="md"
                                    >
                                        {{ branch.status === 'active' ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                                    <div class="flex items-center gap-1.5">
                                        <span>Branch Code:</span>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-md border border-zinc-200/80 bg-zinc-100/80 px-2 py-0.5 font-mono text-xs font-semibold text-zinc-800 hover:bg-zinc-200 transition-colors dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 cursor-pointer"
                                            title="Click to copy code"
                                            @click="copyToClipboard(branch.code, 'code')"
                                        >
                                            <span>{{ branch.code }}</span>
                                            <svg v-if="copiedField === 'code'" class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="h-3 w-3 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <span v-if="copiedField === 'code'" class="text-[11px] font-semibold text-emerald-600">Copied!</span>
                                    </div>

                                    <span class="text-zinc-300 dark:text-zinc-700">•</span>
                                    <span>Added {{ formatDateTime(branch.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <Button
                                href="/admin/branches"
                                variant="outline"
                                size="sm"
                            >
                                <template #prefix>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                </template>
                                All Branches
                            </Button>

                            <Button
                                :href="`/admin/branches/${branch.public_id}/edit`"
                                variant="secondary"
                                size="sm"
                            >
                                <template #prefix>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </template>
                                Edit Branch
                            </Button>

                            <Button
                                v-if="branch.status === 'active'"
                                variant="danger"
                                size="sm"
                                @click="showDeactivateModal = true"
                            >
                                Deactivate
                            </Button>

                            <Button
                                v-else
                                variant="success"
                                size="sm"
                                @click="showActivateModal = true"
                            >
                                Activate
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Hero Bottom Metrics Bar -->
                <div class="grid grid-cols-2 divide-x divide-y sm:divide-y-0 sm:grid-cols-4 divide-zinc-100 border-t border-zinc-100 bg-zinc-50/50 dark:divide-zinc-800 dark:border-zinc-800/80 dark:bg-zinc-950/40 text-xs">
                    <div class="p-3.5 text-center sm:text-left sm:px-6">
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Status</span>
                        <div class="mt-0.5 flex items-center justify-center sm:justify-start gap-1.5 font-semibold text-zinc-900 dark:text-white">
                            <span :class="['h-2 w-2 rounded-full', branch.status === 'active' ? 'bg-emerald-500' : 'bg-zinc-400']" />
                            <span>{{ branch.status === 'active' ? 'Operational' : 'Suspended' }}</span>
                        </div>
                    </div>

                    <div class="p-3.5 text-center sm:text-left sm:px-6">
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">City / Region</span>
                        <p class="mt-0.5 font-semibold text-zinc-900 dark:text-white">
                            {{ branch.city || 'Not Specified' }}
                        </p>
                    </div>

                    <div class="p-3.5 text-center sm:text-left sm:px-6">
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Country</span>
                        <p class="mt-0.5 font-mono font-semibold text-zinc-900 dark:text-white">
                            {{ branch.country_code || '—' }}
                        </p>
                    </div>

                    <div class="p-3.5 text-center sm:text-left sm:px-6">
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Last Modified</span>
                        <p class="mt-0.5 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ formatDateTime(branch.updated_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Two-Column Balanced Content Grid (8 cols + 4 cols) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left 8 Columns: Address & Operations Cards -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Location & Physical Address Card -->
                    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 font-bold text-emerald-700 ring-1 ring-emerald-500/15 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-500/30">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                                    Physical Address & Geolocation
                                </h2>
                            </div>

                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors cursor-pointer"
                                @click="copyToClipboard(getFullAddress(), 'full_address')"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>{{ copiedField === 'full_address' ? 'Copied Full Address!' : 'Copy Address' }}</span>
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Full Address Box -->
                            <div class="rounded-xl border border-zinc-100 bg-zinc-50/70 p-4 dark:border-zinc-800/80 dark:bg-zinc-950/50">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Formatted Address</span>
                                <p class="mt-1 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ getFullAddress() }}
                                </p>
                            </div>

                            <!-- Detailed Address Breakdown -->
                            <dl class="mt-4 grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 text-xs">
                                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800/60">
                                    <dt class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Address Line 1</dt>
                                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ branch.address_line_1 || '—' }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800/60">
                                    <dt class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Address Line 2</dt>
                                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ branch.address_line_2 || '—' }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800/60">
                                    <dt class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">City</dt>
                                    <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ branch.city || '—' }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800/60">
                                    <dt class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">State / Province</dt>
                                    <dd class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ branch.state || '—' }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800/60">
                                    <dt class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Postal / ZIP Code</dt>
                                    <dd class="mt-1 font-mono font-semibold text-zinc-900 dark:text-white">{{ branch.postal_code || '—' }}</dd>
                                </div>

                                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800/60">
                                    <dt class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Country Code</dt>
                                    <dd class="mt-1 font-mono font-semibold text-zinc-900 dark:text-white">{{ branch.country_code || '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Right 4 Columns: Operational Status & System Identifiers -->
                <div class="space-y-6 lg:col-span-4">
                    <!-- Quick Status Management Card -->
                    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 bg-zinc-50/50 px-5 py-3.5 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                            <h3 class="text-xs font-semibold text-zinc-900 dark:text-white">
                                Operational Status
                            </h3>
                        </div>

                        <div class="p-5 space-y-4 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-500 dark:text-zinc-400">Current Status:</span>
                                <Badge :variant="branch.status === 'active' ? 'active' : 'neutral'">
                                    {{ branch.status === 'active' ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>

                            <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed">
                                {{ branch.status === 'active' ? 'This branch is currently active. Employees and inventory can be mapped to this location.' : 'This branch is inactive. Operations are temporarily suspended.' }}
                            </p>

                            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                <Button
                                    v-if="branch.status === 'active'"
                                    variant="danger"
                                    size="sm"
                                    block
                                    @click="showDeactivateModal = true"
                                >
                                    Deactivate Branch
                                </Button>
                                <Button
                                    v-else
                                    variant="success"
                                    size="sm"
                                    block
                                    @click="showActivateModal = true"
                                >
                                    Activate Branch
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- System & Security Metadata Card -->
                    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 bg-zinc-50/50 px-5 py-3.5 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                            <h3 class="text-xs font-semibold text-zinc-900 dark:text-white">
                                System Identifiers & Audit
                            </h3>
                        </div>

                        <div class="p-5 space-y-3.5 text-xs">
                            <div>
                                <span class="text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">Public UUID</span>
                                <div class="mt-1 flex items-center justify-between rounded-lg bg-zinc-50 p-2 font-mono text-[11px] text-zinc-800 dark:bg-zinc-950 dark:text-zinc-200">
                                    <span class="truncate max-w-[200px]" :title="branch.public_id">{{ branch.public_id }}</span>
                                    <button
                                        type="button"
                                        class="ml-2 shrink-0 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 cursor-pointer"
                                        title="Copy UUID"
                                        @click="copyToClipboard(branch.public_id, 'uuid')"
                                    >
                                        <svg v-if="copiedField === 'uuid'" class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-between py-1 border-t border-zinc-100 dark:border-zinc-800/60">
                                <span class="text-zinc-400 dark:text-zinc-500">Created:</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ formatDateTime(branch.created_at) }}</span>
                            </div>

                            <div class="flex justify-between py-1 border-t border-zinc-100 dark:border-zinc-800/60">
                                <span class="text-zinc-400 dark:text-zinc-500">Last Modified:</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ formatDateTime(branch.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deactivate Confirmation Modal -->
        <Modal
            :show="showDeactivateModal"
            title="Deactivate Branch"
            description="Are you sure you want to deactivate this branch?"
            @close="showDeactivateModal = false"
        >
            <div class="space-y-3">
                <p class="text-xs text-zinc-600 dark:text-zinc-300">
                    You are about to deactivate
                    <strong class="font-semibold text-zinc-900 dark:text-white">{{ branch.name }}</strong>
                    <span class="font-mono text-zinc-500">({{ branch.code }})</span>.
                </p>
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-300">
                    <p class="font-medium">Please note:</p>
                    <p class="mt-1">
                        An inactive branch remains preserved in your organization's database. Users and operations mapped to this branch can be resumed at any time by reactivating it.
                    </p>
                </div>
            </div>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isDeactivating"
                    @click="showDeactivateModal = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="danger"
                    size="sm"
                    :loading="isDeactivating"
                    @click="confirmDeactivate"
                >
                    Deactivate Branch
                </Button>
            </template>
        </Modal>

        <!-- Activate Confirmation Modal -->
        <Modal
            :show="showActivateModal"
            title="Activate Branch"
            description="Reactivate this branch for normal operations."
            @close="showActivateModal = false"
        >
            <p class="text-xs text-zinc-600 dark:text-zinc-300">
                Are you sure you want to reactivate
                <strong class="font-semibold text-zinc-900 dark:text-white">{{ branch.name }}</strong>
                <span class="font-mono text-zinc-500">({{ branch.code }})</span>?
                It will immediately resume full availability for normal organization usage.
            </p>

            <template #footer>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isActivating"
                    @click="showActivateModal = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="success"
                    size="sm"
                    :loading="isActivating"
                    @click="confirmActivate"
                >
                    Activate Branch
                </Button>
            </template>
        </Modal>
    </OrganizationLayout>
</template>
