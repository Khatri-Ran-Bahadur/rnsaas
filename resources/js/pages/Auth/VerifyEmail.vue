<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

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

const form = useForm({});

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

const submit = () => {
    form.post('/email/verification-notification');
};
</script>

<template>
    <Head title="Verify Email Address - SathiSaaS" />

    <div :class="{'dark': isDark}" class="min-h-screen flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8 font-sans antialiased selection:bg-indigo-600 selection:text-white transition-colors duration-200 bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 relative overflow-hidden">
        
        <!-- Ambient Decorative Background Glows -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-purple-500/10 dark:bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:28px_28px] pointer-events-none"></div>

        <!-- Top Navigation Bar (Back & Theme Switcher) -->
        <div class="w-full max-w-[460px] mx-auto mb-4 flex items-center justify-between relative z-20">
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
        <div class="w-full max-w-[460px] mx-auto rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/95 backdrop-blur-xl p-8 sm:p-10 shadow-xl shadow-slate-900/5 dark:shadow-black/40 relative z-10 text-center transition-all duration-200">
            
            <!-- Email Icon Badge -->
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-800/80 text-indigo-600 dark:text-indigo-400 mb-6 shadow-md shadow-indigo-500/10">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                Verify Your Work Email
            </h1>

            <p class="mt-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                Thanks for registering your organization! Before getting started, please check your inbox and click the verification link we just sent you.
            </p>

            <div v-if="verificationLinkSent" class="mt-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                A fresh verification link has been dispatched to your email address.
            </div>

            <div class="mt-8 flex flex-col gap-3">
                <form @submit.prevent="submit">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] py-3 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition-all duration-150 disabled:opacity-50 cursor-pointer">
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
                        <span>{{ form.processing ? 'Dispatching Email...' : 'Resend Verification Email' }}</span>
                    </button>
                </form>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full py-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/60 dark:hover:bg-slate-800 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white text-xs font-semibold transition-all cursor-pointer">
                    Log Out of Session
                </Link>
            </div>

            <div class="mt-6 text-[11px] text-slate-400 dark:text-slate-500">
                Didn't receive the email? Check your spam/junk folder or contact your platform administrator.
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

