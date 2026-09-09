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
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'account_type_id',
    'account_group_id',
    'parent_id',
    'code',
    'name',
    'financial_statement_section',
    'description',
    'is_postable',
    'is_control_account',
    'is_system',
    'is_active',
    'sort_order',
])]
class Account extends Model
{
    use HasFactory;

    protected $table = 'accounting_accounts';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'is_postable' => 'boolean',
            'is_control_account' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'financial_statement_section' => FinancialStatementSection::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(
            AccountType::class,
            'account_type_id',
        );
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(
            AccountGroup::class,
            'account_group_id',
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id',
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id',
        );
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

    #[Scope]
    protected function postable(Builder $query): void
    {
        $query->where('is_postable', true);
    }

    public function isHeader(): bool
    {
        return ! $this->is_postable;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
