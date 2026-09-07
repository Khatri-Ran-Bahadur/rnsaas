/**
 * Client-Side File Compression & Validation Engine
 *
 * Provides utilities for:
 * 1. Validating file extensions and maximum payload sizes.
 * 2. Compressing raster images (JPEG, PNG, WebP) using HTML5 Canvas before network transmission.
 * 3. Human-readable file size formatting.
 */

export interface CompressionOptions {
    maxWidth?: number;
    maxHeight?: number;
    quality?: number; // 0.1 to 1.0
    mimeType?: 'image/jpeg' | 'image/webp';
}

export interface ValidationRules {
    maxSizeMb?: number;
    allowedExtensions?: string[];
    allowedMimeTypes?: string[];
}

export interface ValidationResult {
    valid: boolean;
    error?: string;
}

export interface CompressionResult {
    file: File;
    originalSize: number;
    compressedSize: number;
    reductionPercentage: number;
    wasCompressed: boolean;
}

/**
 * Format bytes to a human-readable string (e.g., 2.45 MB, 320 KB).
 */
export function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
}

/**
 * Validate a file against extension, MIME type, and size constraints.
 */
export function validateFile(file: File, rules: ValidationRules = {}): ValidationResult {
    const {
        maxSizeMb = 10,
        allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        allowedMimeTypes,
    } = rules;

    // Check size
    const maxBytes = maxSizeMb * 1024 * 1024;
    if (file.size > maxBytes) {
        return {
            valid: false,
            error: `File size (${formatFileSize(file.size)}) exceeds the maximum allowed limit of ${maxSizeMb} MB.`,
        };
    }

    // Check extension
    const extension = file.name.split('.').pop()?.toLowerCase() || '';
    if (allowedExtensions.length > 0 && !allowedExtensions.map((e) => e.toLowerCase()).includes(extension)) {
        return {
            valid: false,
            error: `File format .${extension.toUpperCase()} is not supported. Allowed formats: ${allowedExtensions.map((e) => e.toUpperCase()).join(', ')}.`,
        };
    }

    // Check MIME type if specified
    if (allowedMimeTypes && allowedMimeTypes.length > 0 && !allowedMimeTypes.includes(file.type)) {
        return {
            valid: false,
            error: `File MIME type (${file.type}) is not allowed.`,
        };
    }

    return { valid: true };
}

/**
 * Compress an image file using an off-screen HTML5 Canvas.
 * Non-image files (such as PDFs) will bypass compression and return unchanged.
 */
export async function compressImage(
    file: File,
    options: CompressionOptions = {}
): Promise<CompressionResult> {
    const {
        maxWidth = 2048,
        maxHeight = 2048,
        quality = 0.82,
        mimeType = 'image/jpeg',
    } = options;

    const originalSize = file.size;

    // If it's not a compressable image (e.g. PDF or SVG), return original
    const isCompressable = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
    if (!isCompressable) {
        return {
            file,
            originalSize,
            compressedSize: originalSize,
            reductionPercentage: 0,
            wasCompressed: false,
        };
    }

    return new Promise((resolve) => {
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);

        img.onload = () => {
            URL.revokeObjectURL(objectUrl);

            let width = img.width;
            let height = img.height;

            // Calculate proportional dimensions
            if (width > height) {
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width = Math.round((width * maxHeight) / height);
                    height = maxHeight;
                }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return resolve({
                    file,
                    originalSize,
                    compressedSize: originalSize,
                    reductionPercentage: 0,
                    wasCompressed: false,
                });
            }

            // Fill white background for transparent PNGs converted to JPEG
            if (mimeType === 'image/jpeg') {
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
            }

            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob(
                (blob) => {
                    if (!blob || blob.size >= originalSize) {
                        // If compression didn't reduce size, keep the original file
                        return resolve({
                            file,
                            originalSize,
                            compressedSize: originalSize,
                            reductionPercentage: 0,
                            wasCompressed: false,
                        });
                    }

                    // Preserve original filename, adjust extension if converted
                    let fileName = file.name;
                    if (mimeType === 'image/jpeg' && !file.name.match(/\.(jpe?g)$/i)) {
                        fileName = file.name.replace(/\.[^/.]+$/, '') + '.jpg';
                    } else if (mimeType === 'image/webp' && !file.name.match(/\.webp$/i)) {
                        fileName = file.name.replace(/\.[^/.]+$/, '') + '.webp';
                    }

                    const compressedFile = new File([blob], fileName, {
                        type: mimeType,
                        lastModified: Date.now(),
                    });

                    const reductionPercentage = Math.round(
                        ((originalSize - blob.size) / originalSize) * 100
                    );

                    resolve({
                        file: compressedFile,
                        originalSize,
                        compressedSize: blob.size,
                        reductionPercentage,
                        wasCompressed: true,
                    });
                },
                mimeType,
                quality
            );
        };

        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            resolve({
                file,
                originalSize,
                compressedSize: originalSize,
                reductionPercentage: 0,
                wasCompressed: false,
            });
        };

        img.src = objectUrl;
    });
}
