<?php

namespace Modules\HRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\HRM\Domain\Enums\LeaveStatus;
use Modules\HRM\Domain\Enums\LeaveType;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

#[Fillable([
    'public_id',
    'tenant_id',
    'tenant_staff_id',
    'leave_type',
    'start_date',
    'end_date',
    'total_days',
    'reason',
    'status',
    'created_by',
    'updated_by',
    'approved_by',
    'approved_at',
    'rejected_by',
    'rejected_at',
    'rejection_reason',
    'is_active',
])]
class LeaveRequest extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $leave): void {
            if (! $leave->public_id) {
                $leave->public_id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'leave_type' => LeaveType::class,
            'status' => LeaveStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(
            TenantStaff::class,
            'tenant_staff_id',
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopeForTenant(
        Builder $query,
        int $tenantId,
    ): Builder {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending(
        Builder $query,
    ): Builder {
        return $query->where(
            'status',
            LeaveStatus::PENDING,
        );
    }

    public function scopeApproved(
        Builder $query,
    ): Builder {
        return $query->where(
            'status',
            LeaveStatus::APPROVED,
        );
    }

    public function scopeRejected(
        Builder $query,
    ): Builder {
        return $query->where(
            'status',
            LeaveStatus::REJECTED,
        );
    }

    public function scopeActive(
        Builder $query,
    ): Builder {
        return $query->where('is_active', true);
    }

    public function isPending(): bool
    {
        return $this->status === LeaveStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === LeaveStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === LeaveStatus::REJECTED;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
