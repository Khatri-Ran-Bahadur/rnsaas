<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    status?: string | null;
}>();

const page = usePage();
const platform = computed(() => (page.props.platform as any) ?? {
    name: 'SathiSaaS',
    logo_url: null,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Organization Sign In" />

    <div class="flex min-h-screen items-center justify-center bg-zinc-50 p-4 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <div class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-8 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <!-- Brand Logo / Header -->
            <div class="mb-8 text-center">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 font-bold text-lg text-white shadow-md shadow-indigo-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Organization Sign In
                    </h1>
                </div>
                <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Enter your organization administrator credentials to access your workspace.
                </p>
            </div>

            <div v-if="status" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50/80 p-3 text-xs font-medium text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/60 dark:text-emerald-300">
                {{ status }}
            </div>

            <!-- Login Form -->
            <form class="space-y-4" @submit.prevent="submit">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                        Email address
                    </label>
                    <div class="mt-1.5">
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            required
                            autofocus
                            placeholder="admin@yourcompany.com"
                            class="w-full rounded-xl border border-zinc-200 bg-white py-2.5 px-3.5 text-xs text-zinc-900 placeholder-zinc-400 transition-colors focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
                            :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500': form.errors.email }"
                        />
                    </div>
                    <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500">
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Password
                        </label>
                    </div>
                    <div class="mt-1.5">
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full rounded-xl border border-zinc-200 bg-white py-2.5 px-3.5 text-xs text-zinc-900 placeholder-zinc-400 transition-colors focus:border-indigo-600 focus:outline-hidden focus:ring-1 focus:ring-indigo-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
                            :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500': form.errors.password }"
                        />
                    </div>
                    <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-500">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:ring-offset-zinc-900"
                        />
                        <span class="text-xs text-zinc-600 dark:text-zinc-400">Remember me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 py-2.5 px-4 text-xs font-semibold text-white shadow-xs shadow-indigo-500/25 transition-colors hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-600 disabled:opacity-50"
                >
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
                    <span>{{ form.processing ? 'Authenticating...' : 'Sign In to Organization' }}</span>
                </button>
            </form>
        </div>
    </div>
</template>
