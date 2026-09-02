<?php

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $module
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'public_id',
    'name',
    'slug',
    'description',
    'module',
    'is_active',
    'sort_order',
    'metadata',
])]
class Feature extends Model
{
    protected $table = 'subscription_features';

    protected function casts(): array
    {
        return [
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

    #[Scope]
    protected function forModule(
        Builder $query,
        string $module,
    ): void {
        $query->where('module', $module);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            Plan::class,
            'subscription_plan_features',
        )->withPivot([
            'enabled',
            'limits',
            'metadata',
        ])->withTimestamps();
    }
}