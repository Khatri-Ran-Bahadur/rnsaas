<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\TenantStatus;

class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'public_id',
        'name',
        'slug',
        'industry',
        'status',
        'country_code',
        'timezone',
        'locale',
        'currency',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
        ];
    }

    /**
     * Scope the query to active tenants.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope the query to a specific tenant status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Get all users who belong to this tenant.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'status',
                'joined_at',
                'invited_at',
                'suspended_at',
                'revoked_at',
                'invited_by',
                'suspended_by',
                'revoked_by',
                'settings',
                'version',
            ])
            ->withTimestamps();
    }

    /**
     * Get all subscriptions belonging to this tenant.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(
            TenantSubscription::class,
            'tenant_id',
        );
    }

    /**
     * Check if a specific module is enabled for this tenant.
     */
    public function isModuleEnabled(string $module): bool
    {
        // 1. Explicit module toggle in tenant settings
        if (is_array($this->settings) && isset($this->settings['modules'][$module])) {
            return (bool) $this->settings['modules'][$module];
        }

        // 2. Subscription plan feature verification if subscription exists
        if (class_exists(TenantSubscription::class)) {
            $activeSubscription = $this->subscriptions()
                ->whereIn('status', ['active', 'trialing'])
                ->with('plan.features')
                ->first();

            if ($activeSubscription && $activeSubscription->plan && $activeSubscription->plan->relationLoaded('features')) {
                $features = $activeSubscription->plan->features;
                if ($features->isNotEmpty()) {
                    return $features->contains(
                        fn ($f) => $f->module === $module || $f->slug === $module
                    );
                }
            }
        }

        return true;
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(TenantStaff::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(TenantRole::class);
    }
}
