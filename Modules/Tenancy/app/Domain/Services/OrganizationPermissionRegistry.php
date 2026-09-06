<?php

namespace Modules\Tenancy\Domain\Services;

use Modules\Tenancy\Domain\Constants\OrganizationPermissions;

final class OrganizationPermissionRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $customModules = [];

    /**
     * Register a module's permissions.
     *
     * @param  array<string, mixed>  $moduleData
     */
    public static function register(string $moduleKey, array $moduleData): void
    {
        self::$customModules[$moduleKey] = $moduleData;
    }

    /**
     * Check if a permission is a registered organization permission.
     */
    public static function has(string $permission): bool
    {
        return in_array($permission, self::all(), true);
    }

    /**
     * Get all registered organization permission slugs.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        $permissions = OrganizationPermissions::all();

        foreach (self::$customModules as $module) {
            if (isset($module['permissions']) && is_array($module['permissions'])) {
                foreach ($module['permissions'] as $perm) {
                    $slug = is_array($perm) ? ($perm['name'] ?? null) : (string) $perm;
                    if ($slug && ! in_array($slug, $permissions, true)) {
                        $permissions[] = $slug;
                    }
                }
            }
        }

        return $permissions;
    }

    /**
     * Get structured tree for UI.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function structured(): array
    {
        $structured = OrganizationPermissions::structured();

        foreach (self::$customModules as $module) {
            $structured[] = $module;
        }

        return $structured;
    }

    /**
     * Reset registered custom modules (primarily for testing).
     */
    public static function reset(): void
    {
        self::$customModules = [];
    }
}
