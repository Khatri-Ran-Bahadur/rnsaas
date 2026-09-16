<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface NavPage {
    title: string;
    slug: string;
}

interface PageData {
    title: string;
    slug: string;
    content: string;
    meta_title?: string | null;
    meta_description?: string | null;
    updated_at: string;
}

const props = defineProps<{
    page: PageData;
    headerPages?: NavPage[];
    footerPages?: NavPage[];
}>();
</script>

<template>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 flex flex-col">
        <Head>
            <title>{{ page.meta_title || page.title }} - SathiSaaS</title>
            <meta v-if="page.meta_description" name="description" :content="page.meta_description" />
        </Head>

        <!-- Navbar -->
        <header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/80 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-900/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-8">
                    <Link href="/" class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-primary-600 to-indigo-500 flex items-center justify-center text-white font-bold text-lg shadow-md shadow-primary-500/20">
                            S
                        </div>
                        <span class="text-lg font-bold tracking-tight bg-gradient-to-r from-zinc-900 to-zinc-600 dark:from-white dark:to-zinc-300 bg-clip-text text-transparent">
                            SathiSaaS
                        </span>
                    </Link>

                    <nav class="hidden md:flex items-center gap-6">
                        <Link href="/" class="text-xs font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors">
                            Home
                        </Link>
                        <Link
                            v-for="hp in headerPages || []"
                            :key="hp.slug"
                            :href="`/page/${hp.slug}`"
                            :class="[
                                page.slug === hp.slug
                                    ? 'text-primary-600 dark:text-primary-400 font-semibold'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                                'text-xs font-medium transition-colors'
                            ]"
                        >
                            {{ hp.title }}
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/login"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors"
                    >
                        Sign In
                    </Link>
                    <Link
                        href="/register"
                        class="text-xs font-semibold px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white shadow-xs shadow-primary-500/30 transition-all"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-12">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 mb-6">
                <Link href="/" class="hover:underline">Home</Link>
                <span>/</span>
                <span class="text-zinc-800 dark:text-zinc-200 font-medium">{{ page.title }}</span>
            </nav>

            <article class="rounded-2xl border border-zinc-200 bg-white p-8 sm:p-12 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <h1 class="text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100 border-b border-zinc-200/80 pb-6 mb-8 dark:border-zinc-800">
                    {{ page.title }}
                </h1>

                <div
                    class="prose prose-zinc max-w-none dark:prose-invert prose-headings:font-bold prose-headings:tracking-tight prose-a:text-primary-600 dark:prose-a:text-primary-400"
                    v-html="page.content"
                />
            </article>
        </main>

        <!-- Footer -->
        <footer class="border-t border-zinc-200 bg-white py-8 dark:border-zinc-800 dark:bg-zinc-900 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-zinc-500">
                    &copy; {{ new Date().getFullYear() }} SathiSaaS Technologies Inc. All rights reserved.
                </p>

                <div class="flex flex-wrap items-center gap-5 text-xs text-zinc-500">
                    <Link
                        v-for="fp in footerPages || []"
                        :key="fp.slug"
                        :href="`/page/${fp.slug}`"
                        class="hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                    >
                        {{ fp.title }}
                    </Link>
                </div>
            </div>
        </footer>
    </div>
</template>
