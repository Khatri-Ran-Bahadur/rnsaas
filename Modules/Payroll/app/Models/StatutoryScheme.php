<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'country_code',
    'name',
    'scheme_type',
    'employee_rate',
    'employer_rate',
    'tax_slabs',
    'exemption_limits',
    'is_active',
    'is_default',
])]
class StatutoryScheme extends Model
{
    use HasFactory;

    protected $table = 'statutory_schemes';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'country_code',
        'name',
        'scheme_type',
        'employee_rate',
        'employer_rate',
        'tax_slabs',
        'exemption_limits',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'employee_rate' => 'decimal:2',
            'employer_rate' => 'decimal:2',
            'tax_slabs' => 'array',
            'exemption_limits' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
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
}
