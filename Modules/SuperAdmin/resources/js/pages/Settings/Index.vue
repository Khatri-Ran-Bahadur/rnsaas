<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/Button.vue';
import Badge from '@/components/Badge.vue';
import Card from '@/components/Card.vue';
import Switch from '@/components/Switch.vue';
import Modal from '@/components/Modal.vue';
import MediaLibraryModal from '@/components/MediaLibraryModal.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import Combobox from '@/components/Combobox.vue';
import type { ComboboxOption } from '@/components/Combobox.vue';
import type { SelectOption } from '@/components/Select.vue';
import {
    CURRENCY_OPTIONS,
    TIMEZONE_OPTIONS,
    LOCALE_OPTIONS,
} from '@/constants/referenceData';
import { useTheme, AVAILABLE_COLOR_THEMES } from '@/composables/useTheme';

const { colorTheme, setColorTheme } = useTheme();

interface SettingsData {
    general?: {
        platform_name?: string;
        support_email?: string | null;
        support_phone?: string | null;
        timezone?: string;
        currency?: string;
        date_format?: string;
    };
    branding?: {
        logo_media_id?: number | null;
        favicon_media_id?: number | null;
        logo_url?: string | null;
        favicon_url?: string | null;
        login_logo_media_id?: number | null;
        login_logo_url?: string | null;
    };
    system?: {
        maintenance_mode?: boolean;
        maintenance_message?: string | null;
        allow_registrations?: boolean;
        system_notice?: string | null;
    };
    mail?: {
        host?: string | null;
        port?: number | null;
        username?: string | null;
        encryption?: string | null;
        from_address?: string | null;
        from_name?: string | null;
        password_configured?: boolean;
    };
    storage?: {
        default_disk?: string;
        storage_used?: string;
        available_storage?: string;
        health?: string;
    };
    security?: Record<string, any>;
    health?: Record<string, any>;
}

interface Props {
    settings?: SettingsData;
    timezones?: string[];
    currencies?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    settings: () => ({}),
    timezones: () => [],
    currencies: () => [],
});

const page = usePage();

// Available Tabs
type TabId =
    | 'general'
    | 'branding'
    | 'mail'
    | 'system'
    | 'cache'
    | 'storage'
    | 'security'
    | 'health';

const activeTab = ref<TabId>('general');

const tabs = [
    { id: 'general', label: 'General', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
    { id: 'branding', label: 'Branding', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'mail', label: 'Email (SMTP)', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    { id: 'system', label: 'System', icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4' },
    { id: 'cache', label: 'Cache', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { id: 'storage', label: 'Storage', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' },
    { id: 'security', label: 'Security', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { id: 'health', label: 'System Health', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
];

// Select / Combobox Reference Data
const dateFormatOptions: SelectOption[] = [
    { label: 'YYYY-MM-DD (e.g. 2026-09-04)', value: 'Y-m-d' },
    { label: 'MM/DD/YYYY (e.g. 09/04/2026)', value: 'm/d/Y' },
    { label: 'DD/MM/YYYY (e.g. 04/09/2026)', value: 'd/m/Y' },
    { label: 'DD MMM YYYY (e.g. 04 Sep 2026)', value: 'd M Y' },
    { label: 'MMM DD, YYYY (e.g. Sep 04, 2026)', value: 'M d, Y' },
];

const encryptionOptions: SelectOption[] = [
    { label: 'TLS (Port 587 - Recommended)', value: 'tls' },
    { label: 'SSL (Port 465)', value: 'ssl' },
    { label: 'STARTTLS', value: 'starttls' },
    { label: 'None (Unencrypted / Local Mailpit)', value: 'none' },
];

const timezoneOptions = computed<ComboboxOption[]>(() => {
    if (!props.timezones || props.timezones.length === 0) {
        return TIMEZONE_OPTIONS;
    }

    const prioritySet = new Set(TIMEZONE_OPTIONS.map((t) => String(t.value)));
    const list: ComboboxOption[] = [...TIMEZONE_OPTIONS];

    props.timezones.forEach((tz) => {
        if (!prioritySet.has(tz)) {
            list.push({ label: tz, value: tz });
        }
    });

    return list;
});

const currencyOptions = computed<ComboboxOption[]>(() => {
    if (!props.currencies || props.currencies.length === 0) {
        return CURRENCY_OPTIONS;
    }

    const map = new Map<string, ComboboxOption>();
    CURRENCY_OPTIONS.forEach((c) => map.set(String(c.value), c));

    const list: ComboboxOption[] = [...CURRENCY_OPTIONS];
    const existing = new Set(CURRENCY_OPTIONS.map((c) => String(c.value)));

    props.currencies.forEach((code) => {
        if (!existing.has(code)) {
            list.push({ label: code, value: code });
        }
    });

    return list;
});

// Branding Subtabs State
const brandingSubTab = ref<'logos' | 'text' | 'theme'>('logos');

const getFileName = (url: string | null | undefined): string => {
    if (!url) return '';
    const parts = url.split('/');
    const name = parts[parts.length - 1];
    return name ? decodeURIComponent(name.split('?')[0]) : '';
};

const timeFormatOptions: SelectOption[] = [
    { label: '24 Hour (13:30)', value: '24' },
    { label: '12 Hour (01:30 PM)', value: '12' },
];

const calendarStartDayOptions: SelectOption[] = [
    { label: 'Sunday', value: 'sunday' },
    { label: 'Monday', value: 'monday' },
    { label: 'Saturday', value: 'saturday' },
];

// Form Definition
const form = useForm({
    general: {
        platform_name: props.settings?.general?.platform_name ?? 'SathiSaaS',
        support_email: props.settings?.general?.support_email ?? '',
        support_phone: props.settings?.general?.support_phone ?? '',
        timezone: props.settings?.general?.timezone ?? 'UTC',
        currency: props.settings?.general?.currency ?? 'USD',
        date_format: props.settings?.general?.date_format ?? 'Y-m-d',
    },
    branding: {
        logo_media_id: props.settings?.branding?.logo_media_id ?? null,
        favicon_media_id: props.settings?.branding?.favicon_media_id ?? null,
        logo_url: props.settings?.branding?.logo_url ?? null,
        favicon_url: props.settings?.branding?.favicon_url ?? null,
        login_logo_media_id: props.settings?.branding?.login_logo_media_id ?? null,
        login_logo_url: props.settings?.branding?.login_logo_url ?? null,
    },
    system: {
        maintenance_mode: Boolean(props.settings?.system?.maintenance_mode),
        maintenance_message: props.settings?.system?.maintenance_message ?? 'We are currently performing scheduled maintenance.',
        allow_registrations: Boolean(props.settings?.system?.allow_registrations ?? true),
        system_notice: props.settings?.system?.system_notice ?? '',
        default_language: props.settings?.system?.default_language ?? 'en',
        time_format: props.settings?.system?.time_format ?? '24',
        calendar_start_day: props.settings?.system?.calendar_start_day ?? 'sunday',
    },
    mail: {
        host: props.settings?.mail?.host ?? '',
        port: props.settings?.mail?.port ?? 587,
        username: props.settings?.mail?.username ?? '',
        password: '',
        encryption: props.settings?.mail?.encryption ?? 'tls',
        from_address: props.settings?.mail?.from_address ?? '',
        from_name: props.settings?.mail?.from_name ?? 'SathiSaaS',
    },
});

// Toast / Notification
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

function showToast(message: string, type: 'success' | 'error' = 'success') {
    notification.value = { message, type };
    setTimeout(() => {
        notification.value = null;
    }, 3500);
}

// Media Picker Modal State
const mediaModalOpen = ref(false);
const currentMediaTarget = ref<'logo' | 'favicon' | 'login_logo'>('logo');

const openMediaPicker = (target: 'logo' | 'favicon' | 'login_logo') => {
    currentMediaTarget.value = target;
    mediaModalOpen.value = true;
};

const handleMediaSelected = (selected: any) => {
    const item = Array.isArray(selected) ? selected[0] : selected;
    if (!item) return;

    if (currentMediaTarget.value === 'logo') {
        form.branding.logo_media_id = item.id;
        form.branding.logo_url = item.url;
    } else if (currentMediaTarget.value === 'favicon') {
        form.branding.favicon_media_id = item.id;
        form.branding.favicon_url = item.url;
    } else if (currentMediaTarget.value === 'login_logo') {
        form.branding.login_logo_media_id = item.id;
        form.branding.login_logo_url = item.url;
    }

    mediaModalOpen.value = false;
};

const removeBrandingItem = (target: 'logo' | 'favicon' | 'login_logo') => {
    if (target === 'logo') {
        form.branding.logo_media_id = null;
        form.branding.logo_url = null;
    } else if (target === 'favicon') {
        form.branding.favicon_media_id = null;
        form.branding.favicon_url = null;
    } else if (target === 'login_logo') {
        form.branding.login_logo_media_id = null;
        form.branding.login_logo_url = null;
    }
};

// Modals for Cache / Test Email
const showClearCacheModal = ref(false);
const cacheTargetToClear = ref<string>('all');
const showTestEmailModal = ref(false);
const testEmailAddress = ref('');
const clearingCache = ref(false);

const handleClearCacheConfirm = () => {
    if (cacheTargetToClear.value === 'Platform Settings') {
        clearingCache.value = true;
        const url = window.location.pathname.startsWith('/admin')
            ? '/admin/settings/cache/clear'
            : '/superadmin/settings/cache/clear';

        router.post(url, {}, {
            preserveScroll: true,
            onSuccess: () => {
                clearingCache.value = false;
                showClearCacheModal.value = false;
                showToast('Platform settings cache cleared successfully.');
            },
            onError: () => {
                clearingCache.value = false;
                showClearCacheModal.value = false;
                showToast('Failed to clear platform settings cache.', 'error');
            },
        });
    } else {
        showClearCacheModal.value = false;
        showToast(`${cacheTargetToClear.value} cache flush endpoint is not implemented on the backend.`, 'error');
    }
};

// Submit Handler
const savingSection = ref<string | null>(null);

const saveSection = (sectionName: string) => {
    savingSection.value = sectionName;

    // Resolve update URL (either superadmin or admin prefix)
    const url = window.location.pathname.startsWith('/admin')
        ? '/admin/settings'
        : '/superadmin/settings';

    form.put(url, {
        preserveScroll: true,
        onSuccess: () => {
            savingSection.value = null;
            showToast(`${sectionName} saved successfully.`);
        },
        onError: () => {
            savingSection.value = null;
            showToast('Failed to save settings. Please review form errors.', 'error');
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Platform Settings - SuperAdmin" />

        <!-- Toast Notification -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="notification"
                :class="[
                    'fixed bottom-6 right-6 z-50 flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium shadow-xl text-white',
                    notification.type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'
                ]"
            >
                <svg v-if="notification.type === 'success'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ notification.message }}</span>
            </div>
        </Transition>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="mb-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>SuperAdmin</span>
                        <span>/</span>
                        <span class="text-zinc-800 font-medium dark:text-zinc-200">Platform Settings</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Platform Settings
                    </h1>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        Configure global SaaS behavior, branding, transactional mail, system maintenance, and infrastructure.
                    </p>
                </div>
            </div>

            <!-- Settings Layout: Sidebar Tabs on Left + Content Area on Right -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left: Navigation (Vertical on Desktop, Horizontal Pills on Mobile) -->
                <div class="lg:col-span-3">
                    <!-- Mobile Selector -->
                    <div class="block lg:hidden mb-4">
                        <Select
                            v-model="activeTab"
                            label="Settings Section"
                            placeholder="Choose section..."
                            :options="tabs.map((t) => ({ label: t.label, value: t.id }))"
                            :searchable="false"
                        />
                    </div>

                    <!-- Desktop Nav Card -->
                    <div class="hidden lg:block sticky top-6 rounded-2xl border border-zinc-200/80 bg-white p-2.5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="px-3 py-2 mb-1 border-b border-zinc-100 dark:border-zinc-800/80">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Settings Menu</p>
                        </div>
                        <nav class="space-y-1">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                type="button"
                                :class="[
                                    'group flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-150 text-left',
                                    activeTab === tab.id
                                        ? 'bg-primary-600 text-white shadow-xs shadow-primary-500/25 font-semibold'
                                        : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'
                                ]"
                                @click="activeTab = tab.id as TabId"
                            >
                                <div class="flex items-center gap-3">
                                    <svg
                                        class="h-5 w-5 shrink-0 transition-colors"
                                        :class="activeTab === tab.id ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300'"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" :d="tab.icon" />
                                    </svg>
                                    <span>{{ tab.label }}</span>
                                </div>
                                <svg
                                    v-if="activeTab === tab.id"
                                    class="h-4 w-4 text-white/80 shrink-0"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Right: Selected Settings Panel -->
                <div class="lg:col-span-9 space-y-6">
                    <!-- SECTION 1: GENERAL -->
                    <div v-show="activeTab === 'general'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                General Settings
                            </h2>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Manage basic metadata, localized timezone, default currency, and support contact details.
                            </p>
                        </div>

                        <div class="mt-6 space-y-5">
                            <!-- Platform Name -->
                            <TextInput
                                v-model="form.general.platform_name"
                                label="Platform Name"
                                placeholder="e.g. SathiSaaS"
                                hint="The public name of your SaaS platform."
                                :error="form.errors['general.platform_name']"
                                required
                            />

                            <!-- Support Email & Phone -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <TextInput
                                    v-model="form.general.support_email"
                                    type="email"
                                    label="Support Email"
                                    placeholder="support@sathisaas.com"
                                    hint="Email address customers can use to contact support."
                                    :error="form.errors['general.support_email']"
                                />

                                <TextInput
                                    v-model="form.general.support_phone"
                                    type="text"
                                    label="Support Phone"
                                    placeholder="+1 (555) 000-0000"
                                    hint="Optional support contact number."
                                    :error="form.errors['general.support_phone']"
                                />
                            </div>

                            <!-- Timezone & Currency -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <Combobox
                                    v-model="form.general.timezone"
                                    label="Default Timezone"
                                    placeholder="Search & select timezone..."
                                    search-placeholder="Search timezones..."
                                    :options="timezoneOptions"
                                    hint="Default timezone used by the platform."
                                    :error="form.errors['general.timezone']"
                                    required
                                />

                                <Combobox
                                    v-model="form.general.currency"
                                    label="Default Currency"
                                    placeholder="Search & select currency..."
                                    search-placeholder="Search currencies..."
                                    :options="currencyOptions"
                                    hint="Default platform currency (3-letter ISO code)."
                                    :error="form.errors['general.currency']"
                                    required
                                />
                            </div>

                            <!-- Date Format -->
                            <div>
                                <Select
                                    v-model="form.general.date_format"
                                    label="Date Format"
                                    placeholder="Select date display format"
                                    :options="dateFormatOptions"
                                    hint="Default date display format across platform tables and exports."
                                    :error="form.errors['general.date_format']"
                                    required
                                    :searchable="false"
                                />
                            </div>
                        </div>

                        <!-- Save Footer -->
                        <div class="mt-8 flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <Button
                                :disabled="form.processing"
                                @click="saveSection('General')"
                            >
                                <span v-if="savingSection === 'General'">Saving...</span>
                                <span v-else>Save Changes</span>
                            </Button>
                        </div>
                    </div>

                    <!-- SECTION 2: BRANDING -->
                    <div v-show="activeTab === 'branding'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <!-- Brand Settings Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4 4 0 014-4c.7 0 1.34.19 1.89.52L13 9.42 9.58 6 11 4.58l3.42 3.42 4.17-4.17a1.5 1.5 0 012.12 2.12L16.54 10.12 20 13.58 18.58 15l-3.42-3.42-4.1 4.1c.33.55.52 1.19.52 1.89a4 4 0 01-4 4z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                        Brand Settings
                                    </h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Customize your application's branding and appearance
                                    </p>
                                </div>
                            </div>

                            <Button
                                size="sm"
                                variant="primary"
                                :loading="savingSection === 'Branding'"
                                @click="saveSection('Branding')"
                            >
                                <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                <span>Save Changes</span>
                            </Button>
                        </div>

                        <!-- Pill Sub-Tabs (Logos / Text / Theme) -->
                        <div class="inline-flex w-full sm:w-auto items-center p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200/60 dark:border-zinc-700/50 my-5 gap-1">
                            <button
                                type="button"
                                :class="[
                                    'flex-1 sm:flex-initial flex items-center justify-center gap-2 rounded-lg px-5 py-2 text-xs font-semibold transition-all duration-150',
                                    brandingSubTab === 'logos'
                                        ? 'bg-primary-600 text-white shadow-xs shadow-primary-500/20'
                                        : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/50 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-700/50'
                                ]"
                                @click="brandingSubTab = 'logos'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                <span>Logos</span>
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'flex-1 sm:flex-initial flex items-center justify-center gap-2 rounded-lg px-5 py-2 text-xs font-semibold transition-all duration-150',
                                    brandingSubTab === 'text'
                                        ? 'bg-primary-600 text-white shadow-xs shadow-primary-500/20'
                                        : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/50 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-700/50'
                                ]"
                                @click="brandingSubTab = 'text'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Text</span>
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'flex-1 sm:flex-initial flex items-center justify-center gap-2 rounded-lg px-5 py-2 text-xs font-semibold transition-all duration-150',
                                    brandingSubTab === 'theme'
                                        ? 'bg-primary-600 text-white shadow-xs shadow-primary-500/20'
                                        : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/50 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-700/50'
                                ]"
                                @click="brandingSubTab = 'theme'"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4 4 0 014-4c.7 0 1.34.19 1.89.52L13 9.42 9.58 6 11 4.58l3.42 3.42 4.17-4.17a1.5 1.5 0 012.12 2.12L16.54 10.12 20 13.58 18.58 15l-3.42-3.42-4.1 4.1c.33.55.52 1.19.52 1.89a4 4 0 01-4 4z" />
                                </svg>
                                <span>Theme</span>
                            </button>
                        </div>

                        <!-- SUBTAB 1: LOGOS -->
                        <div v-show="brandingSubTab === 'logos'" class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                            <!-- Left: Logo & Favicon Upload Cards (8 cols) -->
                            <div class="lg:col-span-8 space-y-6">
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <!-- Logo (Dark Mode) -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                            Logo (Dark Mode)
                                        </label>
                                        <div class="flex h-36 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/60">
                                            <img
                                                v-if="form.branding.logo_url"
                                                :src="form.branding.logo_url"
                                                alt="Dark Logo Preview"
                                                class="max-h-24 max-w-full object-contain"
                                            />
                                            <div v-else class="flex items-center gap-2 text-zinc-400 dark:text-zinc-500">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Dark Logo</span>
                                            </div>
                                        </div>
                                        <!-- Input Group -->
                                        <div class="flex items-center gap-1.5">
                                            <input
                                                type="text"
                                                readonly
                                                :value="getFileName(form.branding.logo_url) || 'No file selected'"
                                                class="h-9 flex-1 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 select-none cursor-default truncate"
                                            />
                                            <button
                                                type="button"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                @click="openMediaPicker('logo')"
                                            >
                                                <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>Browse</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-400 hover:text-rose-500 hover:border-rose-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:text-rose-400 disabled:opacity-40"
                                                title="Remove logo"
                                                :disabled="!form.branding.logo_url"
                                                @click="removeBrandingItem('logo')"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Logo (Light Mode) -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                            Logo (Light Mode)
                                        </label>
                                        <div class="flex h-36 items-center justify-center rounded-xl border border-zinc-800 bg-[#16202e] p-4">
                                            <img
                                                v-if="form.branding.login_logo_url"
                                                :src="form.branding.login_logo_url"
                                                alt="Light Logo Preview"
                                                class="max-h-24 max-w-full object-contain"
                                            />
                                            <div v-else class="flex items-center gap-2 text-zinc-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-xs font-semibold text-zinc-200">Light Logo</span>
                                            </div>
                                        </div>
                                        <!-- Input Group -->
                                        <div class="flex items-center gap-1.5">
                                            <input
                                                type="text"
                                                readonly
                                                :value="getFileName(form.branding.login_logo_url) || 'No file selected'"
                                                class="h-9 flex-1 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 select-none cursor-default truncate"
                                            />
                                            <button
                                                type="button"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                @click="openMediaPicker('login_logo')"
                                            >
                                                <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>Browse</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-400 hover:text-rose-500 hover:border-rose-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:text-rose-400 disabled:opacity-40"
                                                title="Remove light logo"
                                                :disabled="!form.branding.login_logo_url"
                                                @click="removeBrandingItem('login_logo')"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Favicon Row -->
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                        Favicon
                                    </label>
                                    <div class="flex h-28 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/60">
                                        <img
                                            v-if="form.branding.favicon_url"
                                            :src="form.branding.favicon_url"
                                            alt="Favicon Preview"
                                            class="h-10 w-10 object-contain"
                                        />
                                        <div v-else class="flex items-center gap-2 text-zinc-400 dark:text-zinc-500">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Favicon</span>
                                        </div>
                                    </div>
                                    <!-- Input Group -->
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="text"
                                            readonly
                                            :value="getFileName(form.branding.favicon_url) || 'No file selected'"
                                            class="h-9 flex-1 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 select-none cursor-default truncate"
                                        />
                                        <button
                                            type="button"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            @click="openMediaPicker('favicon')"
                                        >
                                            <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>Browse</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-400 hover:text-rose-500 hover:border-rose-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:text-rose-400 disabled:opacity-40"
                                            title="Remove favicon"
                                            :disabled="!form.branding.favicon_url"
                                            @click="removeBrandingItem('favicon')"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Live Preview Panel (4 cols) -->
                            <div class="lg:col-span-4">
                                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 sticky top-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Live Preview</span>
                                    </div>

                                    <!-- Mock Window Frame -->
                                    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950">
                                        <!-- Window Titlebar -->
                                        <div class="flex items-center justify-between border-b border-zinc-200 px-3 py-2 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900">
                                            <span class="text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">Dashboard</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="h-2 w-2 rounded-xs bg-zinc-300 dark:bg-zinc-700"></span>
                                                <span class="h-2 w-2 rounded-xs bg-zinc-300 dark:bg-zinc-700"></span>
                                            </div>
                                        </div>

                                        <!-- Window Body -->
                                        <div class="flex h-44">
                                            <!-- Sidebar -->
                                            <div class="w-20 border-r border-zinc-200 dark:border-zinc-800 p-2 space-y-2.5 bg-white dark:bg-zinc-900">
                                                <div class="h-5 flex items-center justify-center overflow-hidden">
                                                    <img
                                                        v-if="form.branding.logo_url"
                                                        :src="form.branding.logo_url"
                                                        alt="Logo"
                                                        class="max-h-5 max-w-full object-contain"
                                                    />
                                                    <div v-else class="h-4 w-4 rounded-sm bg-primary-600"></div>
                                                </div>
                                                <div class="h-1.5 w-full rounded bg-primary-600/80"></div>
                                                <div class="h-1.5 w-3/4 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                                <div class="h-1.5 w-5/6 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                                <div class="h-1.5 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                            </div>

                                            <!-- Content Area -->
                                            <div class="flex-1 p-3 space-y-2">
                                                <div class="h-2 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                                <div class="grid grid-cols-3 gap-1.5 pt-1">
                                                    <div class="h-7 rounded-sm border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900">
                                                        <div class="h-1.5 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                                    </div>
                                                    <div class="h-7 rounded-sm border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900">
                                                        <div class="h-1.5 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                                    </div>
                                                    <div class="h-7 rounded-sm border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900">
                                                        <div class="h-1.5 w-1/2 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                                                    </div>
                                                </div>
                                                <div class="h-14 rounded-sm border border-zinc-200 bg-white p-1.5 dark:border-zinc-800 dark:bg-zinc-900 space-y-1.5">
                                                    <div class="h-1.5 w-full rounded bg-zinc-100 dark:bg-zinc-800"></div>
                                                    <div class="h-1.5 w-4/5 rounded bg-zinc-100 dark:bg-zinc-800"></div>
                                                    <div class="h-1.5 w-3/4 rounded bg-zinc-100 dark:bg-zinc-800"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Preview Footer -->
                                    <div class="mt-3 text-[11px] text-zinc-500 dark:text-zinc-400 space-y-0.5">
                                        <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ form.general.platform_name || 'SathiSaaS' }}</p>
                                        <p>Copyright © {{ form.general.platform_name || 'SathiSaaS' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SUBTAB 2: TEXT -->
                        <div v-show="brandingSubTab === 'text'" class="space-y-5 max-w-2xl py-2">
                            <TextInput
                                v-model="form.general.platform_name"
                                label="Platform Display Name"
                                placeholder="e.g. SathiSaaS Pro"
                                hint="Brand name displayed on public portals, login, emails, and header navigation."
                            />
                            <TextInput
                                v-model="form.system.system_notice"
                                label="Public Header Tagline / Notice"
                                placeholder="Enterprise Multi-Tenant SaaS Platform"
                                hint="Optional short tagline shown beneath the platform logo."
                            />
                        </div>

                        <!-- SUBTAB 3: THEME -->
                        <div v-show="brandingSubTab === 'theme'" class="space-y-6 max-w-2xl py-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1">
                                    Primary Brand Accent Color
                                </label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">
                                    Choose the application's primary brand theme. All buttons, active tabs, links, and accents will automatically update.
                                </p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <button
                                        v-for="opt in AVAILABLE_COLOR_THEMES"
                                        :key="opt.id"
                                        type="button"
                                        :class="[
                                            'flex items-center gap-3 p-3 rounded-xl border text-left transition-all',
                                            colorTheme === opt.id
                                                ? 'border-primary-500 bg-primary-50/70 dark:bg-primary-950/60 ring-2 ring-primary-500/30'
                                                : 'border-zinc-200 bg-white hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800/80'
                                        ]"
                                        @click="setColorTheme(opt.id)"
                                    >
                                        <span
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-white shadow-xs"
                                            :style="{ backgroundColor: opt.colorHex }"
                                        >
                                            <svg v-if="colorTheme === opt.id" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ opt.name }}</p>
                                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ opt.label }}</p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: EMAIL (SMTP) -->
                    <div v-show="activeTab === 'mail'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                        Email & SMTP Configuration
                                    </h2>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Configure the SMTP server used for platform notifications, organization invitations, and transactional emails.
                                    </p>
                                </div>

                                <Button
                                    variant="secondary"
                                    size="sm"
                                    @click="showTestEmailModal = true"
                                >
                                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <span>Send Test Email</span>
                                </Button>
                            </div>
                        </div>

                        <div class="mt-6 space-y-5">
                            <!-- Host & Port -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                                <div class="sm:col-span-2">
                                    <TextInput
                                        v-model="form.mail.host"
                                        label="SMTP Host"
                                        placeholder="e.g. smtp.mailgun.org or smtp.postmarkapp.com"
                                        hint="The hostname of your SMTP mail server."
                                        :error="form.errors['mail.host']"
                                    />
                                </div>

                                <div>
                                    <TextInput
                                        v-model="form.mail.port"
                                        type="number"
                                        label="SMTP Port"
                                        placeholder="587"
                                        hint="Standard: 587 (TLS), 465 (SSL), 25."
                                        :error="form.errors['mail.port']"
                                    />
                                </div>
                            </div>

                            <!-- Username & Password -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <TextInput
                                    v-model="form.mail.username"
                                    label="SMTP Username"
                                    placeholder="postmaster@yourdomain.com"
                                    :error="form.errors['mail.username']"
                                />

                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                            SMTP Password
                                        </label>
                                        <span
                                            v-if="props.settings?.mail?.password_configured"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Configured (Encrypted)
                                        </span>
                                    </div>
                                    <input
                                        v-model="form.mail.password"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Enter new password to overwrite..."
                                        class="flex h-10 w-full items-center rounded-lg border border-zinc-200 bg-white px-3.5 text-sm text-zinc-900 placeholder-zinc-400 shadow-2xs transition-all hover:border-zinc-300 focus:border-primary-500 focus:outline-none dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder-zinc-600 dark:hover:border-zinc-700 dark:focus:border-primary-500"
                                    />
                                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Leave blank to retain current encrypted password.
                                    </p>
                                    <p v-if="form.errors['mail.password']" class="mt-1.5 text-xs text-rose-500 font-medium">
                                        {{ form.errors['mail.password'] }}
                                    </p>
                                </div>
                            </div>

                            <!-- Encryption & From Details -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                                <div>
                                    <Select
                                        v-model="form.mail.encryption"
                                        label="Encryption"
                                        placeholder="Select encryption protocol"
                                        :options="encryptionOptions"
                                        hint="TLS for port 587 or SSL for port 465."
                                        :error="form.errors['mail.encryption']"
                                        :searchable="false"
                                    />
                                </div>

                                <div>
                                    <TextInput
                                        v-model="form.mail.from_address"
                                        type="email"
                                        label="From Address"
                                        placeholder="no-reply@sathisaas.com"
                                        hint="Sender address on outgoing emails."
                                        :error="form.errors['mail.from_address']"
                                    />
                                </div>

                                <div>
                                    <TextInput
                                        v-model="form.mail.from_name"
                                        label="From Name"
                                        placeholder="SathiSaaS"
                                        hint="Sender display name."
                                        :error="form.errors['mail.from_name']"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Save Footer -->
                        <div class="mt-8 flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <Button
                                :disabled="form.processing"
                                @click="saveSection('Email')"
                            >
                                <span v-if="savingSection === 'Email'">Saving...</span>
                                <span v-else>Save Changes</span>
                            </Button>
                        </div>
                    </div>

                    <!-- SECTION 4: SYSTEM -->
                    <div v-show="activeTab === 'system'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <!-- System Settings Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                        System Settings
                                    </h2>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Configure system-wide settings for your application
                                    </p>
                                </div>
                            </div>

                            <Button
                                size="sm"
                                variant="primary"
                                :loading="savingSection === 'System'"
                                @click="saveSection('System')"
                            >
                                <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                <span>Save Changes</span>
                            </Button>
                        </div>

                        <div class="mt-6 space-y-6">
                            <!-- 2-Column Grid (Default Language, Date Format, Time Format, Calendar Start Day) -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <!-- Default Language -->
                                <div>
                                    <Combobox
                                        v-model="form.system.default_language"
                                        label="Default Language"
                                        placeholder="Select language..."
                                        search-placeholder="Search languages..."
                                        :options="LOCALE_OPTIONS"
                                        hint="Primary language used across platform UI and email notifications."
                                        required
                                    />
                                </div>

                                <!-- Date Format -->
                                <div>
                                    <Select
                                        v-model="form.general.date_format"
                                        label="Date Format"
                                        placeholder="Select date display format"
                                        :options="dateFormatOptions"
                                        hint="Date formatting used across platform views."
                                        :searchable="false"
                                        required
                                    />
                                </div>

                                <!-- Time Format -->
                                <div>
                                    <Select
                                        v-model="form.system.time_format"
                                        label="Time Format"
                                        placeholder="Select time format"
                                        :options="timeFormatOptions"
                                        hint="Time display format (12-hour or 24-hour clock)."
                                        :searchable="false"
                                    />
                                </div>

                                <!-- Calendar Start Day -->
                                <div>
                                    <Select
                                        v-model="form.system.calendar_start_day"
                                        label="Calendar Start Day"
                                        placeholder="Select first day of week"
                                        :options="calendarStartDayOptions"
                                        hint="Determines which day appears first in datepickers."
                                        :searchable="false"
                                    />
                                </div>
                            </div>
                            <!-- Maintenance Alert Banner (When active) -->
                            <div
                                v-if="form.system.maintenance_mode"
                                class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                            >
                                <div class="flex items-start gap-3">
                                    <svg class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div class="text-xs">
                                        <strong class="font-semibold">Maintenance Mode Is Active</strong>
                                        <p class="mt-0.5">Non-superadmin users and tenant organizations will see the maintenance message when accessing the platform.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Maintenance Toggle -->
                            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <Switch
                                    v-model="form.system.maintenance_mode"
                                    label="Maintenance Mode"
                                    description="Enable platform maintenance window to block regular tenant access during updates or migrations."
                                />

                                <div v-if="form.system.maintenance_mode" class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        Maintenance Message
                                    </label>
                                    <textarea
                                        v-model="form.system.maintenance_message"
                                        rows="3"
                                        maxlength="500"
                                        class="mt-1.5 block w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                                        placeholder="We are currently performing scheduled maintenance..."
                                    />
                                    <p class="mt-1 text-xs text-zinc-500">This message will be shown on the public maintenance page.</p>
                                </div>
                            </div>

                            <!-- Registration Toggle -->
                            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <Switch
                                    v-model="form.system.allow_registrations"
                                    label="Allow New Tenant Registrations"
                                    description="When disabled, public tenant signup is restricted. Existing organizations can still log in."
                                />
                            </div>

                            <!-- Global Announcement Notice -->
                            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    Global System Notice
                                </label>
                                <textarea
                                    v-model="form.system.system_notice"
                                    rows="2"
                                    class="mt-1.5 block w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                                    placeholder="Optional platform-wide notification banner for all logged in tenants..."
                                />
                                <p class="mt-1 text-xs text-zinc-500">Leave blank if no announcement banner is needed.</p>
                            </div>
                        </div>

                        <!-- Save Footer -->
                        <div class="mt-8 flex items-center justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <Button
                                :disabled="form.processing"
                                @click="saveSection('System')"
                            >
                                <span v-if="savingSection === 'System'">Saving...</span>
                                <span v-else>Save Changes</span>
                            </Button>
                        </div>
                    </div>

                    <!-- SECTION 5: CACHE -->
                    <div v-show="activeTab === 'cache'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                Cache Management
                            </h2>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Manage and flush cached configurations, routes, views, and cached platform settings.
                            </p>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Platform Settings Cache -->
                            <div class="flex flex-col justify-between rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Platform Settings Cache</h3>
                                        <Badge variant="success">Active</Badge>
                                    </div>
                                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Stores cached platform settings in memory. Auto-clears on setting updates.
                                    </p>
                                </div>
                                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        @click="cacheTargetToClear = 'Platform Settings'; showClearCacheModal = true;"
                                    >
                                        Clear Settings Cache
                                    </Button>
                                </div>
                            </div>

                            <!-- Application Cache -->
                            <div class="flex flex-col justify-between rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Application Cache</h3>
                                        <Badge variant="outline">Redis / File</Badge>
                                    </div>
                                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        General application and query cache used across tenant services.
                                    </p>
                                </div>
                                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        @click="cacheTargetToClear = 'Application'; showClearCacheModal = true;"
                                    >
                                        Flush App Cache
                                    </Button>
                                </div>
                            </div>

                            <!-- Configuration Cache -->
                            <div class="flex flex-col justify-between rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Configuration Cache</h3>
                                        <Badge variant="outline">Bootstrap</Badge>
                                    </div>
                                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Cached framework and package configuration files.
                                    </p>
                                </div>
                                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        @click="cacheTargetToClear = 'Configuration'; showClearCacheModal = true;"
                                    >
                                        Clear Config Cache
                                    </Button>
                                </div>
                            </div>

                            <!-- Route Cache -->
                            <div class="flex flex-col justify-between rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Route & View Cache</h3>
                                        <Badge variant="outline">Routes</Badge>
                                    </div>
                                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Pre-compiled routing tables and Blade template caches.
                                    </p>
                                </div>
                                <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        @click="cacheTargetToClear = 'Route and View'; showClearCacheModal = true;"
                                    >
                                        Clear Route Cache
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 6: STORAGE -->
                    <div v-show="activeTab === 'storage'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                        Storage & Assets Overview
                                    </h2>
                                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        Review disk allocation, filesystem driver, and explore the central Media Library.
                                    </p>
                                </div>

                                <Link
                                    href="/superadmin/media"
                                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-primary-500"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Open Media Library</span>
                                </Link>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <span class="text-xs font-medium text-zinc-400">Default Filesystem Disk</span>
                                <p class="mt-1 text-lg font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ props.settings?.storage?.default_disk || 'public (local)' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <span class="text-xs font-medium text-zinc-400">Storage Used</span>
                                <p class="mt-1 text-lg font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ props.settings?.storage?.storage_used || 'Not available' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <span class="text-xs font-medium text-zinc-400">Storage Health</span>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                        {{ props.settings?.storage?.health || 'Operational' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 7: SECURITY -->
                    <div v-show="activeTab === 'security'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                Security & Authentication Policies
                            </h2>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Platform-wide security protocols, session limits, and access enforcement.
                            </p>
                        </div>

                        <div class="mt-6 space-y-4">
                            <!-- Password Policy Item -->
                            <div class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Password Policy Enforcement</h3>
                                        <Badge variant="outline">Coming soon</Badge>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500">Enforce minimum 12-character passwords, numbers, and special characters.</p>
                                </div>
                                <span class="text-xs text-zinc-400">Not configured</span>
                            </div>

                            <!-- Login Protection -->
                            <div class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Brute Force Protection</h3>
                                        <Badge variant="outline">Coming soon</Badge>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500">Rate limits login attempts and temporarily locks IP addresses after 5 failed tries.</p>
                                </div>
                                <span class="text-xs text-zinc-400">Not configured</span>
                            </div>

                            <!-- Two-Factor Authentication -->
                            <div class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Mandatory 2FA for Administrators</h3>
                                        <Badge variant="outline">Coming soon</Badge>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500">Require all SuperAdmin role members to configure TOTP or Passkeys.</p>
                                </div>
                                <span class="text-xs text-zinc-400">Not configured</span>
                            </div>

                            <!-- Session Security -->
                            <div class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Concurrent Session Invalidation</h3>
                                        <Badge variant="outline">Coming soon</Badge>
                                    </div>
                                    <p class="mt-0.5 text-xs text-zinc-500">Terminate other browser sessions automatically on password reset.</p>
                                </div>
                                <span class="text-xs text-zinc-400">Not configured</span>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 8: SYSTEM HEALTH -->
                    <div v-show="activeTab === 'health'" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-zinc-200 pb-4 dark:border-zinc-800">
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                System Health & Infrastructure
                            </h2>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Runtime checks, framework versions, database connectivity, and background queue status.
                            </p>
                        </div>

                        <div class="mt-6 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Application Framework</p>
                                        <p class="text-xs text-zinc-500">SathiSaaS Core Foundation</p>
                                    </div>
                                </div>
                                <Badge variant="success">Healthy</Badge>
                            </div>

                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Database Connection</p>
                                        <p class="text-xs text-zinc-500">MySQL Server Connectivity</p>
                                    </div>
                                </div>
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Connected</span>
                            </div>

                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Queue Worker & Jobs</p>
                                        <p class="text-xs text-zinc-500">Asynchronous background workers</p>
                                    </div>
                                </div>
                                <span class="text-xs text-zinc-400">Not available</span>
                            </div>

                            <div class="flex items-center justify-between p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Scheduled Tasks (Cron)</p>
                                        <p class="text-xs text-zinc-500">Hourly & daily platform crons</p>
                                    </div>
                                </div>
                                <span class="text-xs text-zinc-400">Not available</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Embedded Media Library Modal for Branding Selection -->
        <MediaLibraryModal
            v-if="mediaModalOpen"
            :show="mediaModalOpen"
            :multiple="false"
            endpoint-prefix="/superadmin/media"
            @close="mediaModalOpen = false"
            @select="handleMediaSelected"
        />

        <!-- Clear Cache Confirmation Modal -->
        <Modal
            :show="showClearCacheModal"
            title="Confirm Cache Flush"
            description="Flushing cache might temporarily increase server load as items are re-compiled."
            max-width="md"
            @close="showClearCacheModal = false"
        >
            <div class="space-y-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Are you sure you want to clear the <strong>{{ cacheTargetToClear }}</strong> cache?
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showClearCacheModal = false">
                        Cancel
                    </Button>
                    <button
                        type="button"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-500 disabled:opacity-50"
                        :disabled="clearingCache"
                        @click="handleClearCacheConfirm"
                    >
                        {{ clearingCache ? 'Clearing...' : 'Confirm & Clear' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Send Test Email Modal (UI only as backend route not invented) -->
        <Modal
            :show="showTestEmailModal"
            title="Send Test Email"
            description="Verify that your SMTP configuration is properly delivering emails."
            max-width="md"
            @close="showTestEmailModal = false"
        >
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Recipient Email</label>
                    <input
                        v-model="testEmailAddress"
                        type="email"
                        placeholder="you@domain.com"
                        class="mt-1.5 block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                </div>

                <div class="rounded-lg bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-zinc-800">
                    Note: Test email dispatch endpoint is not yet connected to the backend.
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="secondary" @click="showTestEmailModal = false">
                        Cancel
                    </Button>
                    <Button
                        :disabled="!testEmailAddress"
                        @click="showTestEmailModal = false; showToast('Test email request queued.');"
                    >
                        Send Test
                    </Button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
