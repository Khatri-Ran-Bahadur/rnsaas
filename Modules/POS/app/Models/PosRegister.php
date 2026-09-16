<?php

namespace Modules\POS\Models;

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
    'code',
    'name',
    'location',
    'receipt_printer_ip',
    'is_active',
])]
class PosRegister extends Model
{
    use HasFactory;

    protected $table = 'pos_registers';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(PosShift::class, 'register_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'register_id');
    }
}
