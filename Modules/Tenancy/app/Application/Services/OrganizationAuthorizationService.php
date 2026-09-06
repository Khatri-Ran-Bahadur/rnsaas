<?php

namespace Modules\Tenancy\Application\Services;

use App\Models\User;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;
use Modules\Tenancy\Domain\Services\OrganizationPermissionRegistry;
use Modules\Tenancy\Models\TenantMembership;

final class OrganizationAuthorizationService
{
    /**
     * In-memory request cache: [tenantId => [userId => array]]
     *
     * @var array<int, array<int, array{is_active: bool, is_admin: bool, role_id: ?int, permissions: array<string>}>>
     */
    private array $memoryCache = [];

    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    /**
     * Determine if the user has the given organization permission.
     */
    public function allows(?User $user, string $permission, ?int $tenantId = null): bool
    {
        if ($user === null) {
            return false;
        }

        $resolvedTenantId = $tenantId ?? ($this->currentTenant->has() ? $this->currentTenant->id() : null);

        if ($resolvedTenantId === null) {
            return false;
        }

        // SuperAdmin Impersonation Mode: Acts as organization Admin with full permissions
        if ($this->isImpersonatingTenant($user, $resolvedTenantId)) {
            return true;
        }

        $userData = $this->resolveUserData($resolvedTenantId, $user->id);

        if (! $userData['is_active']) {
            return false;
        }

        if ($userData['is_admin']) {
            return true;
        }

        return in_array($permission, $userData['permissions'], true);
    }

    /**
     * Authorize that the current user has the organization permission, or throw 403.
     *
     * @throws AuthorizationException
     */
    public function authorize(string $permission, ?int $tenantId = null): void
    {
        $user = auth()->user();

        if (! $this->allows($user, $permission, $tenantId)) {
            throw new AuthorizationException(
                "You do not have the required '{$permission}' permission for this organization.",
            );
        }
    }

    /**
     * Get all organization permissions for a user in a tenant.
     *
     * @return array<string>
     */
    public function getPermissions(?User $user, ?int $tenantId = null): array
    {
        if ($user === null) {
            return [];
        }

        $resolvedTenantId = $tenantId ?? ($this->currentTenant->has() ? $this->currentTenant->id() : null);

        if ($resolvedTenantId === null) {
            return [];
        }

        if ($this->isImpersonatingTenant($user, $resolvedTenantId)) {
            return OrganizationPermissionRegistry::all();
        }

        $userData = $this->resolveUserData($resolvedTenantId, $user->id);

        if (! $userData['is_active']) {
            return [];
        }

        if ($userData['is_admin']) {
            return OrganizationPermissionRegistry::all();
        }

        return $userData['permissions'];
    }

    /**
     * Determine if an ability should be handled as an organization permission.
     */
    public function isOrganizationAbility(string $ability): bool
    {
        if (! OrganizationPermissionRegistry::has($ability)) {
            return false;
        }

        // When a tenant is resolved in the current context, this organization permission is active
        return $this->currentTenant->has();
    }

    /**
     * Resolve membership and role permission data with request memory and Laravel cache.
     *
     * @return array{is_active: bool, is_admin: bool, role_id: ?int, permissions: array<string>}
     */
    public function resolveUserData(int $tenantId, int $userId): array
    {
        // Level 1: In-memory request cache
        if (isset($this->memoryCache[$tenantId][$userId])) {
            return $this->memoryCache[$tenantId][$userId];
        }

        // Level 2: Laravel Cache (tenant-scoped)
        $cacheKey = $this->cacheKey($tenantId, $userId);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            $this->memoryCache[$tenantId][$userId] = $cached;

            return $cached;
        }

        // Level 3: Database resolution
        $membership = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->with(['role.permissions'])
            ->first();

        if ($membership === null || $membership->status !== TenantMembershipStatus::Active) {
            $data = [
                'is_active' => false,
                'is_admin' => false,
                'role_id' => null,
                'permissions' => [],
            ];
        } elseif ($membership->role === null || ! $membership->role->is_active) {
            $data = [
                'is_active' => false,
                'is_admin' => false,
                'role_id' => $membership->role_id,
                'permissions' => [],
            ];
        } else {
            $isAdmin = $membership->role->is_system && $membership->role->slug === 'admin';
            $permissions = $isAdmin
                ? OrganizationPermissionRegistry::all()
                : $membership->role->permissions->pluck('permission')->toArray();

            $data = [
                'is_active' => true,
                'is_admin' => $isAdmin,
                'role_id' => $membership->role->id,
                'permissions' => $permissions,
            ];
        }

        Cache::put($cacheKey, $data, now()->addDay());
        $this->memoryCache[$tenantId][$userId] = $data;

        return $data;
    }

    /**
     * Invalidate cached permissions for a specific user in a tenant.
     */
    public function invalidateUser(int $tenantId, int $userId): void
    {
        unset($this->memoryCache[$tenantId][$userId]);
        Cache::forget($this->cacheKey($tenantId, $userId));
    }

    /**
     * Invalidate cached permissions for all members assigned to a role in a tenant.
     */
    public function invalidateRole(int $tenantId, int $roleId): void
    {
        unset($this->memoryCache[$tenantId]);

        $userIds = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            Cache::forget($this->cacheKey($tenantId, (int) $userId));
        }
    }

    /**
     * Invalidate entire tenant permission cache in memory.
     */
    public function invalidateTenant(int $tenantId): void
    {
        unset($this->memoryCache[$tenantId]);

        $userIds = TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            Cache::forget($this->cacheKey($tenantId, (int) $userId));
        }
    }

    /**
     * Clear all in-memory request cache.
     */
    public function clearMemoryCache(): void
    {
        $this->memoryCache = [];
    }

    /**
     * Build the tenant-aware cache key.
     */
    public function cacheKey(int $tenantId, int $userId): string
    {
        return "tenant:{$tenantId}:user:{$userId}:permissions";
    }

    private function isImpersonatingTenant(User $user, int $tenantId): bool
    {
        if (! request()->hasSession()) {
            return false;
        }

        $session = request()->session();
        $impersonatedTenantId = $session->get('impersonated_tenant_id');

        return $impersonatedTenantId !== null
            && (int) $impersonatedTenantId === $tenantId
            && $user->hasRole('SuperAdmin');
    }
}
