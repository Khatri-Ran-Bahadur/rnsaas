<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'payroll_group_id',
    'run_number',
    'period_name',
    'start_date',
    'end_date',
    'pay_date',
    'status',
    'total_employees',
    'gross_amount',
    'deductions_amount',
    'net_amount',
    'employer_statutory',
    'created_by',
    'approved_by',
])]
class PayrollRun extends Model
{
    use HasFactory;

    protected $table = 'payroll_runs';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'payroll_group_id',
        'run_number',
        'period_name',
        'start_date',
        'end_date',
        'pay_date',
        'status',
        'total_employees',
        'gross_amount',
        'deductions_amount',
        'net_amount',
        'employer_statutory',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'pay_date' => 'date',
            'total_employees' => 'integer',
            'gross_amount' => 'decimal:2',
            'deductions_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'employer_statutory' => 'decimal:2',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(PayrollGroup::class, 'payroll_group_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'payroll_run_id');
    }
}
