<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

interface PlanFeature {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
}

interface Plan {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    price: number;
    currency: string;
    billing_cycle: string;
    trial_days: number;
    is_popular?: boolean;
    features: PlanFeature[];
}

const props = defineProps<{
    plans?: Plan[];
}>();

// Theme State - Light mode by default with Dark mode toggle
const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
});

// Dropdown state for Modules
const isModulesDropdownOpen = ref(false);
const activeDashboardTab = ref<'accounting' | 'pos' | 'inventory' | 'mrp'>('accounting');

// Billing toggle
const billingCycle = ref<'monthly' | 'annual'>('monthly');

// FAQ Accordion State
const activeFaq = ref<number | null>(0);
const toggleFaq = (index: number) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const displayPlans = computed(() => {
    if (props.plans && props.plans.length > 0) {
        return props.plans;
    }

    return [
        {
            id: 1,
            name: 'Starter',
            slug: 'starter',
            description: 'Essential core operations for small businesses and boutiques.',
            price: 19.00,
            currency: 'USD',
            billing_cycle: 'monthly',
            trial_days: 14,
            is_popular: false,
            features: [
                { id: 1, name: 'Double-Entry Accounting & Ledger', slug: 'accounting' },
                { id: 2, name: 'Invoicing & Customer Billing', slug: 'accounting.invoices' },
                { id: 3, name: 'Multi-Warehouse Inventory Control', slug: 'inventory' },
                { id: 4, name: 'POS Front-Counter Register', slug: 'pos' },
                { id: 5, name: 'Statutory Tax Calculations', slug: 'tax' },
            ],
        },
        {
            id: 2,
            name: 'Business',
            slug: 'business',
            description: 'Advanced capabilities for growing restaurants, retail, and distributors.',
            price: 49.00,
            currency: 'USD',
            billing_cycle: 'monthly',
            trial_days: 14,
            is_popular: true,
            features: [
                { id: 1, name: 'Everything in Starter', slug: 'all_starter' },
                { id: 2, name: 'Kitchen Display System (KDS)', slug: 'pos.kds' },
                { id: 3, name: 'MRP Manufacturing & BOM Recipes', slug: 'mrp' },
                { id: 4, name: 'Automated Employee Payroll & Pay Runs', slug: 'payroll' },
                { id: 5, name: 'Multi-Station Cash Shifts & Audits', slug: 'pos.shifts' },
                { id: 6, name: 'Role-Based Access Control (RBAC)', slug: 'tenancy.rbac' },
            ],
        },
        {
            id: 3,
            name: 'Enterprise',
            slug: 'enterprise',
            description: 'Complete operational mastery for multi-branch organizations.',
            price: 99.00,
            currency: 'USD',
            billing_cycle: 'monthly',
            trial_days: 30,
            is_popular: false,
            features: [
                { id: 1, name: 'Everything in Business', slug: 'all_business' },
                { id: 2, name: 'Unlimited Branches & Locations', slug: 'branches.unlimited' },
                { id: 3, name: 'Quality Inspections & Batch Tracking', slug: 'mrp.qa' },
                { id: 4, name: 'Multi-Currency & Tax Exemptions', slug: 'tax.exemptions' },
                { id: 5, name: 'Automated General Ledger Syncing', slug: 'gl.auto' },
                { id: 6, name: 'Priority 24/7 Dedicated Support', slug: 'support.priority' },
            ],
        },
    ];
});

const calculatePrice = (planPrice: number) => {
    if (billingCycle.value === 'annual') {
        return (planPrice * 0.8).toFixed(2);
    }
    return planPrice.toFixed(2);
};

const platformModules = [
    {
        id: 'accounting',
        name: 'Financial Accounting & Ledger',
        badge: 'Core ERP',
        description: 'Double-entry bookkeeping, multi-currency journal entries, automated reconciliations, and balance sheets.',
        iconColor: 'from-blue-600 to-indigo-600',
        stats: '$1.4M+ Managed',
    },
    {
        id: 'pos',
        name: 'Omnichannel POS & KDS',
        badge: 'Retail & Dining',
        description: 'High-speed touch registers, barcode scanners, kitchen display stations, split bills, and shift closing audits.',
        iconColor: 'from-emerald-500 to-teal-600',
        stats: '< 0.3s Order Flow',
    },
    {
        id: 'inventory',
        name: 'Multi-Warehouse Inventory',
        badge: 'Supply Chain',
        description: 'Real-time stock valuation (FIFO/AVCO), low stock threshold alerts, inter-branch stock transfers, and audits.',
        iconColor: 'from-amber-500 to-orange-600',
        stats: '99.9% Stock Accuracy',
    },
    {
        id: 'mrp',
        name: 'MRP Manufacturing & BOM',
        badge: 'Production',
        description: 'Multi-level Bill of Materials, production work orders, routing stations, scrap tracking, and finished goods.',
        iconColor: 'from-purple-600 to-pink-600',
        stats: 'Zero Waste Routing',
    },
    {
        id: 'payroll',
        name: 'Payroll & Employee HRM',
        badge: 'HR & People',
        description: 'Salary slips, attendance clocking, statutory withholdings (EPF, SOCSO, Tax), and one-click salary disbursements.',
        iconColor: 'from-indigo-600 to-blue-700',
        stats: 'Automated Runs',
    },
    {
        id: 'tax',
        name: 'Statutory Tax Compliance',
        badge: 'Government Ready',
        description: 'Real-time GST, SST, VAT calculation engines, tax exemptions, and e-invoicing export compliance.',
        iconColor: 'from-rose-500 to-red-600',
        stats: '100% Audit Proof',
    },
];

const faqs = [
    {
        q: 'How does SathiSaaS isolate multiple organizations securely?',
        a: 'Every organization is completely partitioned with tenant-level scoping, database row-level security, separate configuration silos, custom SMTP credentials, and granular Role-Based Access Control (RBAC).',
    },
    {
        q: 'Can our physical stores run POS offline if internet disconnects?',
        a: 'Yes! The POS register caches menu items, tax rules, and orders locally in the browser storage. When connectivity is restored, all offline transactions automatically sync with the central cloud ledger.',
    },
    {
        q: 'Does SathiSaaS support double-entry accounting standard compliances?',
        a: 'Absolutely. All journal vouchers adhere strictly to standard debit/credit double-entry accounting rules, automatically reconciling transactions from POS sales, inventory receipts, and vendor bills.',
    },
    {
        q: 'Is there a credit card required to start the 14-day trial?',
        a: 'No credit card is required. You can register your company, invite team members, configure warehouses, and start processing live sales in less than two minutes completely free.',
    },
    {
        q: 'Can we migrate existing data from spreadsheets or older accounting software?',
        a: 'Yes. Our platform provides native CSV/Excel import tools for Chart of Accounts, opening trial balances, customer/vendor contact books, and inventory product catalogs.',
    },
];
</script>

<template>
    <Head title="SathiSaaS - Unified Enterprise ERP & Multi-Tenant Operating System" />

    <div :class="{'dark': isDark}" class="min-h-screen font-sans antialiased transition-colors duration-200 selection:bg-indigo-600 selection:text-white bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        
        <!-- Ambient Atmospheric Lights -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[1000px] h-[500px] bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-blue-500/10 dark:from-indigo-600/15 dark:via-purple-600/10 dark:to-blue-600/10 blur-[130px] rounded-full"></div>
            <div class="absolute top-[45%] -right-40 w-[600px] h-[600px] bg-gradient-to-bl from-teal-500/10 via-indigo-500/5 to-transparent dark:from-indigo-600/10 blur-[140px] rounded-full"></div>
        </div>

        <div class="relative z-10 flex flex-col min-h-screen">

            <!-- Navbar -->
            <header class="sticky top-0 z-50 backdrop-blur-xl border-b transition-colors duration-200 bg-white/80 border-slate-200/80 dark:bg-slate-950/80 dark:border-slate-800/80">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    
                    <!-- Modern Logo -->
                    <a href="/" class="flex items-center gap-3 group">
                        <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 flex items-center justify-center shadow-md shadow-indigo-500/25 ring-1 ring-white/20 transition-transform duration-200 group-hover:scale-105">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                                    Sathi<span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-purple-400">SaaS</span>
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800/60">
                                    ERP
                                </span>
                            </div>
                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Enterprise Cloud OS</span>
                        </div>
                    </a>

                    <!-- Navigation Items: Modules (with modern dropdown), Pricing, FAQs -->
                    <nav class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-semibold">
                        <!-- Modules Dropdown Button -->
                        <div class="relative" @mouseleave="isModulesDropdownOpen = false">
                            <button
                                @click="isModulesDropdownOpen = !isModulesDropdownOpen"
                                @mouseenter="isModulesDropdownOpen = true"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all cursor-pointer',
                                    isModulesDropdownOpen
                                        ? 'bg-slate-100 text-indigo-600 dark:bg-slate-800 dark:text-white'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-900'
                                ]"
                            >
                                <span>Modules</span>
                                <svg
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{'rotate-180 text-indigo-600 dark:text-white': isModulesDropdownOpen}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Modern Collapsing Mega-Dropdown -->
                            <div
                                v-show="isModulesDropdownOpen"
                                class="absolute top-full left-0 mt-2 w-[580px] -translate-x-12 rounded-2xl p-4 shadow-2xl border backdrop-blur-2xl transition-all duration-200 z-50 bg-white/95 border-slate-200/90 dark:bg-slate-900/95 dark:border-slate-800"
                            >
                                <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800/80 mb-2 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Unified Core Engines</span>
                                    <span class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold">All Synchronized in Real-Time</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <a
                                        v-for="mod in platformModules"
                                        :key="mod.id"
                                        :href="`#${mod.id}`"
                                        @click="isModulesDropdownOpen = false"
                                        class="group flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors"
                                    >
                                        <div :class="['w-9 h-9 rounded-xl bg-gradient-to-tr flex items-center justify-center text-white shrink-0 shadow-xs', mod.iconColor]">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                    {{ mod.name }}
                                                </p>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5 leading-relaxed">
                                                {{ mod.description }}
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <a href="#pricing" class="px-3.5 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-900 transition-colors">
                            Pricing
                        </a>
                        <a href="#faqs" class="px-3.5 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100/70 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-900 transition-colors">
                            FAQs
                        </a>
                    </nav>

                    <!-- Right Header Actions: Dark Mode Switcher & Get Started -->
                    <div class="flex items-center space-x-3">
                        <!-- Theme Toggle Button -->
                        <button
                            type="button"
                            @click="toggleTheme"
                            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                            class="p-2.5 rounded-xl border transition-all cursor-pointer bg-slate-100 border-slate-200 text-slate-700 hover:bg-slate-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            <!-- Sun (Light Mode) -->
                            <svg v-if="isDark" class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Moon (Dark Mode) -->
                            <svg v-else class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <!-- Primary CTA: Only Get Started -->
                        <Link
                            href="/register"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg shadow-indigo-500/25 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 active:scale-98 transition-all"
                        >
                            <span>Get Started</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </div>

                </div>
            </header>

            <!-- Hero Section -->
            <section class="relative pt-16 pb-20 md:pt-24 md:pb-28">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    
                    <!-- Pill Tag -->
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold mb-8 backdrop-blur-md border bg-indigo-50/80 border-indigo-200 text-indigo-700 dark:bg-indigo-950/60 dark:border-indigo-800/80 dark:text-indigo-300">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Enterprise Cloud ERP & Multi-Tenant Operating System</span>
                    </div>

                    <!-- Headline -->
                    <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight max-w-5xl mx-auto leading-[1.1] text-slate-900 dark:text-white">
                        Run Your Whole Business on <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent dark:from-indigo-400 dark:via-purple-300 dark:to-pink-400">One Unified Cloud</span>.
                    </h1>

                    <!-- Subtitle -->
                    <p class="mt-6 text-base sm:text-xl max-w-3xl mx-auto leading-relaxed text-slate-600 dark:text-slate-400">
                        Stop juggling fragmented tools. SathiSaaS connects General Ledger Accounting, POS Touch Counters, Inventory Control, MRP Recipes, and Payroll into a single high-speed system.
                    </p>

                    <!-- Hero CTA Buttons -->
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            href="/register"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-base font-bold text-white shadow-xl shadow-indigo-600/30 bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 hover:from-indigo-500 hover:to-purple-600 transition-all transform hover:-translate-y-0.5 active:translate-y-0"
                        >
                            <span>Get Started Free (14-Day Trial)</span>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <a
                            href="#pricing"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-4 rounded-2xl border text-base font-semibold transition-all bg-white border-slate-300 text-slate-700 hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                        >
                            View Pricing Plans
                        </a>
                    </div>

                    <!-- Trust Stats Bar -->
                    <div class="mt-14 pt-10 border-t max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center border-slate-200 dark:border-slate-800/80">
                        <div>
                            <div class="text-3xl font-extrabold font-mono text-slate-900 dark:text-white">6+</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Native Modules</div>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold font-mono text-indigo-600 dark:text-indigo-400">100%</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Tenant Isolated</div>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold font-mono text-slate-900 dark:text-white">&lt; 100ms</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Response Latency</div>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold font-mono text-emerald-600 dark:text-emerald-400">99.99%</div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Uptime SLA</div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ULTRA-MODERN PLATFORM DASHBOARD SHOWCASE ("go to dashboard redesigned") -->
            <section class="py-12 md:py-20 relative">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-10">
                        <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Command Center Built for Speed & Precision
                        </h2>
                        <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-2">
                            A live look into the unified workspace powering your accounting, point-of-sale, and manufacturing.
                        </p>
                    </div>

                    <!-- Interactive Mockup Container with Mac Browser Frame -->
                    <div class="rounded-3xl border shadow-2xl overflow-hidden backdrop-blur-xl transition-colors bg-white/90 border-slate-200/90 dark:bg-slate-900/90 dark:border-slate-800 shadow-indigo-500/5">
                        
                        <!-- Top Chrome Bar -->
                        <div class="px-6 py-4 border-b flex items-center justify-between border-slate-200 dark:border-slate-800 bg-slate-100/70 dark:bg-slate-950/60">
                            <!-- Window Control Dots -->
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                                <span class="ml-4 text-xs font-mono font-medium text-slate-500 dark:text-slate-400 hidden sm:inline">
                                    https://app.sathisaas.com/admin/dashboard
                                </span>
                            </div>

                            <!-- Interactive Dashboard Tabs -->
                            <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-200/80 dark:bg-slate-900 border border-slate-300/50 dark:border-slate-800 text-xs font-semibold">
                                <button
                                    type="button"
                                    @click="activeDashboardTab = 'accounting'"
                                    :class="[
                                        'px-3 py-1 rounded-lg transition-all cursor-pointer',
                                        activeDashboardTab === 'accounting'
                                            ? 'bg-white text-indigo-700 shadow-xs dark:bg-indigo-600 dark:text-white'
                                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                                    ]"
                                >
                                    Accounting
                                </button>
                                <button
                                    type="button"
                                    @click="activeDashboardTab = 'pos'"
                                    :class="[
                                        'px-3 py-1 rounded-lg transition-all cursor-pointer',
                                        activeDashboardTab === 'pos'
                                            ? 'bg-white text-indigo-700 shadow-xs dark:bg-indigo-600 dark:text-white'
                                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                                    ]"
                                >
                                    POS Counter
                                </button>
                                <button
                                    type="button"
                                    @click="activeDashboardTab = 'inventory'"
                                    :class="[
                                        'px-3 py-1 rounded-lg transition-all cursor-pointer',
                                        activeDashboardTab === 'inventory'
                                            ? 'bg-white text-indigo-700 shadow-xs dark:bg-indigo-600 dark:text-white'
                                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                                    ]"
                                >
                                    Inventory
                                </button>
                                <button
                                    type="button"
                                    @click="activeDashboardTab = 'mrp'"
                                    :class="[
                                        'px-3 py-1 rounded-lg transition-all cursor-pointer',
                                        activeDashboardTab === 'mrp'
                                            ? 'bg-white text-indigo-700 shadow-xs dark:bg-indigo-600 dark:text-white'
                                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                                    ]"
                                >
                                    MRP
                                </button>
                            </div>

                            <!-- Live Sync Pill -->
                            <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold dark:bg-emerald-950/60 dark:text-emerald-400 dark:border-emerald-800/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Real-time Sync</span>
                            </div>
                        </div>

                        <!-- Dashboard Canvas Inner -->
                        <div class="p-6 md:p-8 space-y-6">
                            
                            <!-- KPI Metrics Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                
                                <div class="p-5 rounded-2xl border bg-slate-50/70 border-slate-200/80 dark:bg-slate-950/60 dark:border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Revenue</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">+18.4%</span>
                                    </div>
                                    <p class="text-2xl sm:text-3xl font-extrabold font-mono mt-2 text-slate-900 dark:text-white">$148,920.00</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Net sales across all registers</p>
                                </div>

                                <div class="p-5 rounded-2xl border bg-slate-50/70 border-slate-200/80 dark:bg-slate-950/60 dark:border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">POS Tickets</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">Live</span>
                                    </div>
                                    <p class="text-2xl sm:text-3xl font-extrabold font-mono mt-2 text-slate-900 dark:text-white">1,428 Orders</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Average ticket $104.28</p>
                                </div>

                                <div class="p-5 rounded-2xl border bg-slate-50/70 border-slate-200/80 dark:bg-slate-950/60 dark:border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Warehouse Stock</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">FIFO</span>
                                    </div>
                                    <p class="text-2xl sm:text-3xl font-extrabold font-mono mt-2 text-slate-900 dark:text-white">$482,100.00</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">9,420 items across 4 hubs</p>
                                </div>

                                <div class="p-5 rounded-2xl border bg-slate-50/70 border-slate-200/80 dark:bg-slate-950/60 dark:border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Net Profit Margin</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">GAAP</span>
                                    </div>
                                    <p class="text-2xl sm:text-3xl font-extrabold font-mono mt-2 text-slate-900 dark:text-white">32.8%</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Post statutory SST & VAT deductions</p>
                                </div>

                            </div>

                            <!-- Middle Section: Chart Curve + Recent Transactions -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                
                                <!-- Revenue Growth Chart -->
                                <div class="lg:col-span-2 p-6 rounded-2xl border bg-slate-50/70 border-slate-200/80 dark:bg-slate-950/60 dark:border-slate-800 flex flex-col justify-between">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Revenue & Cash Flow Velocity</h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Real-time ledger postings from all POS counters</p>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs font-semibold">
                                            <span class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
                                                <span class="w-2 h-2 rounded-full bg-indigo-600"></span> Sales
                                            </span>
                                            <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Collections
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Clean Vector Sparkline / Graph -->
                                    <div class="h-44 w-full flex items-end pt-4">
                                        <svg class="w-full h-full overflow-visible" viewBox="0 0 500 120" fill="none" preserveAspectRatio="none">
                                            <defs>
                                                <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#6366f1" stop-opacity="0.35" />
                                                    <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0" />
                                                </linearGradient>
                                            </defs>
                                            <path d="M0,100 C60,95 90,60 140,70 C190,80 230,40 280,45 C340,50 380,15 440,20 C470,22 490,5 500,8 L500,120 L0,120 Z" fill="url(#chartGrad)" />
                                            <path d="M0,100 C60,95 90,60 140,70 C190,80 230,40 280,45 C340,50 380,15 440,20 C470,22 490,5 500,8" stroke="#6366f1" stroke-width="3" stroke-linecap="round" />
                                            <circle cx="280" cy="45" r="4" fill="#6366f1" class="animate-pulse" />
                                            <circle cx="500" cy="8" r="4" fill="#6366f1" />
                                        </svg>
                                    </div>

                                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-400 pt-3 border-t border-slate-200 dark:border-slate-800/80">
                                        <span>Mon</span>
                                        <span>Tue</span>
                                        <span>Wed</span>
                                        <span>Thu</span>
                                        <span>Fri</span>
                                        <span>Sat</span>
                                        <span>Sun (Today)</span>
                                    </div>
                                </div>

                                <!-- Recent Live Transactions -->
                                <div class="p-6 rounded-2xl border bg-slate-50/70 border-slate-200/80 dark:bg-slate-950/60 dark:border-slate-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Active Feed</h4>
                                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Synchronized</span>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="p-3 rounded-xl border bg-white dark:bg-slate-900/80 border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                                    POS
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-900 dark:text-white">Order #1094</p>
                                                    <p class="text-[11px] text-slate-500">Counter 01 · Dine In</p>
                                                </div>
                                            </div>
                                            <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">+$42.50</span>
                                        </div>

                                        <div class="p-3 rounded-xl border bg-white dark:bg-slate-900/80 border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold text-xs">
                                                    INV
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-900 dark:text-white">Invoice #INV-2026</p>
                                                    <p class="text-[11px] text-slate-500">Acme Logistics Corp</p>
                                                </div>
                                            </div>
                                            <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">+$1,450.00</span>
                                        </div>

                                        <div class="p-3 rounded-xl border bg-white dark:bg-slate-900/80 border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold text-xs">
                                                    MRP
                                                </div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-900 dark:text-white">MO-0042 Completed</p>
                                                    <p class="text-[11px] text-slate-500">200x Roasted Beans</p>
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Ready</span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </section>

            <!-- PLATFORM MODULES DETAILED SECTION -->
            <section id="modules" class="py-20 border-t border-slate-200 dark:border-slate-800/80">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">All Modules Included</span>
                        <h2 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight mt-2">
                            A Modular Architecture with Zero Silos
                        </h2>
                        <p class="text-base text-slate-600 dark:text-slate-400 mt-3">
                            Turn modules on or off with a toggle. Every transaction automatically syncs with the general ledger and inventory balance.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="mod in platformModules"
                            :key="mod.id"
                            :id="mod.id"
                            class="p-8 rounded-3xl border bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-indigo-500/50 dark:hover:border-indigo-500/50 shadow-xs hover:shadow-xl transition-all duration-200 group flex flex-col justify-between"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-5">
                                    <div :class="['w-12 h-12 rounded-2xl bg-gradient-to-tr flex items-center justify-center text-white shadow-md', mod.iconColor]">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ mod.badge }}
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ mod.name }}
                                </h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                                    {{ mod.description }}
                                </p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-500 dark:text-slate-400">Capability metric:</span>
                                <span class="font-bold font-mono text-indigo-600 dark:text-indigo-400">{{ mod.stats }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- PRICING PLANS SECTION -->
            <section id="pricing" class="py-20 border-t border-slate-200 dark:border-slate-800/80 bg-slate-100/50 dark:bg-slate-950/40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center max-w-3xl mx-auto mb-14">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Predictable Pricing</span>
                        <h2 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight mt-2">
                            Transparent Plans with Zero Hidden Fees
                        </h2>
                        <p class="text-base text-slate-600 dark:text-slate-400 mt-3">
                            Start free on our 14-day trial. Upgrade, downgrade, or cancel anytime from your billing dashboard.
                        </p>

                        <!-- Billing Toggle -->
                        <div class="mt-8 inline-flex items-center p-1.5 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-xs">
                            <button
                                type="button"
                                @click="billingCycle = 'monthly'"
                                :class="[
                                    'px-5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer',
                                    billingCycle === 'monthly'
                                        ? 'bg-indigo-600 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                ]"
                            >
                                Monthly Billing
                            </button>
                            <button
                                type="button"
                                @click="billingCycle = 'annual'"
                                :class="[
                                    'px-5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                                    billingCycle === 'annual'
                                        ? 'bg-indigo-600 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                                ]"
                            >
                                <span>Annual Billing</span>
                                <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-[10px] font-extrabold">Save 20%</span>
                            </button>
                        </div>
                    </div>

                    <!-- Pricing Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
                        <div
                            v-for="plan in displayPlans"
                            :key="plan.id"
                            :class="[
                                'rounded-3xl p-8 border flex flex-col justify-between transition-all duration-200 relative',
                                plan.is_popular
                                    ? 'bg-white dark:bg-slate-900 border-indigo-500 dark:border-indigo-500 shadow-2xl ring-2 ring-indigo-500/20 md:-translate-y-2'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-xs'
                            ]"
                        >
                            <div v-if="plan.is_popular" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                                <span class="px-3.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider text-white bg-gradient-to-r from-indigo-600 to-purple-600 shadow-md">
                                    Most Popular
                                </span>
                            </div>

                            <div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ plan.name }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 min-h-[36px]">{{ plan.description }}</p>

                                <div class="mt-6 flex items-baseline">
                                    <span class="text-4xl sm:text-5xl font-extrabold font-mono text-slate-900 dark:text-white">
                                        ${{ calculatePrice(plan.price) }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 ml-2">
                                        / month
                                    </span>
                                </div>

                                <hr class="my-6 border-slate-100 dark:border-slate-800" />

                                <div class="space-y-3">
                                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Features Included:</div>
                                    <div v-for="(feat, fi) in plan.features" :key="fi" class="flex items-start text-xs text-slate-700 dark:text-slate-300 gap-2.5">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>{{ feat.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 pt-4">
                                <Link
                                    :href="`/register?plan=${plan.slug}`"
                                    :class="[
                                        'w-full py-3 px-6 rounded-xl text-center text-sm font-bold block transition-all shadow-sm cursor-pointer',
                                        plan.is_popular
                                            ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-500/25'
                                            : 'bg-slate-100 hover:bg-slate-200 text-slate-900 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-white'
                                    ]"
                                >
                                    Get Started with {{ plan.name }} &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- FAQS ACCORDION SECTION -->
            <section id="faqs" class="py-20 border-t border-slate-200 dark:border-slate-800/80">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    <div class="text-center mb-14">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Frequently Asked Questions</span>
                        <h2 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight mt-2">
                            Answers to Everything You Need
                        </h2>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(faq, idx) in faqs"
                            :key="idx"
                            class="rounded-2xl border transition-colors bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 overflow-hidden"
                        >
                            <button
                                type="button"
                                @click="toggleFaq(idx)"
                                class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-base text-slate-900 dark:text-white cursor-pointer"
                            >
                                <span>{{ faq.q }}</span>
                                <svg
                                    class="w-5 h-5 shrink-0 text-slate-400 transition-transform duration-200 ml-4"
                                    :class="{'rotate-180 text-indigo-600 dark:text-indigo-400': activeFaq === idx}"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                v-show="activeFaq === idx"
                                class="px-6 pb-6 text-sm text-slate-600 dark:text-slate-400 leading-relaxed border-t border-slate-100 dark:border-slate-800/70 pt-4"
                            >
                                {{ faq.a }}
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Bottom Call to Action Banner -->
            <section class="py-16 border-t border-slate-200 dark:border-slate-800/80 bg-gradient-to-b from-indigo-50/50 to-white dark:from-slate-900/60 dark:to-slate-950">
                <div class="max-w-4xl mx-auto px-4 text-center">
                    <h2 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                        Transform Your Organization Today
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-3 max-w-xl mx-auto">
                        Setup your enterprise workspace in less than 2 minutes. No credit card required.
                    </p>
                    <div class="mt-8 flex justify-center">
                        <Link
                            href="/register"
                            class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl text-base font-bold text-white shadow-xl shadow-indigo-600/30 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 transition-all transform hover:-translate-y-0.5"
                        >
                            <span>Get Started Now</span>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Footer (SuperAdmin and Request Demo Removed) -->
            <footer class="border-t border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-950 py-12 text-xs text-slate-500">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-900 pb-6">
                        <div class="flex items-center space-x-2.5">
                            <div class="h-7 w-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="font-bold text-slate-800 dark:text-slate-200">SathiSaaS Platform &copy; {{ new Date().getFullYear() }}</span>
                        </div>

                        <!-- CMS Custom Dynamic Pages -->
                        <div class="flex flex-wrap items-center gap-6 text-slate-600 dark:text-slate-400 font-medium">
                            <Link href="/page/about-us" class="hover:text-indigo-600 dark:hover:text-white transition-colors">About Us</Link>
                            <Link href="/page/terms-of-service" class="hover:text-indigo-600 dark:hover:text-white transition-colors">Terms of Service</Link>
                            <Link href="/page/privacy-policy" class="hover:text-indigo-600 dark:hover:text-white transition-colors">Privacy Policy</Link>
                            <Link href="/page/faq" class="hover:text-indigo-600 dark:hover:text-white transition-colors">FAQ</Link>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-slate-500">
                        <p>Enterprise ERP & Multi-Tenant Operating Platform for Global Scale.</p>
                        <div class="flex items-center space-x-6">
                            <Link href="/register" class="hover:text-indigo-600 dark:hover:text-white transition-colors">Register Company</Link>
                            <a href="/update" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">System Updater</a>
                        </div>
                    </div>
                </div>
            </footer>

        </div>
    </div>
</template>
