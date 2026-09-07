<?php

namespace Modules\HRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\HRM\Domain\Enums\AttendanceSource;
use Modules\HRM\Domain\Enums\AttendanceStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

#[Fillable([
    'public_id',
    'tenant_id',
    'tenant_staff_id',
    'attendance_date',
    'check_in',
    'check_out',
    'worked_minutes',
    'late_minutes',
    'early_leave_minutes',
    'overtime_minutes',
    'status',
    'source',
    'notes',
    'created_by',
    'updated_by',
    'is_active',
])]
class Attendance extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $attendance): void {
            if (! $attendance->public_id) {
                $attendance->public_id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'status' => AttendanceStatus::class,
            'source' => AttendanceSource::class,
            'worked_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'overtime_minutes' => 'integer',
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

    public function scopeForDate(
        Builder $query,
        string $date,
    ): Builder {
        return $query->whereDate('attendance_date', $date);
    }

    public function isPresent(): bool
    {
        return $this->status === AttendanceStatus::PRESENT;
    }

    public function isLate(): bool
    {
        return $this->status === AttendanceStatus::LATE;
    }

    public function workedHours(): float
    {
        return round($this->worked_minutes / 60, 2);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
