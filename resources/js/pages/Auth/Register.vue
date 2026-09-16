<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Combobox from '@/components/Combobox.vue';
import Select from '@/components/Select.vue';
import {
    COUNTRY_OPTIONS,
    CURRENCY_OPTIONS,
    TIMEZONE_OPTIONS,
    findCountryDefaults,
} from '@/constants/referenceData';

const props = defineProps<{
    selectedPlan?: string;
}>();

const isDark = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

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

const currentStep = ref<1 | 2>(1);

const industryOptions = [
    { label: 'Food & Beverage / Restaurant', value: 'restaurant' },
    { label: 'Retail & E-commerce', value: 'retail' },
    { label: 'Software & Technology', value: 'technology' },
    { label: 'Healthcare & Medical', value: 'healthcare' },
    { label: 'Financial Services', value: 'finance' },
    { label: 'Education & Training', value: 'education' },
    { label: 'Hospitality & Tourism', value: 'hospitality' },
    { label: 'Other', value: 'other' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    company_name: '',
    industry: 'restaurant',
    country_code: 'MY',
    currency: 'MYR',
    timezone: 'Asia/Kuala_Lumpur',
    locale: 'en',
    plan_slug: props.selectedPlan || 'enterprise',
    terms_accepted: false,
});

const handleCountryChange = (countryCode: string | number) => {
    const code = String(countryCode);
    const defaults = findCountryDefaults(code);
    if (defaults) {
        form.currency = defaults.currency;
        form.timezone = defaults.timezone;
        form.locale = defaults.locale;
    }
};

const validateStep1 = () => {
    form.clearErrors('name', 'email', 'password', 'password_confirmation');
    let hasError = false;

    if (!form.name.trim()) {
        form.setError('name', 'Full name is required.');
        hasError = true;
    }
    if (!form.email.trim()) {
        form.setError('email', 'Work email is required.');
        hasError = true;
    }
    if (!form.password) {
        form.setError('password', 'Password is required.');
        hasError = true;
    } else if (form.password.length < 8) {
        form.setError('password', 'Password must be at least 8 characters.');
        hasError = true;
    }
    if (!form.password_confirmation) {
        form.setError('password_confirmation', 'Password confirmation is required.');
        hasError = true;
    } else if (form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Passwords do not match.');
        hasError = true;
    }

    if (!hasError) {
        currentStep.value = 2;
    }
};

const submit = () => {
    if (!form.company_name.trim()) {
        form.setError('company_name', 'Company name is required.');
        return;
    }
    if (!form.terms_accepted) {
        form.setError('terms_accepted' as any, 'You must agree to the Terms of Service.');
        return;
    }

    form.post('/register', {
        onError: (errors) => {
            if (errors.name || errors.email || errors.password || errors.password_confirmation) {
                currentStep.value = 1;
            }
        },
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Register Organization" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col justify-center py-10 sm:px-6 lg:px-8 relative overflow-hidden transition-colors duration-200">
        <!-- Background Grid Pattern -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:28px_28px] pointer-events-none"></div>

        <!-- Top Navigation Bar (Back & Theme Switcher) -->
        <div class="w-full max-w-[540px] mx-auto mb-4 flex items-center justify-between relative z-20 px-2 sm:px-0">
            <Link
                href="/"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 dark:text-slate-400 dark:hover:text-white shadow-xs hover:shadow-sm transition-all"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Home</span>
            </Link>

            <button
                type="button"
                @click="toggleTheme"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 shadow-xs hover:shadow-sm transition-all cursor-pointer"
            >
                <svg v-if="isDark" class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg v-else class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <span>{{ isDark ? 'Light' : 'Dark' }}</span>
            </button>
        </div>

        <!-- Main Card Container -->
        <div class="w-full max-w-[540px] mx-auto rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/95 backdrop-blur-xl p-8 sm:p-10 shadow-xl shadow-slate-900/5 dark:shadow-black/40 relative z-10 transition-all duration-200">
            
            <!-- Brand & Header -->
            <div class="text-center mb-6">
                <Link href="/" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 text-white font-bold text-lg shadow-lg shadow-indigo-500/25 ring-4 ring-indigo-50 dark:ring-indigo-950/40 transition-transform hover:scale-105">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </Link>
                <h1 class="mt-4 text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                    Register Organization
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Get started with SathiSaaS. Start your 14-day full platform trial today.
                </p>
            </div>

            <!-- Modern Step Tabs -->
            <div class="flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 mb-6">
                <button
                    type="button"
                    @click="currentStep = 1"
                    :class="[
                        'flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer',
                        currentStep === 1
                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    ]">
                    <span class="h-4.5 w-4.5 rounded-full flex items-center justify-center text-[10px] font-bold"
                          :class="currentStep === 1 ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-500'">1</span>
                    <span>Admin Profile</span>
                </button>
                <button
                    type="button"
                    @click="validateStep1"
                    :class="[
                        'flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer',
                        currentStep === 2
                            ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    ]">
                    <span class="h-4.5 w-4.5 rounded-full flex items-center justify-center text-[10px] font-bold"
                          :class="currentStep === 2 ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-500'">2</span>
                    <span>Organization</span>
                </button>
            </div>

            <!-- Form: novalidate prevents browser from throwing un-focusable input error on hidden steps -->
            <form @submit.prevent="submit" novalidate class="space-y-4">
                
                <!-- STEP 1: Admin Account Details -->
                <div v-show="currentStep === 1" class="space-y-4">
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Full Name *
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input
                                id="name"
                                name="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Alex Morgan"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/60 pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 transition-all focus:bg-white dark:focus:bg-slate-950 focus:border-indigo-600 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-600/10 dark:focus:ring-indigo-500/20 focus:outline-none"
                                :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20': form.errors.name }"
                            />
                        </div>
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Work Email *
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                id="email"
                                name="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                placeholder="alex@company.com"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/60 pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 transition-all focus:bg-white dark:focus:bg-slate-950 focus:border-indigo-600 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-600/10 dark:focus:ring-indigo-500/20 focus:outline-none"
                                :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500">{{ form.errors.email }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Password *
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-slate-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/60 pl-9 pr-9 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 transition-all focus:bg-white dark:focus:bg-slate-950 focus:border-indigo-600 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-600/10 dark:focus:ring-indigo-500/20 focus:outline-none"
                                    :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20': form.errors.password }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors cursor-pointer"
                                >
                                    <svg v-if="showPassword" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Confirm *
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-slate-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/60 pl-9 pr-9 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 transition-all focus:bg-white dark:focus:bg-slate-950 focus:border-indigo-600 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-600/10 dark:focus:ring-indigo-500/20 focus:outline-none"
                                    :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20': form.errors.password_confirmation }"
                                />
                                <button
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors cursor-pointer"
                                >
                                    <svg v-if="showConfirmPassword" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-rose-500">{{ form.errors.password_confirmation }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="validateStep1"
                        class="w-full mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white font-bold py-3 px-4 text-sm shadow-lg shadow-indigo-600/25 transition-all duration-150 cursor-pointer">
                        <span>Continue to Company Details &rarr;</span>
                    </button>
                </div>

                <!-- STEP 2: Company / Organization Details -->
                <div v-show="currentStep === 2" class="space-y-4">
                    
                    <!-- Selected Plan Banner -->
                    <div class="p-3 rounded-2xl border flex items-center justify-between bg-indigo-50/70 border-indigo-100 dark:bg-indigo-950/40 dark:border-indigo-900/60">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-[10px]">
                                ★
                            </span>
                            <div>
                                <span class="text-slate-500 dark:text-slate-400">Selected Plan: </span>
                                <span class="font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wide">{{ form.plan_slug }}</span>
                            </div>
                        </div>
                        <a href="/#pricing" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">Change</a>
                    </div>

                    <div>
                        <label for="company_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Company / Organization Name *
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <input
                                id="company_name"
                                name="company_name"
                                v-model="form.company_name"
                                type="text"
                                placeholder="Acme Global Technologies"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/60 pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 transition-all focus:bg-white dark:focus:bg-slate-950 focus:border-indigo-600 dark:focus:border-indigo-500 focus:ring-4 focus:ring-indigo-600/10 dark:focus:ring-indigo-500/20 focus:outline-none"
                                :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20': form.errors.company_name }"
                            />
                        </div>
                        <p v-if="form.errors.company_name" class="mt-1 text-xs text-rose-500">{{ form.errors.company_name }}</p>
                    </div>

                    <!-- Industry Classification -->
                    <div>
                        <Select
                            v-model="form.industry"
                            label="Industry / Sector"
                            placeholder="Select industry classification"
                            :options="industryOptions"
                            :error="form.errors.industry"
                        />
                    </div>

                    <!-- Regional Defaults: Country & Currency (Searchable Comboboxes using same referenceData as Superadmin) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <Combobox
                                v-model="form.country_code"
                                label="Country / Region"
                                placeholder="Search & select country..."
                                search-placeholder="Search all 240+ countries..."
                                :options="COUNTRY_OPTIONS"
                                :error="form.errors.country_code"
                                required
                                @change="handleCountryChange"
                            />
                            <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                Auto-populates currency & timezone.
                            </p>
                        </div>

                        <div>
                            <Combobox
                                v-model="form.currency"
                                label="Billing Currency"
                                placeholder="Search & select currency..."
                                search-placeholder="Search currencies..."
                                :options="CURRENCY_OPTIONS"
                                :error="form.errors.currency"
                                required
                            />
                        </div>
                    </div>

                    <!-- Timezone selection -->
                    <div>
                        <Combobox
                            v-model="form.timezone"
                            label="Default Timezone"
                            placeholder="Search & select timezone..."
                            search-placeholder="Search timezones..."
                            :options="TIMEZONE_OPTIONS"
                            :error="form.errors.timezone"
                        />
                    </div>

                    <!-- Terms agreement -->
                    <div class="pt-1">
                        <label class="flex items-start gap-2.5 text-xs text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.terms_accepted"
                                class="h-4 w-4 mt-0.5 rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-indigo-600 focus:ring-indigo-500 transition-colors cursor-pointer"
                            />
                            <span>I agree to the Terms of Service and Privacy Policy for multi-tenant software operations.</span>
                        </label>
                        <p v-if="form.errors.terms_accepted" class="mt-1 text-xs text-rose-500">{{ form.errors.terms_accepted }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="button"
                            @click="currentStep = 1"
                            class="w-1/3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/60 dark:hover:bg-slate-800 py-3 px-4 text-sm font-semibold text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white transition-all cursor-pointer">
                            &larr; Back
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-2/3 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] py-3 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition-all duration-150 disabled:opacity-50 cursor-pointer">
                            <svg
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <span>{{ form.processing ? 'Creating Workspace...' : 'Create Organization' }}</span>
                        </button>
                    </div>
                </div>

            </form>

            <!-- Footer link -->
            <div class="mt-8 border-t border-slate-200 dark:border-slate-800 pt-6 text-center text-xs text-slate-500 dark:text-slate-400">
                <span>Already have a workspace? </span>
                <Link href="/login" class="font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 underline underline-offset-4">
                    Sign in here
                </Link>
            </div>

        </div>

        <!-- Trust & Security Micro-Footer -->
        <div class="mt-6 text-center text-[11px] text-slate-500 dark:text-slate-500 relative z-10 flex items-center justify-center gap-2">
            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>256-bit SSL Bank-Grade Encryption • Multi-Tenant Isolated Storage</span>
        </div>

    </div>
</template>
