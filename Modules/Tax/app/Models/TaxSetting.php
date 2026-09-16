<?php

namespace Modules\Tax\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Accounting\Models\Account;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'country',
    'tax_registration_number',
    'registered_business_name',
    'tax_authority_name',
    'tax_regime',
    'reporting_frequency',
    'accounting_method',
    'default_sales_tax_rate_id',
    'default_purchase_tax_rate_id',
    'output_tax_account_id',
    'input_tax_account_id',
    'withholding_tax_account_id',
    'tax_settlement_account_id',
    'default_pricing_mode',
    'allow_cashier_tax_override',
    'rounding_level',
    'rounding_precision',
    'rounding_direction',
    'display_tax_summary_on_invoices',
    'enable_einvoice_compliance',
])]
class TaxSetting extends Model
{
    use HasFactory;

    protected $table = 'tax_settings';

    protected function casts(): array
    {
        return [
            'allow_cashier_tax_override' => 'boolean',
            'display_tax_summary_on_invoices' => 'boolean',
            'enable_einvoice_compliance' => 'boolean',
            'rounding_precision' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function salesTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'default_sales_tax_rate_id');
    }

    public function purchaseTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'default_purchase_tax_rate_id');
    }

    public function outputTaxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'output_tax_account_id');
    }

    public function inputTaxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'input_tax_account_id');
    }

    public function withholdingTaxAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'withholding_tax_account_id');
    }

    public function taxSettlementAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'tax_settlement_account_id');
    }
}
