<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Button from '@/components/Button.vue';
import Switch from '@/components/Switch.vue';
import TextInput from '@/components/TextInput.vue';

interface PageItem {
    id?: number;
    title?: string;
    slug?: string;
    content?: string;
    meta_title?: string | null;
    meta_description?: string | null;
    is_published?: boolean;
    show_in_header?: boolean;
    show_in_footer?: boolean;
    sort_order?: number;
}

const props = defineProps<{
    page?: PageItem | null;
}>();

const isEditing = Boolean(props.page?.id);

const form = useForm({
    title: props.page?.title || '',
    slug: props.page?.slug || '',
    content: props.page?.content || '',
    meta_title: props.page?.meta_title || '',
    meta_description: props.page?.meta_description || '',
    is_published: props.page?.is_published ?? true,
    show_in_header: props.page?.show_in_header ?? false,
    show_in_footer: props.page?.show_in_footer ?? true,
    sort_order: props.page?.sort_order ?? 0,
});

const submit = () => {
    if (isEditing) {
        form.put(`/superadmin/pages/${props.page!.id}`);
    } else {
        form.post('/superadmin/pages');
    }
};
</script>

<template>
    <SuperAdminLayout>
        <Head :title="`${isEditing ? 'Edit' : 'Create'} Custom Page - SuperAdmin`" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <Link href="/superadmin/pages" class="hover:underline">Pages</Link>
                        <span>/</span>
                        <span class="text-zinc-800 font-medium dark:text-zinc-200">{{ isEditing ? form.title : 'New Page' }}</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ isEditing ? 'Edit Custom Page' : 'Create Custom Page' }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/superadmin/pages"
                        class="rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 shadow-2xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Cancel
                    </Link>
                    <Button :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Page') }}
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Main Content (8 cols) -->
                <div class="space-y-6 lg:col-span-8">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5">
                        <TextInput
                            v-model="form.title"
                            label="Page Title"
                            placeholder="e.g. Terms of Service or About Us"
                            :error="form.errors.title"
                            required
                        />

                        <TextInput
                            v-model="form.slug"
                            label="URL Slug"
                            placeholder="e.g. terms-of-service"
                            hint="Leave blank to automatically slugify from the title."
                            :error="form.errors.slug"
                        />

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                    Page HTML / Markdown Content
                                </label>
                            </div>
                            <textarea
                                v-model="form.content"
                                rows="14"
                                class="flex w-full rounded-lg border border-zinc-200 bg-white p-3.5 text-sm text-zinc-900 shadow-2xs transition-all hover:border-zinc-300 focus:border-primary-500 focus:outline-none dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="Enter full HTML content for this page..."
                                required
                            />
                            <p v-if="form.errors.content" class="mt-1 text-xs text-rose-500">
                                {{ form.errors.content }}
                            </p>
                        </div>
                    </div>

                    <!-- SEO Settings Card -->
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-5">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                            SEO Metadata
                        </h2>

                        <TextInput
                            v-model="form.meta_title"
                            label="Meta Title (Optional)"
                            placeholder="Page title for search engines"
                            :error="form.errors.meta_title"
                        />

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Meta Description (Optional)
                            </label>
                            <textarea
                                v-model="form.meta_description"
                                rows="3"
                                class="flex w-full rounded-lg border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-2xs transition-all hover:border-zinc-300 focus:border-primary-500 focus:outline-none dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
                                placeholder="Brief summary of page content for search engines..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Right Settings Sidebar (4 cols) -->
                <div class="space-y-6 lg:col-span-4">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-2">
                            Publishing & Visibility
                        </h3>

                        <Switch
                            v-model="form.is_published"
                            label="Publish Page"
                            description="Publicly accessible via its direct link."
                        />

                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 space-y-4">
                            <Switch
                                v-model="form.show_in_header"
                                label="Show in Top Header"
                                description="Add link to primary website navbar."
                            />

                            <Switch
                                v-model="form.show_in_footer"
                                label="Show in Footer"
                                description="Add link to website footer."
                            />
                        </div>

                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <TextInput
                                v-model="form.sort_order"
                                type="number"
                                label="Display Sort Order"
                                placeholder="0"
                                hint="Lower numbers appear first."
                            />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
