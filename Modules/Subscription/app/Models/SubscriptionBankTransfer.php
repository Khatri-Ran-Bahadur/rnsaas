<?php

namespace Modules\Subscription\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

class SubscriptionBankTransfer extends Model
{
    use HasFactory;

    protected $table = 'subscription_bank_transfers';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'user_id',
        'plan_id',
        'branches_count',
        'base_amount',
        'extra_branches_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'billing_cycle',
        'transaction_reference',
        'receipt_path',
        'notes',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'branches_count' => 'integer',
            'base_amount' => 'decimal:2',
            'extra_branches_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::ulid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
