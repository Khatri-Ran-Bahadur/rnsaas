<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantStaff;

#[Fillable([
    'public_id',
    'tenant_id',
    'payroll_run_id',
    'staff_id',
    'payslip_number',
    'period_start',
    'period_end',
    'pay_date',
    'base_salary',
    'gross_earnings',
    'total_deductions',
    'net_payable',
    'tax_deduction',
    'ssf_employee_deduction',
    'ssf_employer_contribution',
    'pf_employee_deduction',
    'pf_employer_contribution',
    'loan_deductions',
    'lop_deduction',
    'overtime_earnings',
    'working_days',
    'present_days',
    'absent_days',
    'leave_days',
    'overtime_hours',
    'payment_status',
    'paid_at',
    'payment_method',
    'payment_reference',
    'metadata',
])]
class Payslip extends Model
{
    use HasFactory;

    protected $table = 'payslips';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'payroll_run_id',
        'staff_id',
        'payslip_number',
        'period_start',
        'period_end',
        'pay_date',
        'base_salary',
        'gross_earnings',
        'total_deductions',
        'net_payable',
        'tax_deduction',
        'ssf_employee_deduction',
        'ssf_employer_contribution',
        'pf_employee_deduction',
        'pf_employer_contribution',
        'loan_deductions',
        'lop_deduction',
        'overtime_earnings',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'overtime_hours',
        'payment_status',
        'paid_at',
        'payment_method',
        'payment_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'pay_date' => 'date',
            'base_salary' => 'decimal:2',
            'gross_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_payable' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'ssf_employee_deduction' => 'decimal:2',
            'ssf_employer_contribution' => 'decimal:2',
            'pf_employee_deduction' => 'decimal:2',
            'pf_employer_contribution' => 'decimal:2',
            'loan_deductions' => 'decimal:2',
            'lop_deduction' => 'decimal:2',
            'overtime_earnings' => 'decimal:2',
            'working_days' => 'integer',
            'present_days' => 'decimal:2',
            'absent_days' => 'decimal:2',
            'leave_days' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
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

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(TenantStaff::class, 'staff_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class, 'payslip_id');
    }
}
