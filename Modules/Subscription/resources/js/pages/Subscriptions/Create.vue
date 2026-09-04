<script setup lang="ts">
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';

export interface TenantOption {
    id: number;
    public_id: string;
    name: string;
}

export interface PlanOption {
    id: number;
    public_id: string;
    name: string;
    price: string | number;
    currency: string;
    billing_cycle: string;
    trial_days: number;
}

const props = defineProps<{
    tenants: TenantOption[];
    plans: PlanOption[];
}>();

const form = useForm({
    tenant_id: props.tenants[0]?.id ?? '',
    plan_id: props.plans[0]?.id ?? '',
    starts_at: null as string | null,
});

const tenantOptions = computed(() => {
    return props.tenants.map((t) => ({
        label: `${t.name} (${t.public_id})`,
        value: t.id,
    }));
});

const planOptions = computed(() => {
    return props.plans.map((p) => ({
        label: `${p.name} — ${p.currency} ${p.price} / ${p.billing_cycle}`,
        value: p.id,
    }));
});

const submit = () => {
    form.post('/superadmin/subscriptions');
};
</script>

<template>
    <AdminLayout
        title="Create Subscription"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/superadmin/dashboard' },
            { label: 'Subscriptions', href: '/superadmin/subscriptions' },
            { label: 'New Subscription' },
        ]"
    >
        <!-- Page Header with Back Button (matching Create Organization) -->
        <div class="pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center gap-3">
                <Link
                    href="/superadmin/subscriptions"
                    class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Create Subscription
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Assign a new subscription plan to an active organization.
                    </p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-6 max-w-4xl">
            <!-- Card 1: General Information -->
            <div class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5 pb-4 border-b border-zinc-100 dark:border-zinc-800/80">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                        General Information
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Core organization and plan identifiers for this subscription.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Organization / Tenant Select -->
                    <Select
                        v-model="form.tenant_id"
                        label="Organization / Tenant"
                        :options="tenantOptions"
                        placeholder="Select an active organization"
                        :required="true"
                        :error="form.errors.tenant_id"
                    />

                    <!-- Subscription Plan Select -->
                    <Select
                        v-model="form.plan_id"
                        label="Subscription Plan"
                        :options="planOptions"
                        placeholder="Select a subscription plan"
                        :required="true"
                        :error="form.errors.plan_id"
                    />
                </div>
            </div>

            <!-- Card 2: Schedule & Activation -->
            <div class="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-2xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5 pb-4 border-b border-zinc-100 dark:border-zinc-800/80">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                        Schedule & Activation
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure start date and period parameters.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Start Date DatePicker -->
                    <DatePicker
                        v-model="form.starts_at"
                        label="Start Date (Optional)"
                        placeholder="Select start date..."
                        hint="Leave blank to start subscription immediately."
                        :error="form.errors.starts_at"
                    />
                </div>
            </div>

            <!-- Action Buttons at bottom right (matching Create Organization) -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link href="/superadmin/subscriptions">
                    <Button type="button" variant="outline">
                        <span>Cancel</span>
                    </Button>
                </Link>

                <Button type="submit" variant="primary" :disabled="form.processing">
                    <span>Create Subscription</span>
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>
