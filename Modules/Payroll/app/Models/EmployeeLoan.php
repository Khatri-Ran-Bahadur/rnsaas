<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

#[Fillable([
    'public_id',
    'tenant_id',
    'staff_id',
    'loan_number',
    'loan_type',
    'principal_amount',
    'interest_rate',
    'total_repayable',
    'monthly_emi',
    'tenure_months',
    'paid_amount',
    'remaining_amount',
    'disbursed_at',
    'start_deduction_date',
    'status',
    'reason',
])]
class EmployeeLoan extends Model
{
    use HasFactory;

    protected $table = 'employee_loans';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'staff_id',
        'loan_number',
        'loan_type',
        'principal_amount',
        'interest_rate',
        'total_repayable',
        'monthly_emi',
        'tenure_months',
        'paid_amount',
        'remaining_amount',
        'disbursed_at',
        'start_deduction_date',
        'status',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'total_repayable' => 'decimal:2',
            'monthly_emi' => 'decimal:2',
            'tenure_months' => 'integer',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'disbursed_at' => 'date',
            'start_deduction_date' => 'date',
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

    public function staff(): BelongsTo
    {
        return $this->belongsTo(TenantStaff::class, 'staff_id');
    }
}
