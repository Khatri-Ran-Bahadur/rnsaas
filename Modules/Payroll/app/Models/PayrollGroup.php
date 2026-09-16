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
    'name',
    'code',
    'cycle',
    'pay_day',
    'is_active',
])]
class PayrollGroup extends Model
{
    use HasFactory;

    protected $table = 'payroll_groups';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'pay_day' => 'integer',
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

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class, 'payroll_group_id');
    }

    public function salaryProfiles(): HasMany
    {
        return $this->hasMany(EmployeeSalaryProfile::class, 'payroll_group_id');
    }
}
