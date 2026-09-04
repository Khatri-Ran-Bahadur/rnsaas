<?php

return [
    'name' => 'Media',

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | The disk on which uploaded media files will be stored.
    |
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Max Upload Size
    |--------------------------------------------------------------------------
    |
    | Maximum upload size per file in kilobytes (default: 51200 KB = 50 MB).
    |
    */
    'max_upload_size' => env('MEDIA_MAX_UPLOAD_SIZE', 51200),

    /*
    |--------------------------------------------------------------------------
    | Allowed Mime Types
    |--------------------------------------------------------------------------
    */
    'allowed_mimes' => [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/avif',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        // Videos
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/gzip',
    ],
];
