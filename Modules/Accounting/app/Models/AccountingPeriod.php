<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\AccountingPeriodStatus;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'fiscal_year_id',
    'name',
    'period_number',
    'start_date',
    'end_date',
    'status',
    'closed_at',
    'closed_by',
])]
#[Hidden([
    'id',
])]
class AccountingPeriod extends Model
{
    protected $table = 'accounting_periods';

    protected static function booted(): void
    {
        static::creating(function (self $period): void {
            $period->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'period_number' => 'integer',
            'status' => AccountingPeriodStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(
            'status',
            AccountingPeriodStatus::OPEN->value,
        );
    }

    public function isOpen(): bool
    {
        return $this->status === AccountingPeriodStatus::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === AccountingPeriodStatus::CLOSED;
    }
}
