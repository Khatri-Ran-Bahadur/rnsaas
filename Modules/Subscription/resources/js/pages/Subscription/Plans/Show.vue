<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Badge from '@/components/Badge.vue';
import Button from '@/components/Button.vue';

interface Feature {
    id: number;
    public_id: string;
    name: string;
    slug: string;
    description: string | null;
    module: string;
    is_active: boolean;
    sort_order: number;
    pivot?: {
        enabled: boolean;
        limits: Record<string, any> | null;
    };
}

interface Plan {
    id: number;
    public_id: string;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    currency: string;
    billing_cycle: string;
    trial_days: number;
    is_active: boolean;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
    features?: Feature[];
}

const props = defineProps<{
    plan: Plan;
    allFeatures?: Feature[];
}>();

const copiedField = ref<string | null>(null);

const copyToClipboard = (text: string, field: string) => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
        copiedField.value = field;
        setTimeout(() => {
            copiedField.value = null;
        }, 2000);
    }
};

const formatCurrency = (amount: string | number, currency: string = 'USD') => {
    const num = Number(amount);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency || 'USD',
    }).format(num);
};

const formatDate = (dateStr?: string) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const planFeatureIds = computed(() => {
    const list = props.plan.features || [];
    return new Set(list.map((f) => f.id));
});

// Group all features by module dynamically
const groupedModules = computed(() => {
    const sourceFeatures = (props.allFeatures && props.allFeatures.length > 0)
        ? props.allFeatures
        : (props.plan.features || []);

    const groups: Record<string, { moduleName: string; features: Feature[] }> = {};

    for (const feature of sourceFeatures) {
        const mod = feature.module || 'General';
        if (!groups[mod]) {
            groups[mod] = {
                moduleName: mod.charAt(0).toUpperCase() + mod.slice(1),
                features: [],
            };
        }
        groups[mod].features.push(feature);
    }

    return Object.values(groups);
});
</script>

<template>
    <AdminLayout
        :title="`${plan.name} - Plan Details`"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/dashboard' },
            { label: 'Subscriptions', href: '/admin/subscriptions/plans' },
            { label: 'Plans', href: '/admin/subscriptions/plans' },
            { label: plan.name },
        ]"
    >
        <!-- Hero Header -->
        <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <!-- Plan Icon & Title -->
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-zinc-900 text-white font-bold text-xl uppercase shadow-sm dark:bg-zinc-100 dark:text-zinc-900">
                        {{ plan.name.substring(0, 2).toUpperCase() }}
                    </div>
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                                {{ plan.name }}
                            </h1>
                            <Badge :variant="plan.is_active ? 'active' : 'suspended'" />
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="font-mono bg-zinc-100 px-2 py-0.5 rounded text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                slug: {{ plan.slug }}
                            </span>
                            <span>•</span>
                            <span>Sort Priority: #{{ plan.sort_order }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="flex items-center gap-3 self-start sm:self-auto">
                    <Button
                        :href="`/admin/subscriptions/plans/${plan.id}/edit`"
                        variant="primary"
                        size="sm"
                    >
                        <template #prefix>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </template>
                        <span>Edit Plan</span>
                    </Button>

                    <Button
                        href="/admin/subscriptions/plans"
                        variant="outline"
                        size="sm"
                    >
                        Back to Plans
                    </Button>
                </div>
            </div>

            <!-- Quick Metrics Strip -->
            <div class="mt-6 grid grid-cols-2 gap-4 border-t border-zinc-100 pt-6 sm:grid-cols-4 dark:border-zinc-800">
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Tier Price</p>
                    <p class="text-base font-bold text-zinc-900 dark:text-white">
                        {{ formatCurrency(plan.price, plan.currency) }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Billing Cycle</p>
                    <p class="text-sm font-semibold capitalize text-zinc-900 dark:text-white">{{ plan.billing_cycle }}</p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Trial Period</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                        {{ plan.trial_days > 0 ? `${plan.trial_days} days` : 'None' }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Active Features</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ plan.features?.length ?? 0 }} enabled</p>
                </div>
            </div>
        </div>

        <!-- Main Details Section -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left 2 Cols: Feature Entitlements Breakdown -->
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                    <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Module Feature Entitlements
                            </h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                Scalable module capabilities and permissions granted to this tier.
                            </p>
                        </div>
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ plan.features?.length ?? 0 }} Included
                        </span>
                    </div>

                    <!-- Module Groups -->
                    <div class="mt-6 space-y-6">
                        <div
                            v-for="group in groupedModules"
                            :key="group.moduleName"
                            class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40"
                        >
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-zinc-200/60 dark:border-zinc-800">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-zinc-900 dark:bg-white" />
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-800 dark:text-zinc-200">
                                        {{ group.moduleName }} Module
                                    </h3>
                                </div>
                                <span class="text-[11px] text-zinc-400">
                                    {{ group.features.filter(f => planFeatureIds.has(f.id)).length }} of {{ group.features.length }} active
                                </span>
                            </div>

                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                <div
                                    v-for="feat in group.features"
                                    :key="feat.id"
                                    :class="[
                                        'flex items-start gap-3 rounded-lg p-2.5 transition-colors',
                                        planFeatureIds.has(feat.id)
                                            ? 'bg-white border border-zinc-200/90 shadow-2xs dark:bg-zinc-900 dark:border-zinc-700/80'
                                            : 'opacity-40 bg-zinc-100/50 dark:bg-zinc-900/40',
                                    ]"
                                >
                                    <!-- Enabled Check or Cross -->
                                    <div
                                        :class="[
                                            'flex h-5 w-5 shrink-0 items-center justify-center rounded-md text-xs font-bold mt-0.5',
                                            planFeatureIds.has(feat.id)
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                                : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500',
                                        ]"
                                    >
                                        <svg v-if="planFeatureIds.has(feat.id)" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>

                                    <div class="overflow-hidden">
                                        <p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">
                                            {{ feat.name }}
                                        </p>
                                        <p v-if="feat.description" class="text-[11px] text-zinc-500 dark:text-zinc-400 line-clamp-1">
                                            {{ feat.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Specifications & Identifiers -->
            <div class="space-y-6">
                <!-- Plan Description & Overview Card -->
                <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                        Plan Description
                    </h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed">
                        {{ plan.description || 'No description provided for this plan.' }}
                    </p>

                    <div class="mt-6 space-y-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Public ULID</p>
                            <div class="mt-1 flex items-center justify-between rounded-lg bg-zinc-50 p-2.5 font-mono text-xs text-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">
                                <span>{{ plan.public_id }}</span>
                                <button
                                    type="button"
                                    class="text-zinc-400 hover:text-zinc-700 dark:hover:text-white"
                                    @click="copyToClipboard(plan.public_id, 'ulid')"
                                >
                                    <svg v-if="copiedField === 'ulid'" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Database ID</p>
                            <p class="mt-1 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">#{{ plan.id }}</p>
                        </div>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Created At</p>
                            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ formatDate(plan.created_at) }}</p>
                        </div>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Last Modified</p>
                            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ formatDate(plan.updated_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
