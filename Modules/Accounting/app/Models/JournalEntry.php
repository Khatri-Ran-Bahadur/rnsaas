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
use Modules\Accounting\Domain\Enums\JournalEntryStatus;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'fiscal_year_id',
    'accounting_period_id',
    'entry_number',
    'entry_date',
    'reference_type',
    'reference_id',
    'description',
    'status',
    'idempotency_key',
    'created_by',
    'posted_by',
    'posted_at',
    'reversed_by',
    'reverses_journal_entry_id',
])]
#[Hidden([
    'id',
])]
class JournalEntry extends Model
{
    protected $table = 'journal_entries';

    protected static function booted(): void
    {
        static::creating(function (self $journal): void {
            $journal->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'status' => JournalEntryStatus::class,
            'posted_at' => 'datetime',
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

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)
            ->orderBy('line_number');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reversed_by',
        );
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reverses_journal_entry_id',
        );
    }

    public function reversal(): HasMany
    {
        return $this->hasMany(
            self::class,
            'reverses_journal_entry_id',
        );
    }

    public function scopePosted(
        Builder $query,
    ): Builder {
        return $query->where(
            'status',
            JournalEntryStatus::POSTED->value,
        );
    }

    public function scopeDraft(
        Builder $query,
    ): Builder {
        return $query->where(
            'status',
            JournalEntryStatus::DRAFT->value,
        );
    }

    public function isDraft(): bool
    {
        return $this->status === JournalEntryStatus::DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === JournalEntryStatus::POSTED;
    }

    public function isReversed(): bool
    {
        return $this->status === JournalEntryStatus::REVERSED;
    }
}
