<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Accounting\Domain\Enums\AccountClassification;
use Modules\Accounting\Domain\Enums\AccountNormalBalance;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'code',
    'name',
    'classification',
    'normal_balance',
    'financial_statement',
    'financial_statement_section',
    'description',
    'is_system',
    'is_active',
    'sort_order',
])]
class AccountType extends Model
{
    use HasFactory;

    protected $table = 'accounting_account_types';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'classification' => AccountClassification::class,
            'normal_balance' => AccountNormalBalance::class,
            'financial_statement_section' => FinancialStatementSection::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(AccountGroup::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    #[Scope]
    protected function forTenant(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
