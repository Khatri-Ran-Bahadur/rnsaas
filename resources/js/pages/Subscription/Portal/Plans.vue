<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';

interface Feature {
    id: number;
    name: string;
    description: string | null;
    module: string;
}

interface Plan {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string | number;
    currency: string;
    billing_cycle: string;
    included_branches: number;
    extra_branch_price: string | number;
    features?: Feature[];
}

interface CurrentSubscription {
    id: number;
    plan_id: number;
    status: string;
    allowed_branches: number;
    plan?: Plan;
}

const props = defineProps<{
    plans: Plan[];
    currentSubscription: CurrentSubscription | null;
    pendingTransfer: any | null;
    bankSettings: {
        enabled: boolean;
        instructions: string;
    };
    selectedPlanSlug?: string;
}>();

// Billing cycle filter
const billingCycle = ref<'all' | 'monthly' | 'yearly'>('all');

// Active selected plan for checkout
const initialPlan = props.plans.find((p) => p.slug === props.selectedPlanSlug) || props.plans[0] || null;
const selectedPlan = ref<Plan | null>(initialPlan);

// Branch quantity
const branchCount = ref<number>(initialPlan ? Math.max(1, initialPlan.included_branches || 1) : 1);

const selectPlan = (plan: Plan) => {
    selectedPlan.value = plan;
    branchCount.value = Math.max(branchCount.value, plan.included_branches || 1);
};

const incrementBranches = () => {
    branchCount.value++;
};

const decrementBranches = () => {
    const min = selectedPlan.value ? Math.max(1, selectedPlan.value.included_branches || 1) : 1;
    if (branchCount.value > min) {
        branchCount.value--;
    }
};

const filteredPlans = computed(() => {
    if (billingCycle.value === 'all') return props.plans;
    return props.plans.filter((p) => p.billing_cycle === billingCycle.value);
});

// Expand/collapse modules per plan card
const expandedPlans = ref<Record<number, boolean>>({});

const togglePlanModules = (planId: number) => {
    expandedPlans.value[planId] = !expandedPlans.value[planId];
};

// Price calculation
const calculation = computed(() => {
    if (!selectedPlan.value) {
        return {
            basePrice: 0,
            includedBranches: 1,
            extraBranches: 0,
            extraBranchPrice: 15,
            extraTotal: 0,
            totalPrice: 0,
            currency: 'USD',
        };
    }

    const base = Number(selectedPlan.value.price || 0);
    const included = Math.max(1, selectedPlan.value.included_branches || 1);
    const count = Math.max(included, branchCount.value);
    const extra = count - included;
    const unitExtra = Number(selectedPlan.value.extra_branch_price || 15);
    const extraTotal = extra * unitExtra;
    const total = base + extraTotal;

    return {
        basePrice: base,
        includedBranches: included,
        extraBranches: extra,
        extraBranchPrice: unitExtra,
        extraTotal: extraTotal,
        totalPrice: total,
        currency: selectedPlan.value.currency || 'USD',
    };
});

// Form submission
const form = useForm({
    plan_id: selectedPlan.value?.id || 0,
    branches_count: branchCount.value,
    transaction_reference: '',
    receipt: null as File | null,
    notes: '',
});

const handleReceiptChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.receipt = target.files[0];
    }
};

const submitSubscription = () => {
    if (!selectedPlan.value) return;
    form.plan_id = selectedPlan.value.id;
    form.branches_count = branchCount.value;

    form.post('/admin/subscription/bank-transfer', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Subscription Plans - Select & Upgrade" />

    <OrganizationLayout>
        <div class="space-y-8 pb-16 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/60 px-3 py-1 text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                    Transparent & Flexible Pricing
                </span>
                <h1 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Choose the Right Plan for Your Business
                </h1>
                <p class="mt-2 text-sm sm:text-base text-slate-500 dark:text-zinc-400">
                    Scale with confidence. Include extra branches on any plan with instant transparent calculation.
                </p>

                <!-- Billing Cycle Switcher -->
                <div class="mt-6 inline-flex items-center rounded-xl bg-slate-100 dark:bg-zinc-800 p-1">
                    <button
                        type="button"
                        @click="billingCycle = 'all'"
                        :class="[
                            'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all',
                            billingCycle === 'all'
                                ? 'bg-white dark:bg-zinc-900 text-slate-900 dark:text-white shadow-xs'
                                : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white',
                        ]"
                    >
                        All Plans
                    </button>
                    <button
                        type="button"
                        @click="billingCycle = 'monthly'"
                        :class="[
                            'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all',
                            billingCycle === 'monthly'
                                ? 'bg-white dark:bg-zinc-900 text-slate-900 dark:text-white shadow-xs'
                                : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white',
                        ]"
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        @click="billingCycle = 'yearly'"
                        :class="[
                            'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all',
                            billingCycle === 'yearly'
                                ? 'bg-white dark:bg-zinc-900 text-slate-900 dark:text-white shadow-xs'
                                : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white',
                        ]"
                    >
                        Yearly
                    </button>
                </div>
            </div>

            <!-- Plan Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="plan in filteredPlans"
                    :key="plan.id"
                    @click="selectPlan(plan)"
                    :class="[
                        'rounded-3xl p-6 transition-all duration-200 cursor-pointer relative flex flex-col justify-between border-2',
                        selectedPlan?.id === plan.id
                            ? 'border-indigo-600 bg-white dark:bg-zinc-900 shadow-xl ring-4 ring-indigo-600/10 dark:ring-indigo-500/20'
                            : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 hover:border-slate-300 dark:hover:border-zinc-700 shadow-xs',
                    ]"
                >
                    <!-- Popular / Selected Badge -->
                    <div v-if="selectedPlan?.id === plan.id" class="absolute -top-3.5 right-6">
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white shadow-xs">
                            Selected
                        </span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                                {{ plan.name }}
                            </h3>
                            <span class="text-xs font-medium text-slate-400 dark:text-zinc-500 capitalize">
                                {{ plan.billing_cycle }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-zinc-400 leading-relaxed min-h-[36px]">
                            {{ plan.description || 'Full features for your business operations.' }}
                        </p>

                        <!-- Price Tag -->
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-xs font-semibold text-slate-400 dark:text-zinc-500">{{ plan.currency }}</span>
                            <span class="text-4xl font-black text-slate-900 dark:text-white">
                                {{ Number(plan.price).toFixed(0) }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-zinc-400">/ {{ plan.billing_cycle }}</span>
                        </div>

                        <!-- Branch Info Box -->
                        <div class="mt-4 rounded-xl bg-slate-50 dark:bg-zinc-950 p-3 border border-slate-100 dark:border-zinc-800/80">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600 dark:text-zinc-400 font-medium">Included Branches:</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ plan.included_branches || 1 }} Branch</span>
                            </div>
                            <div class="flex items-center justify-between text-xs mt-1">
                                <span class="text-slate-600 dark:text-zinc-400 font-medium">Extra Branch Price:</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ plan.currency }} {{ Number(plan.extra_branch_price || 15).toFixed(2) }} / mo
                                </span>
                            </div>
                        </div>

                        <!-- Features List -->
                        <div class="mt-6 border-t border-slate-100 dark:border-zinc-800 pt-5">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500 mb-3">
                                Features Included
                            </h4>
                            <ul class="space-y-2.5">
                                <li
                                    v-for="feat in (expandedPlans[plan.id] ? (plan.features || []) : (plan.features || []).slice(0, 8))"
                                    :key="feat.id"
                                    class="flex items-start gap-2 text-xs text-slate-700 dark:text-zinc-300"
                                >
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ feat.name }}</span>
                                </li>
                                <li
                                    v-if="(plan.features?.length || 0) > 8"
                                    class="pt-1.5"
                                >
                                    <button
                                        type="button"
                                        @click.stop="togglePlanModules(plan.id)"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 transition cursor-pointer"
                                    >
                                        <span v-if="!expandedPlans[plan.id]">+ {{ (plan.features?.length || 0) - 8 }} more modules (click to open all) &darr;</span>
                                        <span v-else>Show less modules &uarr;</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-8 pt-4">
                        <button
                            type="button"
                            :class="[
                                'w-full rounded-xl py-2.5 text-xs font-semibold transition-all shadow-xs',
                                selectedPlan?.id === plan.id
                                    ? 'bg-indigo-600 text-white hover:bg-indigo-500'
                                    : 'bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700',
                            ]"
                        >
                            {{ selectedPlan?.id === plan.id ? 'Selected' : 'Choose ' + plan.name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Branch Customizer & Checkout Section -->
            <div v-if="selectedPlan" class="mt-12 rounded-3xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 sm:p-8 shadow-md">
                <div class="border-b border-slate-100 dark:border-zinc-800 pb-6">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        Step 2: Customize Capacity & Payment
                    </span>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                        Configure {{ selectedPlan.name }} for Your Organization
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">
                        Select how many branches you operate. Extra branches are added automatically into your total.
                    </p>
                </div>

                <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Branch Counter & Price Calculator -->
                    <div class="lg:col-span-6 space-y-6">
                        <div class="rounded-2xl bg-slate-50 dark:bg-zinc-950 p-6 border border-slate-200 dark:border-zinc-800">
                            <label class="block text-sm font-bold text-slate-900 dark:text-white">
                                Total Branches Needed
                            </label>
                            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                                Base plan includes {{ calculation.includedBranches }} branch. Single branch extra price is {{ calculation.currency }} {{ calculation.extraBranchPrice.toFixed(2) }}.
                            </p>

                            <div class="mt-4 flex items-center gap-4">
                                <button
                                    type="button"
                                    @click="decrementBranches"
                                    :disabled="branchCount <= calculation.includedBranches"
                                    class="h-12 w-12 rounded-xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-200 flex items-center justify-center text-xl font-bold hover:bg-slate-50 dark:hover:bg-zinc-800 disabled:opacity-30 disabled:cursor-not-allowed shadow-xs transition-colors"
                                >
                                    −
                                </button>
                                <div class="flex-1 text-center">
                                    <span class="text-3xl font-extrabold text-slate-900 dark:text-white">
                                        {{ branchCount }}
                                    </span>
                                    <span class="block text-xs font-semibold text-slate-400 dark:text-zinc-500 mt-0.5">
                                        {{ branchCount === 1 ? 'Branch' : 'Branches' }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="incrementBranches"
                                    class="h-12 w-12 rounded-xl bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 text-slate-700 dark:text-zinc-200 flex items-center justify-center text-xl font-bold hover:bg-slate-50 dark:hover:bg-zinc-800 shadow-xs transition-colors"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Real-Time Calculation Breakdown -->
                            <div class="mt-6 pt-5 border-t border-slate-200 dark:border-zinc-800/80 space-y-2 text-xs">
                                <div class="flex justify-between text-slate-600 dark:text-zinc-400">
                                    <span>{{ selectedPlan.name }} Base Plan (incl. {{ calculation.includedBranches }} branch)</span>
                                    <span class="font-medium text-slate-900 dark:text-white">
                                        {{ calculation.currency }} {{ calculation.basePrice.toFixed(2) }}
                                    </span>
                                </div>
                                <div v-if="calculation.extraBranches > 0" class="flex justify-between text-slate-600 dark:text-zinc-400">
                                    <span>
                                        {{ calculation.extraBranches }} Extra Branch(es) @ {{ calculation.currency }} {{ calculation.extraBranchPrice.toFixed(2) }}
                                    </span>
                                    <span class="font-medium text-slate-900 dark:text-white">
                                        + {{ calculation.currency }} {{ calculation.extraTotal.toFixed(2) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-slate-600 dark:text-zinc-400">
                                    <span>Discount / Coupon</span>
                                    <span class="font-medium text-slate-900 dark:text-white">0.00</span>
                                </div>
                                <div class="pt-3 border-t border-slate-200 dark:border-zinc-800 flex justify-between items-baseline">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">Total Amount Due</span>
                                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                                        {{ calculation.currency }} {{ calculation.totalPrice.toFixed(2) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Transfer Details Card -->
                        <div class="rounded-2xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/40 dark:bg-indigo-950/20 p-5">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                </svg>
                                <h4 class="text-sm font-bold text-indigo-950 dark:text-indigo-200">
                                    SuperAdmin Bank Payment Details
                                </h4>
                            </div>
                            <pre class="mt-3 text-xs text-indigo-950/90 dark:text-indigo-300 font-mono whitespace-pre-wrap leading-relaxed bg-white/70 dark:bg-zinc-900/70 p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-900/40">{{ bankSettings.instructions }}</pre>
                            <p class="mt-2 text-[11px] text-indigo-800/80 dark:text-indigo-400">
                                * Please make the transfer to the above account and submit your deposit slip/reference below.
                            </p>
                        </div>
                    </div>

                    <!-- Payment Proof Submission Form -->
                    <div class="lg:col-span-6">
                        <form @submit.prevent="submitSubscription" class="space-y-4">
                            <!-- Payment Method Selector -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                    Select Payment Method
                                </label>
                                <div class="mt-2 grid grid-cols-2 gap-3">
                                    <div class="flex items-center gap-3 rounded-xl border-2 border-indigo-600 bg-indigo-50/20 dark:bg-indigo-950/30 p-3 cursor-pointer">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-slate-900 dark:text-white block">Bank Transfer</span>
                                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">Enabled</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-900/40 p-3 opacity-60 cursor-not-allowed">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200 dark:bg-zinc-800 text-slate-500">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-slate-600 dark:text-zinc-400 block">PayPal / Cards</span>
                                            <span class="text-[10px] text-slate-400">Coming Soon</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transaction Reference ID -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                    Bank Transaction / Reference Number *
                                </label>
                                <input
                                    v-model="form.transaction_reference"
                                    type="text"
                                    required
                                    placeholder="e.g. REF-20260913-9824"
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white focus:outline-hidden"
                                />
                                <span v-if="form.errors.transaction_reference" class="text-xs text-rose-600 mt-1 block">
                                    {{ form.errors.transaction_reference }}
                                </span>
                            </div>

                            <!-- Receipt File Upload -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                    Upload Deposit Slip / Receipt (Image or PDF) *
                                </label>
                                <div class="mt-1.5 rounded-xl border-2 border-dashed border-slate-200 dark:border-zinc-800 p-4 text-center hover:border-indigo-500 transition-colors">
                                    <input
                                        type="file"
                                        id="receipt-upload"
                                        accept="image/*,application/pdf"
                                        @change="handleReceiptChange"
                                        required
                                        class="hidden"
                                    />
                                    <label for="receipt-upload" class="cursor-pointer">
                                        <svg class="mx-auto h-8 w-8 text-slate-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="mt-1 text-xs font-medium text-slate-700 dark:text-zinc-300">
                                            {{ form.receipt ? form.receipt.name : 'Click to select payment receipt' }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">PNG, JPG, PDF up to 10MB</p>
                                    </label>
                                </div>
                                <span v-if="form.errors.receipt" class="text-xs text-rose-600 mt-1 block">
                                    {{ form.errors.receipt }}
                                </span>
                            </div>

                            <!-- Additional Notes -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-zinc-300">
                                    Optional Notes / Depositor Name
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    placeholder="e.g. Paid by Acme Corp account..."
                                    class="mt-1.5 w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 px-3.5 py-2 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white focus:outline-hidden"
                                />
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full rounded-xl bg-indigo-600 py-3.5 text-sm font-bold text-white shadow-md hover:bg-indigo-500 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                                >
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    <span>
                                        {{ form.processing ? 'Submitting Receipt...' : `Submit Payment (${calculation.currency} ${calculation.totalPrice.toFixed(2)})` }}
                                    </span>
                                </button>
                                <p class="text-center text-[11px] text-slate-400 mt-2">
                                    SuperAdmin will review your transfer and activate all modules immediately upon approval.
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </OrganizationLayout>
</template>
