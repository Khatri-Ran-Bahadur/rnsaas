<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasFactory;

    protected $table = 'subscription_coupons';

    protected $fillable = [
        'public_id',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'float',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
            $model->code = strtoupper(trim($model->code));
        });
    }

    /**
     * Check if coupon is valid for use.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount against a given base price.
     */
    public function calculateDiscount(float $basePrice): float
    {
        if ($this->discount_type === 'percentage') {
            return round(($basePrice * $this->discount_value) / 100, 2);
        }

        return min($basePrice, (float) $this->discount_value);
    }
}
