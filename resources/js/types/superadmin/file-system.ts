export type StorageProviderId = 'local' | 's3' | 'r2';

export type StorageVisibility = 'private' | 'public';

export type FileNamingStrategy = 'uuid' | 'original' | 'uuid_original';

export type DirectoryStructure = 'tenant' | 'module' | 'year_month' | 'tenant_module_date';

export type ImageConvertFormat = 'original' | 'webp' | 'jpeg';

export type ConnectionStatus = 'idle' | 'testing' | 'connected' | 'failed';

export interface StorageProviderInfo {
    id: StorageProviderId;
    name: string;
    description: string;
    badgeText: string;
    isConfigured: boolean;
    isActive: boolean;
    icon: string;
    statusText: string;
}

export interface LocalStorageConfig {
    storageName: string;
    storagePath: string;
    visibility: StorageVisibility;
    maxFileSizeMb: number;
    allowedFileTypes: string[];
}

export interface S3StorageConfig {
    storageName: string;
    bucket: string;
    region: string;
    accessKey: string;
    secretKey: string;
    endpoint: string;
    folderPrefix: string;
    visibility: StorageVisibility;
    maxFileSizeMb: number;
    allowedFileTypes: string[];
}

export interface R2StorageConfig {
    storageName: string;
    accountId: string;
    bucket: string;
    accessKeyId: string;
    secretAccessKey: string;
    endpoint: string;
    folderPrefix: string;
    visibility: StorageVisibility;
    maxFileSizeMb: number;
    allowedFileTypes: string[];
}

export interface FileUploadPolicyConfig {
    maxFileSizeMb: number;
    allowedFileTypes: string[];
}

export interface ImageOptimizationConfig {
    enabled: boolean;
    quality: number;
    maxWidth: number;
    maxHeight: number;
    convertFormat: ImageConvertFormat;
    generateThumbnail: boolean;
    thumbnailWidth: number;
    thumbnailHeight: number;
}

export interface FileOrganizationConfig {
    namingStrategy: FileNamingStrategy;
    directoryStructure: DirectoryStructure;
}

export interface StorageSecurityConfig {
    defaultVisibility: StorageVisibility;
    enforceSignedUrls: boolean;
    signedUrlExpiryMinutes: number;
}

export interface StorageHealthItem {
    name: string;
    status: 'operational' | 'supported' | 'enabled' | 'connected' | 'warning';
    description: string;
}

export interface CurrentStorageStatus {
    activeProvider: StorageProviderId;
    providerName: string;
    isConnected: boolean;
    isDefault: boolean;
    usedStorage: string;
    quotaStorage: string;
    quotaPercent: number;
    fileCount: string;
    lastTested: string;
}

export interface FileSystemSettingsState {
    activeProvider: StorageProviderId;
    providers: Record<StorageProviderId, LocalStorageConfig | S3StorageConfig | R2StorageConfig>;
    uploadPolicy: FileUploadPolicyConfig;
    imageOptimization: ImageOptimizationConfig;
    fileOrganization: FileOrganizationConfig;
    security: StorageSecurityConfig;
}
