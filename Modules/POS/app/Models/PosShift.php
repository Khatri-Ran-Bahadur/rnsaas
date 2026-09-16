<?php

namespace Modules\POS\Models;

use App\Models\User;
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
    'register_id',
    'user_id',
    'shift_number',
    'terminal_name',
    'opened_at',
    'closed_at',
    'opening_float',
    'cash_sales',
    'card_sales',
    'qr_sales',
    'total_sales',
    'cash_in',
    'cash_out',
    'expected_cash',
    'actual_cash',
    'variance',
    'transaction_count',
    'status',
    'notes',
    'verified_by',
])]
class PosShift extends Model
{
    use HasFactory;

    protected $table = 'pos_shifts';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_float' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'card_sales' => 'decimal:2',
            'qr_sales' => 'decimal:2',
            'total_sales' => 'decimal:2',
            'cash_in' => 'decimal:2',
            'cash_out' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'variance' => 'decimal:2',
            'transaction_count' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'register_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'shift_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'shift_id');
    }
}
