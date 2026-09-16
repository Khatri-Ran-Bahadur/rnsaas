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
    'payroll_group_id',
    'base_salary',
    'hourly_rate',
    'wage_type',
    'payment_method',
    'bank_name',
    'bank_account_number',
    'bank_account_name',
    'tax_status',
    'pan_number',
    'ssf_number',
    'cit_number',
    'custom_components',
    'is_active',
    'effective_from',
])]
class EmployeeSalaryProfile extends Model
{
    use HasFactory;

    protected $table = 'employee_salary_profiles';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'staff_id',
        'payroll_group_id',
        'base_salary',
        'hourly_rate',
        'wage_type',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'tax_status',
        'pan_number',
        'ssf_number',
        'cit_number',
        'custom_components',
        'is_active',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'custom_components' => 'array',
            'is_active' => 'boolean',
            'effective_from' => 'date',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(PayrollGroup::class, 'payroll_group_id');
    }

    public function payrollGroup(): BelongsTo
    {
        return $this->belongsTo(PayrollGroup::class, 'payroll_group_id');
    }
}
