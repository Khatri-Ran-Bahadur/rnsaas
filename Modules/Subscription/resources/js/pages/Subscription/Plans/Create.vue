<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Card from '@/components/Card.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import Switch from '@/components/Switch.vue';
import Combobox from '@/components/Combobox.vue';
import { CURRENCY_OPTIONS } from '@/constants/referenceData';

interface Feature {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    module: string;
    is_active: boolean;
    sort_order: number;
}

const props = defineProps<{
    features?: Feature[];
}>();

const availableFeatures = computed<Feature[]>(() => {
    return props.features ?? [];
});

const form = useForm({
    name: '',
    slug: '',
    description: '',
    price: '29.00',
    currency: 'USD',
    billing_cycle: 'monthly',
    trial_days: 14,
    is_active: true,
    sort_order: 1,
    feature_ids: [] as number[],
});

const isSlugTouched = ref(false);

const slugify = (text: string): string => {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]+/g, '')
        .replace(/--+/g, '-');
};

const handleNameInput = () => {
    if (!isSlugTouched.value) {
        form.slug = slugify(form.name);
    }
};

const handleSlugInput = () => {
    isSlugTouched.value = true;
    form.slug = slugify(form.slug);
};

// Group features dynamically by module
const groupedModules = computed(() => {
    const groups: Record<string, { moduleKey: string; moduleName: string; features: Feature[] }> = {};

    for (const feature of availableFeatures.value) {
        const mod = feature.module || 'general';
        if (!groups[mod]) {
            groups[mod] = {
                moduleKey: mod,
                moduleName: mod.charAt(0).toUpperCase() + mod.slice(1),
                features: [],
            };
        }
        groups[mod].features.push(feature);
    }

    return Object.values(groups);
});

const isFeatureSelected = (featureId: number) => {
    return form.feature_ids.includes(featureId);
};

const toggleFeature = (featureId: number) => {
    const idx = form.feature_ids.indexOf(featureId);
    if (idx > -1) {
        form.feature_ids.splice(idx, 1);
    } else {
        form.feature_ids.push(featureId);
    }
};

const toggleAllModuleFeatures = (features: Feature[]) => {
    const allSelected = features.every(f => form.feature_ids.includes(f.id));
    if (allSelected) {
        // Deselect all
        const idsToRemove = new Set(features.map(f => f.id));
        form.feature_ids = form.feature_ids.filter(id => !idsToRemove.has(id));
    } else {
        // Select all
        const currentSet = new Set(form.feature_ids);
        features.forEach(f => currentSet.add(f.id));
        form.feature_ids = Array.from(currentSet);
    }
};

const billingCycleOptions = [
    { label: 'Monthly (recurring every month)', value: 'monthly' },
    { label: 'Quarterly (recurring every 3 months)', value: 'quarterly' },
    { label: 'Yearly / Annual (billed annually)', value: 'yearly' },
    { label: 'Lifetime / One-time', value: 'lifetime' },
];

const submit = () => {
    form.post('/superadmin/subscriptions/plans');
};
</script>

<template>
    <AdminLayout
        title="Create Subscription Plan"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Subscriptions', href: '/superadmin/subscriptions/plans' },
            { label: 'Plans', href: '/superadmin/subscriptions/plans' },
            { label: 'Create Plan' },
        ]"
    >
        <!-- Page Header -->
        <div class="pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center gap-3">
                <Link
                    href="/superadmin/subscriptions/plans"
                    class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Create Subscription Plan
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Define pricing, billing interval, and module feature access for this subscription tier.
                    </p>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <div class="mt-8 max-w-4xl">
            <form class="space-y-8" @submit.prevent="submit">
                <!-- Section 1: Basic Information -->
                <Card
                    title="Basic Information"
                    description="Plan identifier, naming and descriptive marketing summary."
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <TextInput
                                v-model="form.name"
                                label="Plan Name"
                                placeholder="e.g. Starter, Growth, Enterprise"
                                :error="form.errors.name"
                                required
                                autofocus
                                @input="handleNameInput"
                            />
                        </div>

                        <div>
                            <TextInput
                                v-model="form.slug"
                                label="Plan Slug Identifier"
                                placeholder="starter"
                                prefix-addon="plans/"
                                :error="form.errors.slug"
                                mono
                                required
                                @input="handleSlugInput"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <TextInput
                                v-model="form.description"
                                label="Plan Description"
                                placeholder="Brief summary of who this plan is tailored for..."
                                :error="form.errors.description"
                            />
                        </div>
                    </div>
                </Card>

                <!-- Section 2: Pricing & Billing Cycle -->
                <Card
                    title="Pricing & Billing Model"
                    description="Set the tier price, currency, billing interval, and free trial duration."
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <TextInput
                                v-model="form.price"
                                label="Price"
                                type="number"
                                placeholder="49.00"
                                :error="form.errors.price"
                                required
                            />
                        </div>

                        <div>
                            <Combobox
                                v-model="form.currency"
                                label="Billing Currency"
                                placeholder="Select currency..."
                                :options="CURRENCY_OPTIONS"
                                :error="form.errors.currency"
                                required
                            />
                        </div>

                        <div>
                            <Select
                                v-model="form.billing_cycle"
                                label="Billing Cycle Interval"
                                placeholder="Select billing interval"
                                :options="billingCycleOptions"
                                :error="form.errors.billing_cycle"
                                required
                            />
                        </div>

                        <div>
                            <TextInput
                                v-model="form.trial_days"
                                label="Free Trial Days"
                                type="number"
                                placeholder="14"
                                hint="Set to 0 for no free trial"
                                :error="form.errors.trial_days"
                                required
                            />
                        </div>
                    </div>
                </Card>

                <!-- Section 3: Configuration & Visibility -->
                <Card
                    title="Configuration & Order"
                    description="Control plan visibility and display sort priority."
                >
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <Switch
                                v-model="form.is_active"
                                title="Publish Plan"
                                description="When active, this plan is available for customer subscriptions."
                            />
                        </div>

                        <div>
                            <TextInput
                                v-model="form.sort_order"
                                label="Display Sort Order"
                                type="number"
                                placeholder="1"
                                hint="Lower number appears first (e.g. 1 for Starter, 2 for Pro)"
                                :error="form.errors.sort_order"
                                required
                            />
                        </div>
                    </div>
                </Card>

                <!-- Section 4: Module Feature Entitlements -->
                <Card
                    title="Module Feature Entitlements"
                    description="Select which platform modules and features are unlocked for tenants subscribing to this tier."
                >
                    <div class="space-y-6">
                        <div
                            v-for="group in groupedModules"
                            :key="group.moduleKey"
                            class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-5 dark:border-zinc-800 dark:bg-zinc-950/40"
                        >
                            <!-- Module Header with Select All button -->
                            <div class="flex items-center justify-between mb-4 pb-3 border-b border-zinc-200/60 dark:border-zinc-800">
                                <div class="flex items-center gap-2.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-zinc-900 dark:bg-white" />
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white">
                                        {{ group.moduleName }} Module
                                    </h3>
                                    <span class="rounded bg-zinc-200/70 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ group.features.filter(f => isFeatureSelected(f.id)).length }} / {{ group.features.length }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="text-xs font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white underline cursor-pointer"
                                    @click="toggleAllModuleFeatures(group.features)"
                                >
                                    {{ group.features.every(f => isFeatureSelected(f.id)) ? 'Deselect All' : 'Select All' }}
                                </button>
                            </div>

                            <!-- Feature Grid -->
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label
                                    v-for="feature in group.features"
                                    :key="feature.id"
                                    :class="[
                                        'flex items-start gap-3 rounded-xl border p-3 cursor-pointer transition-all duration-150',
                                        isFeatureSelected(feature.id)
                                            ? 'border-zinc-900 bg-white shadow-xs dark:border-white dark:bg-zinc-900'
                                            : 'border-zinc-200/70 bg-white/60 hover:bg-white dark:border-zinc-800 dark:bg-zinc-900/40 dark:hover:bg-zinc-900',
                                    ]"
                                    @click.prevent="toggleFeature(feature.id)"
                                >
                                    <!-- Custom Checkbox -->
                                    <div
                                        :class="[
                                            'flex h-5 w-5 shrink-0 items-center justify-center rounded-md border text-xs transition-colors mt-0.5',
                                            isFeatureSelected(feature.id)
                                                ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900 font-bold'
                                                : 'border-zinc-300 bg-white dark:border-zinc-700 dark:bg-zinc-800',
                                        ]"
                                    >
                                        <svg v-if="isFeatureSelected(feature.id)" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>

                                    <div class="select-none">
                                        <p class="text-xs font-semibold text-zinc-900 dark:text-white">
                                            {{ feature.name }}
                                        </p>
                                        <p v-if="feature.description" class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ feature.description }}
                                        </p>
                                        <p class="mt-1 font-mono text-[10px] text-zinc-400">
                                            {{ feature.slug }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <Button
                        href="/superadmin/subscriptions/plans"
                        variant="outline"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="form.processing"
                    >
                        Create Subscription Plan
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
