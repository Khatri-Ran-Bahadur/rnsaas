<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\JournalLineType;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'journal_entry_id',
    'account_id',
    'line_number',
    'line_type',
    'amount',
    'description',
])]
#[Hidden([
    'id',
])]
class JournalLine extends Model
{
    protected $table = 'journal_lines';

    protected static function booted(): void
    {
        static::creating(function (self $line): void {
            $line->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'line_type' => JournalLineType::class,
            'amount' => 'decimal:6',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isDebit(): bool
    {
        return $this->line_type === JournalLineType::DEBIT;
    }

    public function isCredit(): bool
    {
        return $this->line_type === JournalLineType::CREDIT;
    }
}
