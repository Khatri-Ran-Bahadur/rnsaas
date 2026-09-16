<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import MediaLibraryModal, { type MediaItem } from '@/components/MediaLibraryModal.vue';

interface UserData {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar_url: string | null;
    email_verified_at: string | null;
    created_at: string | null;
}

const props = defineProps<{
    user: UserData;
    panel: 'superadmin' | 'admin';
}>();

// Dynamic Layout Selection
const ActiveLayout = computed(() => {
    return props.panel === 'superadmin' ? SuperAdminLayout : OrganizationLayout;
});

// Active Tab
const activeTab = ref<'general' | 'security' | 'overview'>('general');

// Media Library Modal Integration
const showMediaModal = ref(false);
const isUpdatingAvatar = ref(false);

const mediaEndpointPrefix = computed(() => {
    return props.panel === 'superadmin' ? '/superadmin/media' : '/media';
});

// Direct File Input
const fileInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);
const selectedFile = ref<File | null>(null);

const triggerFileInput = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const onFileSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        selectedFile.value = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const cancelDirectUpload = () => {
    avatarPreview.value = null;
    selectedFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const saveDirectUpload = () => {
    if (!selectedFile.value) return;
    isUpdatingAvatar.value = true;
    const url = props.panel === 'superadmin' ? '/superadmin/profile/avatar' : '/admin/profile/avatar';
    
    router.post(url, {
        avatar: selectedFile.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            cancelDirectUpload();
            isUpdatingAvatar.value = false;
        },
        onError: () => {
            isUpdatingAvatar.value = false;
        },
    });
};

const handleMediaSelected = (selected: MediaItem | MediaItem[]) => {
    const item = Array.isArray(selected) ? selected[0] : selected;
    if (!item) return;

    showMediaModal.value = false;
    isUpdatingAvatar.value = true;

    const url = props.panel === 'superadmin' ? '/superadmin/profile/avatar' : '/admin/profile/avatar';
    router.post(url, {
        avatar_url: item.url,
        media_id: item.id,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isUpdatingAvatar.value = false;
        },
    });
};

const removeAvatar = () => {
    if (confirm('Are you sure you want to remove your profile photo?')) {
        isUpdatingAvatar.value = true;
        const url = props.panel === 'superadmin' ? '/superadmin/profile/avatar' : '/admin/profile/avatar';
        router.delete(url, {
            preserveScroll: true,
            onFinish: () => {
                isUpdatingAvatar.value = false;
            },
        });
    }
};

// Profile Details Form
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone ?? '',
});

const submitProfile = () => {
    const url = props.panel === 'superadmin' ? '/superadmin/profile' : '/admin/profile';
    profileForm.put(url, {
        preserveScroll: true,
    });
};

// Password Change Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const submitPassword = () => {
    const url = props.panel === 'superadmin' ? '/superadmin/profile/password' : '/admin/profile/password';
    passwordForm.put(url, {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

// Password Strength
const passwordStrength = computed(() => {
    const pwd = passwordForm.password;
    if (!pwd) return 0;
    let score = 0;
    if (pwd.length >= 8) score += 25;
    if (/[A-Z]/.test(pwd)) score += 25;
    if (/[0-9]/.test(pwd)) score += 25;
    if (/[^A-Za-z0-9]/.test(pwd)) score += 25;
    return score;
});

const passwordStrengthColor = computed(() => {
    const score = passwordStrength.value;
    if (score <= 25) return 'bg-rose-500';
    if (score <= 50) return 'bg-amber-500';
    if (score <= 75) return 'bg-blue-500';
    return 'bg-emerald-500';
});

const passwordStrengthLabel = computed(() => {
    const score = passwordStrength.value;
    if (score <= 25) return 'Weak';
    if (score <= 50) return 'Moderate';
    if (score <= 75) return 'Good';
    return 'Very Strong';
});
</script>

<template>
    <Head title="Account Settings & Profile" />

    <component :is="ActiveLayout">
        <div class="w-full space-y-6 pb-20">
            <!-- Hidden native file input for direct uploads -->
            <input
                ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/webp,image/gif"
                class="hidden"
                @change="onFileSelected"
            />

            <!-- Top Profile Hero Banner with Integrated Media Avatar -->
            <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
                <!-- Atmospheric Cover Background -->
                <div class="relative h-44 sm:h-52 w-full overflow-hidden bg-linear-to-r from-slate-900 via-indigo-950 to-blue-900">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(99,102,241,0.35),transparent_50%)]" />
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,rgba(59,130,246,0.25),transparent_50%)]" />
                    <!-- Grid Mesh Effect -->
                    <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff0a_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0a_1px,transparent_1px)] bg-[size:24px_24px]" />
                </div>

                <!-- Profile Info & Avatar Placement -->
                <div class="relative px-6 sm:px-8 pb-6 sm:pb-8 pt-0">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 -mt-16 sm:-mt-20">
                        <!-- Left: Avatar + Identity -->
                        <div class="flex flex-col sm:flex-row items-center sm:items-end gap-5 text-center sm:text-left">
                            <!-- Avatar with Hover Overlay & Direct Media Pick Trigger -->
                            <div class="relative group">
                                <div class="relative h-28 w-28 sm:h-32 sm:w-32 rounded-3xl p-1.5 bg-white dark:bg-zinc-900 shadow-xl ring-1 ring-slate-200/60 dark:ring-zinc-800">
                                    <div class="relative h-full w-full rounded-2xl overflow-hidden bg-indigo-600 flex items-center justify-center text-white font-bold text-3xl shadow-inner">
                                        <!-- Active Avatar Image or Fallback Initial -->
                                        <img
                                            v-if="avatarPreview || user.avatar_url"
                                            :src="avatarPreview || user.avatar_url!"
                                            :alt="user.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>{{ user.name.charAt(0).toUpperCase() }}</span>

                                        <!-- Loading Spinner Overlay -->
                                        <div
                                            v-if="isUpdatingAvatar"
                                            class="absolute inset-0 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center text-white"
                                        >
                                            <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                            </svg>
                                        </div>

                                        <!-- Hover Action Overlay -->
                                        <button
                                            type="button"
                                            @click="showMediaModal = true"
                                            title="Select or upload photo via Media Library"
                                            class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 text-white cursor-pointer"
                                        >
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="text-[10px] font-semibold mt-1">Change Photo</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Camera Badge Button (Direct Media Trigger) -->
                                <button
                                    type="button"
                                    @click="showMediaModal = true"
                                    title="Open Media Uploader & Library"
                                    class="absolute -bottom-1 -right-1 p-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/30 ring-2 ring-white dark:ring-zinc-900 transition cursor-pointer"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Details -->
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                                        {{ user.name }}
                                    </h1>
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                        :class="panel === 'superadmin' ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="panel === 'superadmin' ? 'bg-indigo-500' : 'bg-emerald-500'" />
                                        {{ panel === 'superadmin' ? 'Super Administrator' : 'Organization Admin' }}
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 dark:text-zinc-400 flex items-center justify-center sm:justify-start gap-2">
                                    <span>{{ user.email }}</span>
                                    <span>•</span>
                                    <span>Member since {{ user.created_at || 'Recently' }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Right: Avatar Action Buttons (Media Library, Direct File, Remove) -->
                        <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2">
                            <!-- If temporary file preview selected, show Save / Cancel -->
                            <template v-if="avatarPreview">
                                <button
                                    type="button"
                                    @click="saveDirectUpload"
                                    :disabled="isUpdatingAvatar"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Save New Photo</span>
                                </button>
                                <button
                                    type="button"
                                    @click="cancelDirectUpload"
                                    class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 transition"
                                >
                                    Cancel
                                </button>
                            </template>

                            <template v-else>
                                <!-- Primary Media Library Uploader Button -->
                                <button
                                    type="button"
                                    @click="showMediaModal = true"
                                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs shadow-indigo-600/20 transition cursor-pointer"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Media Library</span>
                                </button>

                                <!-- Direct File Upload Option -->
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-750 transition shadow-2xs cursor-pointer"
                                >
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <span>Upload File</span>
                                </button>

                                <!-- Remove Photo Button (Only if avatar exists) -->
                                <button
                                    v-if="user.avatar_url"
                                    type="button"
                                    @click="removeAvatar"
                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
                                    title="Remove Avatar"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Modern Segmented Navigation Tabs -->
                <div class="border-t border-slate-100 dark:border-zinc-800/80 px-6 sm:px-8 bg-slate-50/50 dark:bg-zinc-900/50">
                    <div class="flex items-center gap-1 sm:gap-2 py-2 overflow-x-auto">
                        <button
                            type="button"
                            @click="activeTab = 'general'"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap',
                                activeTab === 'general'
                                    ? 'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-xs ring-1 ring-slate-200/80 dark:ring-zinc-700'
                                    : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-zinc-800/60',
                            ]"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Profile & Identity</span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'security'"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap',
                                activeTab === 'security'
                                    ? 'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-xs ring-1 ring-slate-200/80 dark:ring-zinc-700'
                                    : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-zinc-800/60',
                            ]"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Security & Password</span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'overview'"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap',
                                activeTab === 'overview'
                                    ? 'bg-white dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 shadow-xs ring-1 ring-slate-200/80 dark:ring-zinc-700'
                                    : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-zinc-800/60',
                            ]"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Account Details</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 1: Profile Details Form -->
            <div v-show="activeTab === 'general'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left: Guide Card -->
                <div class="md:col-span-1 space-y-3">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                        Personal Information
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
                        Update your public display name, notification email, and direct telephone contact number.
                    </p>
                    <div class="rounded-2xl border border-indigo-100 dark:border-indigo-950/80 bg-indigo-50/50 dark:bg-indigo-950/30 p-4 space-y-2 text-xs text-indigo-950 dark:text-indigo-300">
                        <div class="flex items-center gap-2 font-semibold">
                            <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Photo Tip</span>
                        </div>
                        <p class="text-[11px] leading-relaxed text-indigo-900/80 dark:text-indigo-300/80">
                            You can upload photos directly or browse and pick existing assets directly from your platform Media Library.
                        </p>
                    </div>
                </div>

                <!-- Right: Form Fields -->
                <div class="md:col-span-2 rounded-3xl border border-slate-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 sm:p-7 shadow-xs">
                    <form @submit.prevent="submitProfile" class="space-y-5">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300 mb-1.5">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="profileForm.name"
                                    type="text"
                                    required
                                    placeholder="Jane Doe"
                                    class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950 pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-600 focus:bg-white dark:focus:bg-zinc-950 focus:ring-4 focus:ring-indigo-600/10 focus:outline-hidden transition-all"
                                />
                            </div>
                            <span v-if="profileForm.errors.name" class="text-xs text-rose-600 mt-1 block font-medium">
                                {{ profileForm.errors.name }}
                            </span>
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300 mb-1.5">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="profileForm.email"
                                    type="email"
                                    required
                                    placeholder="user@example.com"
                                    class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950 pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-600 focus:bg-white dark:focus:bg-zinc-950 focus:ring-4 focus:ring-indigo-600/10 focus:outline-hidden transition-all"
                                />
                            </div>
                            <span v-if="profileForm.errors.email" class="text-xs text-rose-600 mt-1 block font-medium">
                                {{ profileForm.errors.email }}
                            </span>
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300 mb-1.5">
                                Phone Number (Optional)
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="profileForm.phone"
                                    type="text"
                                    placeholder="+1 (555) 000-0000"
                                    class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950 pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-indigo-600 focus:bg-white dark:focus:bg-zinc-950 focus:ring-4 focus:ring-indigo-600/10 focus:outline-hidden transition-all"
                                />
                            </div>
                            <span v-if="profileForm.errors.phone" class="text-xs text-rose-600 mt-1 block font-medium">
                                {{ profileForm.errors.phone }}
                            </span>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3 flex items-center justify-end">
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-6 py-2.5 shadow-md shadow-indigo-600/25 transition-all disabled:opacity-50 cursor-pointer"
                            >
                                <svg v-if="profileForm.processing" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <span>{{ profileForm.processing ? 'Saving Changes...' : 'Save Profile Changes' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 2: Security & Password Form -->
            <div v-show="activeTab === 'security'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left: Guide Card -->
                <div class="md:col-span-1 space-y-3">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">
                        Password & Security
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed">
                        Protect your account by creating a strong password that you do not use on other platforms.
                    </p>
                    <ul class="text-[11px] text-slate-500 dark:text-zinc-400 space-y-1.5 pt-1">
                        <li class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>At least 8 characters long</span>
                        </li>
                        <li class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Include uppercase & lowercase letters</span>
                        </li>
                        <li class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Include numbers & special symbols</span>
                        </li>
                    </ul>
                </div>

                <!-- Right: Form Fields -->
                <div class="md:col-span-2 rounded-3xl border border-slate-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 sm:p-7 shadow-xs">
                    <form @submit.prevent="submitPassword" class="space-y-5">
                        <!-- Current Password -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300 mb-1.5">
                                Current Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm.current_password"
                                    :type="showCurrentPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950 pl-4 pr-10 py-2.5 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white dark:focus:bg-zinc-950 focus:ring-4 focus:ring-indigo-600/10 focus:outline-hidden transition-all"
                                />
                                <button
                                    type="button"
                                    @click="showCurrentPassword = !showCurrentPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                                >
                                    <svg v-if="!showCurrentPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <span v-if="passwordForm.errors.current_password" class="text-xs text-rose-600 mt-1 block font-medium">
                                {{ passwordForm.errors.current_password }}
                            </span>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300 mb-1.5">
                                New Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm.password"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950 pl-4 pr-10 py-2.5 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white dark:focus:bg-zinc-950 focus:ring-4 focus:ring-indigo-600/10 focus:outline-hidden transition-all"
                                />
                                <button
                                    type="button"
                                    @click="showNewPassword = !showNewPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                                >
                                    <svg v-if="!showNewPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Password Strength Indicator -->
                            <div v-if="passwordForm.password" class="mt-2 space-y-1">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500 dark:text-zinc-400">Strength:</span>
                                    <span class="font-bold" :class="passwordStrength >= 75 ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ passwordStrengthLabel }}
                                    </span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-200 dark:bg-zinc-800 overflow-hidden">
                                    <div
                                        class="h-full transition-all duration-300 rounded-full"
                                        :class="passwordStrengthColor"
                                        :style="{ width: passwordStrength + '%' }"
                                    />
                                </div>
                            </div>

                            <span v-if="passwordForm.errors.password" class="text-xs text-rose-600 mt-1 block font-medium">
                                {{ passwordForm.errors.password }}
                            </span>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300 mb-1.5">
                                Confirm New Password <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="w-full rounded-xl border border-slate-200 dark:border-zinc-800 bg-slate-50/70 dark:bg-zinc-950 pl-4 pr-10 py-2.5 text-sm text-slate-900 dark:text-white focus:border-indigo-600 focus:bg-white dark:focus:bg-zinc-950 focus:ring-4 focus:ring-indigo-600/10 focus:outline-hidden transition-all"
                                />
                                <button
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                                >
                                    <svg v-if="!showConfirmPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3 flex items-center justify-end">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-xs px-6 py-2.5 shadow-md transition-all hover:bg-slate-800 dark:hover:bg-zinc-100 disabled:opacity-50 cursor-pointer"
                            >
                                <svg v-if="passwordForm.processing" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <span>{{ passwordForm.processing ? 'Updating Password...' : 'Change Password' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 3: Account Details & Audit Overview -->
            <div v-show="activeTab === 'overview'" class="rounded-3xl border border-slate-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-6 sm:p-8 shadow-xs space-y-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">
                        Account Information & Identifiers
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                        Technical profile parameters, permissions level, and registration history.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-950/60 space-y-1">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">User ID</span>
                        <p class="text-sm font-black text-slate-800 dark:text-zinc-200 font-mono">#{{ user.id }}</p>
                    </div>

                    <div class="p-4 rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-950/60 space-y-1">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Primary Role</span>
                        <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 capitalize">{{ panel === 'superadmin' ? 'SuperAdmin' : 'Organization Admin' }}</p>
                    </div>

                    <div class="p-4 rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-950/60 space-y-1">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Email Verified</span>
                        <div class="flex items-center gap-1.5 pt-0.5">
                            <span class="h-2 w-2 rounded-full" :class="user.email_verified_at ? 'bg-emerald-500' : 'bg-amber-500'" />
                            <p class="text-xs font-bold text-slate-800 dark:text-zinc-200">{{ user.email_verified_at ? 'Verified' : 'Unverified' }}</p>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-950/60 space-y-1">
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Registered Since</span>
                        <p class="text-xs font-bold text-slate-800 dark:text-zinc-200">{{ user.created_at || 'Recently' }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-5 bg-slate-50/40 dark:bg-zinc-950/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-0.5">
                        <h3 class="text-xs font-bold text-slate-900 dark:text-white">Active Session Security</h3>
                        <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                            You are currently signed in securely to this device. Remember to sign out after using public devices.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="router.post('/logout')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/40 transition cursor-pointer self-start sm:self-auto"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Sign Out of Account</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- System Media Library Modal Picker -->
        <MediaLibraryModal
            v-if="showMediaModal"
            :show="showMediaModal"
            :multiple="false"
            :endpoint-prefix="mediaEndpointPrefix"
            @close="showMediaModal = false"
            @select="handleMediaSelected"
        />
    </component>
</template>
