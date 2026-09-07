<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import Button from '@/components/Button.vue';

// Specialized components
import SettingsNav from '@/components/superadmin/settings/file-system/SettingsNav.vue';
import FileSystemHeader from '@/components/superadmin/settings/file-system/FileSystemHeader.vue';
import StorageStatusCard from '@/components/superadmin/settings/file-system/StorageStatusCard.vue';
import StorageProviderCard from '@/components/superadmin/settings/file-system/StorageProviderCard.vue';
import StorageConfiguration from '@/components/superadmin/settings/file-system/StorageConfiguration.vue';
import StoragePolicy from '@/components/superadmin/settings/file-system/StoragePolicy.vue';
import ImageOptimization from '@/components/superadmin/settings/file-system/ImageOptimization.vue';
import FileOrganization from '@/components/superadmin/settings/file-system/FileOrganization.vue';
import StorageSecurity from '@/components/superadmin/settings/file-system/StorageSecurity.vue';
import ConnectionTest from '@/components/superadmin/settings/file-system/ConnectionTest.vue';
import StorageHealth from '@/components/superadmin/settings/file-system/StorageHealth.vue';

// Types
import type {
    StorageProviderId,
    StorageProviderInfo,
    LocalStorageConfig,
    S3StorageConfig,
    R2StorageConfig,
    FileUploadPolicyConfig,
    ImageOptimizationConfig,
    FileOrganizationConfig,
    StorageSecurityConfig,
    CurrentStorageStatus,
} from '@/types/superadmin/file-system';

// Initial Mock State
const initialActiveProvider: StorageProviderId = 'r2';
const activeProvider = ref<StorageProviderId>(initialActiveProvider);
const selectedProvider = ref<StorageProviderId>(initialActiveProvider);

const providersList = computed<StorageProviderInfo[]>(() => [
    {
        id: 'local',
        name: 'Local Storage',
        description: 'Store files directly on the application web server disk.',
        badgeText: 'Available',
        isConfigured: true,
        isActive: activeProvider.value === 'local',
        icon: 'local',
        statusText: 'Available',
    },
    {
        id: 's3',
        name: 'Amazon S3',
        description: 'Reliable cloud object storage hosted in AWS buckets.',
        badgeText: 'Not configured',
        isConfigured: false,
        isActive: activeProvider.value === 's3',
        icon: 's3',
        statusText: 'Not configured',
    },
    {
        id: 'r2',
        name: 'Cloudflare R2',
        description: 'S3-compatible zero-egress fee cloud object storage.',
        badgeText: 'Active',
        isConfigured: true,
        isActive: activeProvider.value === 'r2',
        icon: 'r2',
        statusText: 'Active',
    },
]);

// Provider Configurations
const localConfig = reactive<LocalStorageConfig>({
    storageName: 'local-server',
    storagePath: 'storage/app/uploads',
    visibility: 'private',
    maxFileSizeMb: 25,
    allowedFileTypes: ['PDF', 'JPG', 'PNG', 'DOCX', 'XLSX'],
});

const s3Config = reactive<S3StorageConfig>({
    storageName: 'aws-s3-vault',
    bucket: 'sathisaas-production-media',
    region: 'us-east-1',
    accessKey: 'AKIAIOSFODNN7EXAMPLE',
    secretKey: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    endpoint: '',
    folderPrefix: 'media-vault',
    visibility: 'private',
    maxFileSizeMb: 50,
    allowedFileTypes: ['PDF', 'JPG', 'JPEG', 'PNG', 'WEBP', 'DOCX', 'XLSX', 'ZIP'],
});

const r2Config = reactive<R2StorageConfig>({
    storageName: 'cloudflare-r2-primary',
    accountId: 'a8f92b47e56214c990bfa1829e5781a9',
    bucket: 'sathisaas-r2-storage',
    accessKeyId: '8f74a01c3e59b209d8e7c654a32190be',
    secretAccessKey: '98d7a6b5c4e3f2019a8b7c6d5e4f3a2b1c0e9f8a',
    endpoint: 'https://a8f92b47e56214c990bfa1829e5781a9.r2.cloudflarestorage.com',
    folderPrefix: 'platform-assets',
    visibility: 'private',
    maxFileSizeMb: 50,
    allowedFileTypes: ['PDF', 'JPG', 'JPEG', 'PNG', 'WEBP', 'DOC', 'DOCX', 'XLS', 'XLSX', 'CSV', 'ZIP'],
});

// Policies & Settings
const uploadPolicy = reactive<FileUploadPolicyConfig>({
    maxFileSizeMb: 10,
    allowedFileTypes: ['PDF', 'JPG', 'JPEG', 'PNG', 'WEBP', 'DOC', 'DOCX', 'XLS', 'XLSX', 'CSV', 'ZIP'],
});

const imageOptimization = reactive<ImageOptimizationConfig>({
    enabled: true,
    quality: 80,
    maxWidth: 2048,
    maxHeight: 2048,
    convertFormat: 'webp',
    generateThumbnail: true,
    thumbnailWidth: 300,
    thumbnailHeight: 300,
});

const fileOrganization = reactive<FileOrganizationConfig>({
    namingStrategy: 'uuid_original',
    directoryStructure: 'tenant_module_date',
});

const storageSecurity = reactive<StorageSecurityConfig>({
    defaultVisibility: 'private',
    enforceSignedUrls: true,
    signedUrlExpiryMinutes: 60,
});

// Storage Status Mock
const storageStatus = computed<CurrentStorageStatus>(() => {
    const providerName =
        activeProvider.value === 'r2'
            ? 'Cloudflare R2'
            : activeProvider.value === 's3'
            ? 'Amazon S3'
            : 'Local Storage';

    return {
        activeProvider: activeProvider.value,
        providerName,
        isConnected: true,
        isDefault: true,
        usedStorage: '12.4 GB',
        quotaStorage: '100 GB',
        quotaPercent: 12.4,
        fileCount: '18,492',
        lastTested: 'Today, 10:42 AM',
    };
});

// Actions & Interactions
const isSaving = ref(false);
const showSavedToast = ref(false);
const isTestingFromTop = ref(false);
const configSectionRef = ref<HTMLElement | null>(null);
const connectionTestRef = ref<InstanceType<typeof ConnectionTest> | null>(null);

const handleSelectProvider = (id: StorageProviderId) => {
    selectedProvider.value = id;
};

const handleSetActiveProvider = (id: StorageProviderId) => {
    activeProvider.value = id;
    selectedProvider.value = id;
    triggerToast(`Active storage provider updated to ${id.toUpperCase()}`);
};

const triggerConnectionTest = () => {
    if (connectionTestRef.value) {
        connectionTestRef.value.runTest();
    }
};

const scrollToConfig = () => {
    configSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
};

const triggerToast = (msg?: string) => {
    showSavedToast.value = true;
    setTimeout(() => {
        showSavedToast.value = false;
    }, 3500);
};

const saveChanges = () => {
    isSaving.value = true;
    setTimeout(() => {
        isSaving.value = false;
        triggerToast();
    }, 900);
};

const resetChanges = () => {
    activeProvider.value = 'r2';
    selectedProvider.value = 'r2';
    uploadPolicy.maxFileSizeMb = 10;
    imageOptimization.quality = 80;
    triggerToast('Settings reset to defaults');
};
</script>

<template>
    <SuperAdminLayout
        title="File System Settings"
        :breadcrumbs="[
            { label: 'SuperAdmin', href: '/superadmin' },
            { label: 'Settings', href: '/superadmin/settings' },
            { label: 'File System' },
        ]"
    >
        <Head title="File System Settings" />

        <div class="px-4 py-6 sm:px-8 max-w-7xl mx-auto">
            <!-- Toast Notification -->
            <Transition
                enter-active-class="transition ease-out duration-300 transform"
                enter-from-class="-translate-y-4 opacity-0 scale-95"
                enter-to-class="translate-y-0 opacity-100 scale-100"
                leave-active-class="transition ease-in duration-200 transform"
                leave-from-class="translate-y-0 opacity-100 scale-100"
                leave-to-class="-translate-y-4 opacity-0 scale-95"
            >
                <div
                    v-if="showSavedToast"
                    class="fixed top-5 right-5 z-50 flex items-center gap-3 rounded-xl border border-emerald-200 bg-white p-4 shadow-xl dark:border-emerald-800 dark:bg-zinc-900"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/80 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-zinc-900 dark:text-white">Saved Successfully</p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Storage and media policies have been applied globally.</p>
                    </div>
                </div>
            </Transition>

            <!-- 1. Settings Horizontal Sub-Navigation -->
            <SettingsNav current-section="file-system" />

            <!-- 2. Header Component -->
            <FileSystemHeader
                :active-provider-name="storageStatus.providerName"
                :is-encrypted="true"
            />

            <!-- 3. Current Storage Status Card -->
            <StorageStatusCard
                :status="storageStatus"
                :is-testing="isTestingFromTop"
                @test-connection="triggerConnectionTest"
                @scroll-to-config="scrollToConfig"
            />

            <!-- 4. Provider Selection Grid -->
            <StorageProviderCard
                :providers="providersList"
                :selected-provider="selectedProvider"
                :active-provider="activeProvider"
                @select-provider="handleSelectProvider"
                @set-active-provider="handleSetActiveProvider"
            />

            <!-- Configuration & Policies Main Body -->
            <div ref="configSectionRef" class="space-y-6">
                <!-- 5. Active Storage Provider Configuration Panel -->
                <StorageConfiguration
                    :provider-id="selectedProvider"
                    :local-config="localConfig"
                    :s3-config="s3Config"
                    :r2-config="r2Config"
                    :is-active-provider="selectedProvider === activeProvider"
                    @update:local-config="Object.assign(localConfig, $event)"
                    @update:s3-config="Object.assign(s3Config, $event)"
                    @update:r2-config="Object.assign(r2Config, $event)"
                    @set-active-provider="handleSetActiveProvider"
                />

                <!-- 6. File Upload Policy -->
                <StoragePolicy
                    v-model="uploadPolicy"
                />

                <!-- 7. Image Optimization & Compression -->
                <ImageOptimization
                    v-model="imageOptimization"
                />

                <!-- 8. File Naming & Organization -->
                <FileOrganization
                    v-model="fileOrganization"
                />

                <!-- 9. Security & Access Visibility -->
                <StorageSecurity
                    v-model="storageSecurity"
                />

                <!-- 10. Live Connection Diagnostic Probe -->
                <ConnectionTest
                    ref="connectionTestRef"
                    :provider-id="selectedProvider"
                    :provider-name="selectedProvider === 'r2' ? 'Cloudflare R2' : selectedProvider === 's3' ? 'Amazon S3' : 'Local Storage'"
                />

                <!-- 11. Storage Health Matrix -->
                <StorageHealth
                    :provider-id="activeProvider"
                />
            </div>

            <!-- 12. Bottom Sticky Action Bar -->
            <div class="sticky bottom-4 z-40 mt-8 rounded-2xl border border-zinc-200/90 bg-white/90 p-4 shadow-lg backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/90 flex items-center justify-between">
                <div class="hidden sm:flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Configuring: <strong class="text-zinc-800 dark:text-zinc-200 uppercase font-semibold">{{ selectedProvider }}</strong></span>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-xs text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                        @click="resetChanges"
                    >
                        Cancel
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="text-xs gap-1.5"
                        @click="triggerConnectionTest"
                    >
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Test Connection
                    </Button>

                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        class="text-xs gap-2 font-semibold"
                        :disabled="isSaving"
                        @click="saveChanges"
                    >
                        <svg
                            v-if="isSaving"
                            class="h-3.5 w-3.5 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ isSaving ? 'Saving...' : 'Save Changes' }}</span>
                    </Button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
