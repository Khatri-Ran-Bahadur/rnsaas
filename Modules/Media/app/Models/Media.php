<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Tenancy\Models\Tenant;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'tenant_id',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'size',
        'directory_id',
        'created_by',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'url',
        'thumb_url',
        'human_size',
        'file_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(MediaDirectory::class, 'directory_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query->whereNull('tenant_id');
        }

        return $query->where('tenant_id', $tenantId);
    }

    public function scopePlatform(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->url('media/'.$this->file_name);
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->url;
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / (1024 ** $power), 2).' '.($units[$power] ?? 'B');
    }

    public function getFileTypeAttribute(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $ext = strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'])) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'])) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/') || in_array($ext, ['mp3', 'wav', 'ogg', 'aac', 'flac'])) {
            return 'audio';
        }

        if (
            in_array($mime, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
            ]) || in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'])
        ) {
            return 'document';
        }

        if (in_array($ext, ['zip', 'rar', 'tar', 'gz', '7z'])) {
            return 'archive';
        }

        return 'other';
    }
}
