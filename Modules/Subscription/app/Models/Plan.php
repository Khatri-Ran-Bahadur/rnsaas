<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property string $billing_cycle
 * @property int $trial_days
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'public_id',
    'name',
    'slug',
    'description',
    'price',
    'currency',
    'billing_cycle',
    'trial_days',
    'is_active',
    'sort_order',
    'metadata',
])]
class Plan extends Model
{
    protected $table = 'subscription_plans';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }


    
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            Feature::class,
            'subscription_plan_features',
        )->withPivot([
            'enabled',
            'limits',
            'metadata',
        ])->withTimestamps();
    }
}