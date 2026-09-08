<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\FiscalYearStatus;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'code',
    'name',
    'start_date',
    'end_date',
    'status',
    'is_current',
    'closed_at',
    'closed_by',
])]
#[Hidden([
    'id',
])]
class FiscalYear extends Model
{
    protected $table = 'fiscal_years';

    protected static function booted(): void
    {
        static::creating(function (self $fiscalYear): void {
            $fiscalYear->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => FiscalYearStatus::class,
            'is_current' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(
            'status',
            FiscalYearStatus::OPEN->value,
        );
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function isOpen(): bool
    {
        return $this->status === FiscalYearStatus::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === FiscalYearStatus::CLOSED;
    }
}
