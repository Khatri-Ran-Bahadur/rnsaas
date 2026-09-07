<?php

namespace Modules\HRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\HRM\Domain\Enums\EmployeeDocumentStatus;
use Modules\HRM\Domain\Enums\EmployeeDocumentType;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

#[Fillable([
    'public_id',
    'tenant_id',
    'tenant_staff_id',
    'type',
    'title',
    'document_number',
    'issue_date',
    'expiry_date',
    'file_disk',
    'file_path',
    'original_file_name',
    'mime_type',
    'file_size',
    'status',
    'notes',
    'created_by',
    'updated_by',
    'verified_by',
    'verified_at',
    'is_active',
])]
#[Hidden([
    'file_path',
])]
class EmployeeDocument extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            if (! $document->public_id) {
                $document->public_id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => EmployeeDocumentType::class,
            'status' => EmployeeDocumentStatus::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'file_size' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(TenantStaff::class, 'tenant_staff_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForTenant(
        Builder $query,
        int $tenantId,
    ): Builder {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(
        Builder $query,
    ): Builder {
        return $query->where('is_active', true);
    }

    public function scopeVerified(
        Builder $query,
    ): Builder {
        return $query->where(
            'status',
            EmployeeDocumentStatus::VERIFIED,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null
            && $this->expiry_date->isPast();
    }

    public function isVerified(): bool
    {
        return $this->status === EmployeeDocumentStatus::VERIFIED;
    }

    public function isPending(): bool
    {
        return $this->status === EmployeeDocumentStatus::PENDING;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
