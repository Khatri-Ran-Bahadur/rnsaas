<script setup lang="ts">
import { ref } from 'vue';
import type {
    StorageProviderId,
    LocalStorageConfig,
    S3StorageConfig,
    R2StorageConfig,
} from '@/types/superadmin/file-system';
import Button from '@/components/Button.vue';

const props = defineProps<{
    providerId: StorageProviderId;
    localConfig: LocalStorageConfig;
    s3Config: S3StorageConfig;
    r2Config: R2StorageConfig;
    isActiveProvider: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:localConfig', val: LocalStorageConfig): void;
    (e: 'update:s3Config', val: S3StorageConfig): void;
    (e: 'update:r2Config', val: R2StorageConfig): void;
    (e: 'set-active-provider', id: StorageProviderId): void;
}>();

const showS3Secret = ref(false);
const showR2Secret = ref(false);

const awsRegions = [
    { value: 'us-east-1', label: 'US East (N. Virginia) us-east-1' },
    { value: 'us-east-2', label: 'US East (Ohio) us-east-2' },
    { value: 'us-west-1', label: 'US West (N. California) us-west-1' },
    { value: 'us-west-2', label: 'US West (Oregon) us-west-2' },
    { value: 'eu-west-1', label: 'EU (Ireland) eu-west-1' },
    { value: 'eu-central-1', label: 'EU (Frankfurt) eu-central-1' },
    { value: 'ap-southeast-1', label: 'Asia Pacific (Singapore) ap-southeast-1' },
    { value: 'ap-south-1', label: 'Asia Pacific (Mumbai) ap-south-1' },
];
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 mb-8">
        <!-- Header & Action to make Active Provider -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-zinc-100 dark:border-zinc-800">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                        <span v-if="providerId === 'local'">Local Storage Configuration</span>
                        <span v-else-if="providerId === 's3'">Amazon S3 Storage Configuration</span>
                        <span v-else-if="providerId === 'r2'">Cloudflare R2 Storage Configuration</span>
                    </h3>
                    <span
                        v-if="isActiveProvider"
                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800"
                    >
                        Active Default
                    </span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    <span v-if="providerId === 'local'">Files are stored locally on the application web server disk storage.</span>
                    <span v-else-if="providerId === 's3'">Scalable object storage hosted in Amazon Web Services S3 buckets.</span>
                    <span v-else-if="providerId === 'r2'">Zero-egress fee S3-compatible cloud storage hosted in Cloudflare R2.</span>
                </p>
            </div>

            <div>
                <Button
                    v-if="!isActiveProvider"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="gap-1.5 text-xs font-semibold text-emerald-700 border-emerald-200 hover:bg-emerald-50 dark:text-emerald-400 dark:border-emerald-800 dark:hover:bg-emerald-950/40"
                    @click="emit('set-active-provider', providerId)"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Set as Active Storage
                </Button>
                <div v-else class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Active Global Storage Provider
                </div>
            </div>
        </div>

        <!-- 1. LOCAL STORAGE FORM -->
        <div v-if="providerId === 'local'" class="pt-6 space-y-6">
            <div class="rounded-xl bg-amber-50/60 p-4 border border-amber-200/70 text-xs text-amber-800 dark:bg-amber-950/40 dark:border-amber-900/60 dark:text-amber-300 flex items-start gap-3">
                <svg class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <span class="font-bold">Files are stored on the application server.</span>
                    <p class="mt-0.5 text-amber-700/90 dark:text-amber-300/80">
                        Local disk storage is ideal for single-instance deployments and development. For multi-server or auto-scaling clusters, cloud object storage (S3 or R2) is recommended.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Storage Name
                    </label>
                    <input
                        v-model="localConfig.storageName"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="e.g., local-primary"
                    />
                    <p class="text-[11px] text-zinc-400 mt-1">Identifier used within the internal media engine.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Storage Path (Absolute or Root-relative)
                    </label>
                    <input
                        v-model="localConfig.storagePath"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="storage/app/uploads"
                    />
                    <p class="text-[11px] text-zinc-400 mt-1">Directory where uploaded tenant and system files reside.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Default Storage Visibility
                    </label>
                    <select
                        v-model="localConfig.visibility"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                        <option value="private">Private (Restricted via Signed Endpoints)</option>
                        <option value="public">Public (Direct Web Access)</option>
                    </select>
                    <p class="text-[11px] text-zinc-400 mt-1">Private is strictly recommended for enterprise tenant confidentiality.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Maximum File Size (MB)
                    </label>
                    <input
                        v-model.number="localConfig.maxFileSizeMb"
                        type="number"
                        min="1"
                        max="500"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    />
                    <p class="text-[11px] text-zinc-400 mt-1">Hard cap per single file upload for Local storage.</p>
                </div>
            </div>
        </div>

        <!-- 2. AMAZON S3 FORM -->
        <div v-else-if="providerId === 's3'" class="pt-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Storage Name
                    </label>
                    <input
                        v-model="s3Config.storageName"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="s3-production"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Bucket Name
                    </label>
                    <input
                        v-model="s3Config.bucket"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="sathisaas-production-media"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        AWS Region
                    </label>
                    <select
                        v-model="s3Config.region"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                        <option v-for="r in awsRegions" :key="r.value" :value="r.value">
                            {{ r.label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Folder / Prefix
                    </label>
                    <input
                        v-model="s3Config.folderPrefix"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="e.g., uploads"
                    />
                    <p class="text-[11px] text-zinc-400 mt-1">Root prefix directory within the S3 bucket.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        AWS Access Key ID
                    </label>
                    <input
                        v-model="s3Config.accessKey"
                        type="text"
                        autocomplete="off"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="AKIAIOSFODNN7EXAMPLE"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        AWS Secret Access Key
                    </label>
                    <div class="relative">
                        <input
                            v-model="s3Config.secretKey"
                            :type="showS3Secret ? 'text' : 'password'"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-10 font-mono text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            placeholder="••••••••••••••••••••••••••••••••"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                            @click="showS3Secret = !showS3Secret"
                        >
                            <svg v-if="!showS3Secret" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Custom Endpoint (Optional)
                    </label>
                    <input
                        v-model="s3Config.endpoint"
                        type="url"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="https://s3.custom-domain.com or leave blank for AWS standard"
                    />
                    <p class="text-[11px] text-zinc-400 mt-1">Leave blank unless utilizing MinIO, Wasabi, or a private VPC endpoint.</p>
                </div>
            </div>
        </div>

        <!-- 3. CLOUDFLARE R2 FORM -->
        <div v-else-if="providerId === 'r2'" class="pt-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Storage Name
                    </label>
                    <input
                        v-model="r2Config.storageName"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="cloudflare-r2-primary"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Cloudflare Account ID
                    </label>
                    <input
                        v-model="r2Config.accountId"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="e.g., 9a2b8c3d4e5f60718293a4b5c6d7e8f9"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        R2 Bucket Name
                    </label>
                    <input
                        v-model="r2Config.bucket"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="sathisaas-r2-vault"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Folder / Root Prefix
                    </label>
                    <input
                        v-model="r2Config.folderPrefix"
                        type="text"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="e.g., platform-assets"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        R2 Access Key ID
                    </label>
                    <input
                        v-model="r2Config.accessKeyId"
                        type="text"
                        autocomplete="off"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="e.g., 6739402948bc8f..."
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        R2 Secret Access Key
                    </label>
                    <div class="relative">
                        <input
                            v-model="r2Config.secretAccessKey"
                            :type="showR2Secret ? 'text' : 'password'"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-10 font-mono text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                            placeholder="••••••••••••••••••••••••••••••••"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer"
                            @click="showR2Secret = !showR2Secret"
                        >
                            <svg v-if="!showR2Secret" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                        R2 S3-Compatible API Endpoint URL
                    </label>
                    <input
                        v-model="r2Config.endpoint"
                        type="url"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 shadow-2xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        placeholder="https://<ACCOUNT_ID>.r2.cloudflarestorage.com"
                    />
                    <p class="text-[11px] text-zinc-400 mt-1">Direct endpoint generated in Cloudflare R2 bucket settings.</p>
                </div>
            </div>
        </div>
    </div>
</template>
