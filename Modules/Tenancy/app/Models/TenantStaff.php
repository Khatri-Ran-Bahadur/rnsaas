<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Domain\Enums\EmploymentStatus;

class TenantStaff extends Model
{
    protected $table = 'tenant_staff';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'user_id',
        'branch_id',
        'department_id',
        'designation_id',
        'employee_code',
        'joining_date',
        'employment_status',
        'suspended_at',
        'terminated_at',
        'termination_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'employment_status' => EmploymentStatus::class,
            'suspended_at' => 'datetime',
            'terminated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TenantStaff $staff): void {
            $staff->public_id ??= (string) str()->uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function scopeForTenant(
        Builder $query,
        int $tenantId
    ): Builder {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'employment_status',
            EmploymentStatus::Active->value
        );
    }

    public function isActive(): bool
    {
        return $this->employment_status->isActive();
    }

    public function isSuspended(): bool
    {
        return $this->employment_status->isSuspended();
    }

    public function isTerminated(): bool
    {
        return $this->employment_status->isTerminated();
    }
}
