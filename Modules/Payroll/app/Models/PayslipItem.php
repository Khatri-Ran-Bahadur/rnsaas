<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'payslip_id',
    'payroll_component_id',
    'name',
    'code',
    'type',
    'amount',
    'is_taxable',
    'calculation_details',
])]
class PayslipItem extends Model
{
    use HasFactory;

    protected $table = 'payslip_items';

    protected $fillable = [
        'tenant_id',
        'payslip_id',
        'payroll_component_id',
        'name',
        'code',
        'type',
        'amount',
        'is_taxable',
        'calculation_details',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_taxable' => 'boolean',
            'calculation_details' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}
