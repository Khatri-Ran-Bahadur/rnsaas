<?php

namespace Modules\HRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\HRM\database\factories\OvertimeFactory;
use Modules\HRM\Domain\Enums\OvertimeStatus;
use Modules\HRM\Domain\Enums\OvertimeType;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

/** @use HasFactory<OvertimeFactory> */
#[Fillable([
    'public_id',
    'tenant_id',
    'tenant_staff_id',
    'date',
    'start_time',
    'end_time',
    'total_minutes',
    'type',
    'status',
    'rate_multiplier',
    'reason',
    'created_by',
    'updated_by',
    'approved_by',
    'approved_at',
    'rejected_by',
    'rejected_at',
    'rejection_reason',
    'is_active',
])]
class Overtime extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => OvertimeType::class,
            'status' => OvertimeStatus::class,
            'total_minutes' => 'integer',
            'rate_multiplier' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Overtime $overtime): void {
            $overtime->public_id ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function setDateAttribute(mixed $value): void
    {
        $this->attributes['date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : (is_string($value) ? substr($value, 0, 10) : $value);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(
            TenantStaff::class,
            'tenant_staff_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rejected_by'
        );
    }

    #[Scope]
    protected function forTenant(
        Builder $query,
        int $tenantId
    ): void {
        $query->where('tenant_id', $tenantId);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where(
            'status',
            OvertimeStatus::PENDING
        );
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where(
            'status',
            OvertimeStatus::APPROVED
        );
    }

    #[Scope]
    protected function rejected(Builder $query): void
    {
        $query->where(
            'status',
            OvertimeStatus::REJECTED
        );
    }

    public function isPending(): bool
    {
        return $this->status === OvertimeStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === OvertimeStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === OvertimeStatus::REJECTED;
    }

    public function totalHours(): float
    {
        return round($this->total_minutes / 60, 2);
    }

    public function calculateMinutes(): int
    {
        $start = Carbon::parse(
            $this->date->toDateString().' '.$this->start_time
        );

        $end = Carbon::parse(
            $this->date->toDateString().' '.$this->end_time
        );

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }
}
