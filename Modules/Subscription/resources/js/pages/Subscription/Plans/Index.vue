<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Button from '@/components/Button.vue';
import EmptyState from '@/components/EmptyState.vue';

interface Feature {
    id: number;
    public_id?: string;
    name: string;
    slug: string;
    description?: string | null;
    module: string;
    is_active?: boolean;
    sort_order?: number;
    pivot?: {
        enabled?: boolean;
        limits?: Record<string, any> | null;
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
    features_count?: number;
    features?: Feature[];
}

interface PaginatedPlans {
    data: Plan[];
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
}

interface Props {
    plans?: Plan[] | PaginatedPlans;
    all_features?: Feature[];
}

const props = defineProps<Props>();

// Handles both plain Eloquent collection (Plan[]) and Paginator ({ data: Plan[] })
const planList = computed<Plan[]>(() => {
    if (!props.plans) return [];
    if (Array.isArray(props.plans)) return props.plans;
    if (Array.isArray((props.plans as any).data)) return (props.plans as any).data;
    return [];
});

const featureList = computed<Feature[]>(() => {
    return props.all_features ?? [];
});

// Dynamic hierarchical module grouping
interface ModuleGroup {
    moduleKey: string;
    moduleName: string;
    coreFeature?: Feature;
    features: Feature[];
}

const moduleGroups = computed<ModuleGroup[]>(() => {
    const groups: Record<string, ModuleGroup> = {};

    for (const feat of featureList.value) {
        const mod = (feat.module || 'general').toLowerCase();
        if (!groups[mod]) {
            groups[mod] = {
                moduleKey: mod,
                moduleName: mod.charAt(0).toUpperCase() + mod.slice(1),
                features: [],
            };
        }

        // If it is the module itself (e.g. slug === 'accounting' and module === 'accounting')
        if (feat.slug === mod) {
            groups[mod].coreFeature = feat;
        } else {
            groups[mod].features.push(feat);
        }
    }

    // If a module only has a single core feature and no sub-features, include it
    for (const group of Object.values(groups)) {
        if (group.features.length === 0 && group.coreFeature) {
            group.features.push(group.coreFeature);
        }
    }

    return Object.values(groups);
});

// Check whether a plan enables a specific feature
const isFeatureEnabled = (plan: Plan, feature: Feature): boolean => {
    if (!plan.features || plan.features.length === 0) return false;
    const match = plan.features.find((f) => f.slug === feature.slug || f.id === feature.id);
    if (!match) return false;
    if (match.pivot && match.pivot.enabled === false) return false;
    return true;
};

// Count enabled sub-features for a specific module on a plan
const getModuleEnabledCount = (plan: Plan, moduleFeatures: Feature[]): number => {
    return moduleFeatures.filter((f) => isFeatureEnabled(plan, f)).length;
};

// Total enabled features count for a plan
const getTotalEnabledCount = (plan: Plan): number => {
    return featureList.value.filter((f) => isFeatureEnabled(plan, f)).length;
};

// Formatted Price from database
const formatPrice = (plan: Plan) => {
    const rawPrice = Number(plan.price);
    if (rawPrice === 0) return 'Free';
    return `${plan.currency || 'USD'} ${rawPrice.toFixed(0)}`;
};
</script>

<template>
    <SuperAdminLayout
        title="Subscription Settings"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Subscriptions' },
            { label: 'Plans & Settings' },
        ]"
    >
        <!-- Top Title & Controls Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Subscription Setting
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Compare tier entitlements, manage pricing, and configure granular module permissions.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    href="/superadmin/subscriptions/plans/create"
                    variant="primary"
                    size="sm"
                >
                    <template #prefix>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </template>
                    <span>Create Plan</span>
                </Button>
            </div>
        </div>

        <!-- Subscription Comparison Matrix Area -->
        <div v-if="planList.length > 0" class="overflow-x-auto pb-10">
            <!-- Compact Matrix Grid with Fixed Column Widths -->
            <div class="inline-flex flex-col gap-5 min-w-max">
                <!-- ================= TOP ROW: PLAN CARDS ================= -->
                <div class="flex items-stretch gap-5">
                    <!-- Column 0: Left Features Header Card -->
                    <div class="w-64 shrink-0 flex items-center justify-center rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/40">
                        <span class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            Features
                        </span>
                    </div>

                    <!-- Plan Overview Cards -->
                    <div
                        v-for="(plan, idx) in planList"
                        :key="plan.id"
                        :class="[
                            'relative w-64 shrink-0 flex flex-col justify-between rounded-2xl border bg-white p-6 shadow-xs transition-all dark:bg-zinc-900',
                            idx === 1 || plan.sort_order === 2
                                ? 'border-zinc-900 ring-2 ring-zinc-900/10 dark:border-zinc-200 dark:ring-white/10'
                                : 'border-zinc-200/80 dark:border-zinc-800/80',
                        ]"
                    >
                        <!-- Top Popular Ribbon Pill -->
                        <div v-if="idx === 1 || plan.sort_order === 2" class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-900 px-3 py-0.5 text-[10px] font-bold tracking-wide text-white shadow-xs dark:bg-white dark:text-zinc-900">
                                ★ Most Popular
                            </span>
                        </div>

                        <!-- Active Status Badge on Top Right -->
                        <div v-if="plan.is_active" class="absolute top-3 right-3">
                            <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                Active
                            </span>
                        </div>

                        <!-- Plan Title & Subtitle -->
                        <div class="text-center pt-2">
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">
                                {{ plan.name }}
                            </h2>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 min-h-[32px]">
                                {{ plan.description || 'Configured subscription tier for customer accounts.' }}
                            </p>
                        </div>

                        <!-- Price Section (From Database) -->
                        <div class="my-4 text-center">
                            <div class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white">
                                {{ formatPrice(plan) }}
                                <span v-if="Number(plan.price) > 0" class="text-xs font-medium text-zinc-400">
                                    /{{ plan.billing_cycle === 'monthly' ? 'mo' : (plan.billing_cycle === 'yearly' ? 'yr' : plan.billing_cycle) }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs font-medium text-zinc-500 dark:text-zinc-400 capitalize">
                                {{ Number(plan.price) === 0 ? 'Forever Free' : `Billed ${plan.billing_cycle}` }}
                            </p>
                        </div>

                        <!-- Real Database Plan Attributes -->
                        <div class="space-y-2 border-t border-zinc-100 pt-4 text-xs dark:border-zinc-800">
                            <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-zinc-900 dark:bg-white" />
                                <span>{{ getTotalEnabledCount(plan) }} Features Enabled</span>
                            </div>
                            <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-zinc-900 dark:bg-white" />
                                <span class="font-mono text-[11px] text-zinc-500">slug: {{ plan.slug }}</span>
                            </div>
                            <div class="flex items-center gap-2 font-medium" :class="plan.trial_days > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-500'">
                                <span class="h-1.5 w-1.5 rounded-full" :class="plan.trial_days > 0 ? 'bg-emerald-500' : 'bg-zinc-400'" />
                                <span>{{ plan.trial_days > 0 ? `${plan.trial_days} days trial` : 'No free trial' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= BOTTOM ROW: HIERARCHICAL MODULE & SUB-FEATURE MATRIX ================= -->
                <div class="flex items-stretch gap-5">
                    <!-- Bottom Card 0: Module & Sub-Features Tree List -->
                    <div class="w-64 shrink-0 rounded-2xl border border-zinc-200/80 bg-white shadow-xs overflow-hidden dark:border-zinc-800/80 dark:bg-zinc-900">
                        <div class="border-b border-zinc-100 p-4 text-center dark:border-zinc-800">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-400">
                                Modules & Sub-Features
                            </span>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <div
                                v-for="group in moduleGroups"
                                :key="group.moduleKey"
                            >
                                <!-- Module Header Row -->
                                <div class="flex h-10 items-center gap-2 bg-zinc-50/90 px-4 font-bold text-xs uppercase tracking-wider text-zinc-900 dark:bg-zinc-950/70 dark:text-white border-y border-zinc-100/80 dark:border-zinc-800/60">
                                    <span class="h-2 w-2 rounded-full bg-zinc-900 dark:bg-white" />
                                    <span>{{ group.moduleName }}</span>
                                </div>

                                <!-- Sub-Feature Indented Rows -->
                                <div class="divide-y divide-zinc-100/60 dark:divide-zinc-800/50">
                                    <div
                                        v-for="feat in group.features"
                                        :key="feat.slug"
                                        class="flex h-10 items-center pl-7 pr-4 text-xs font-medium text-zinc-700 dark:text-zinc-300"
                                    >
                                        <span class="text-zinc-400 mr-2">↳</span>
                                        <span class="truncate">{{ feat.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Cards 1..N: Plan Feature Check Cards -->
                    <div
                        v-for="plan in planList"
                        :key="plan.id"
                        class="w-64 shrink-0 flex flex-col justify-between rounded-2xl border border-zinc-200/80 bg-white shadow-xs overflow-hidden dark:border-zinc-800/80 dark:bg-zinc-900"
                    >
                        <div>
                            <!-- Header with Total Enabled count -->
                            <div class="border-b border-zinc-100 p-4 text-center dark:border-zinc-800">
                                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                    {{ getTotalEnabledCount(plan) }}/{{ featureList.length }} Total Enabled
                                </span>
                            </div>

                            <!-- Grouped Module Rows and Sub-feature Checks -->
                            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                <div
                                    v-for="group in moduleGroups"
                                    :key="group.moduleKey"
                                >
                                    <!-- Module Summary Count Header Row -->
                                    <div class="flex h-10 items-center justify-center bg-zinc-50/90 font-bold text-[11px] text-zinc-600 dark:bg-zinc-950/70 dark:text-zinc-400 border-y border-zinc-100/80 dark:border-zinc-800/60">
                                        {{ getModuleEnabledCount(plan, group.features) }}/{{ group.features.length }} Enabled
                                    </div>

                                    <!-- Sub-Feature Checkmark Rows -->
                                    <div class="divide-y divide-zinc-100/60 dark:divide-zinc-800/50">
                                        <div
                                            v-for="feat in group.features"
                                            :key="feat.slug"
                                            class="flex h-10 items-center justify-center"
                                        >
                                            <!-- Enabled Check Circle -->
                                            <div
                                                v-if="isFeatureEnabled(plan, feat)"
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100/80 text-emerald-600 dark:bg-emerald-950/80 dark:text-emerald-400 shadow-2xs"
                                                :title="`${feat.name} is enabled in ${plan.name}`"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>

                                            <!-- Disabled Cross Circle -->
                                            <div
                                                v-else
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500"
                                                :title="`${feat.name} is disabled in ${plan.name}`"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Plan Action Button -->
                        <div class="p-4 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-950/20">
                            <Button
                                :href="`/superadmin/subscriptions/plans/${plan.id}/edit`"
                                variant="primary"
                                size="sm"
                                class="w-full justify-center"
                            >
                                Edit Plan
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="mt-8 rounded-2xl border border-zinc-200/80 bg-white p-8 shadow-xs dark:border-zinc-800/80 dark:bg-zinc-900">
            <EmptyState
                title="No subscription plans configured"
                description="Get started by creating your first subscription tier with module feature entitlements."
            >
                <template #actions>
                    <Button
                        href="/superadmin/subscriptions/plans/create"
                        variant="primary"
                        size="sm"
                    >
                        Create First Plan
                    </Button>
                </template>
            </EmptyState>
        </div>
    </SuperAdminLayout>
</template>
