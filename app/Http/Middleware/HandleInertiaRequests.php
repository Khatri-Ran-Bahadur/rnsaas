<?php

namespace App\Http\Middleware;

use App\Support\ReferenceData;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Media\Models\Media;
use Modules\Subscription\Models\SubscriptionBankTransfer;
use Modules\SuperAdmin\Services\PlatformSettings;
use Modules\Tenancy\Application\Services\OrganizationAuthorizationService;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMembership;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if ($request->is('install*', 'update*') || ! file_exists(storage_path('installed'))) {
            return [
                'name' => config('app.name'),
                'csrf_token' => csrf_token(),
                'auth' => [
                    'user' => null,
                ],
            ];
        }

        $userLocale = null;
        try {
            $userLocale = $request->user()?->locale;
        } catch (\Throwable) {
        }

        $activeLocale = $request->session()->get('locale')
            ?? $userLocale
            ?? config('app.locale', 'en');

        app()->setLocale($activeLocale);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'csrf_token' => csrf_token(),
            'locale' => $activeLocale,
            'supported_locales' => ReferenceData::locales(),
            'auth' => [
                'user' => function () use ($request) {
                    try {
                        return $request->user();
                    } catch (\Throwable) {
                        return null;
                    }
                },
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'platform' => $this->platformBranding(),
            'current_tenant' => function () use ($request) {
                try {
                    $currentTenant = app(CurrentTenant::class);
                    if (! $currentTenant->has()) {
                        return null;
                    }

                    $tenant = $currentTenant->get();
                    $user = $request->user();

                    $activeSub = $tenant->subscriptions()
                        ->where('status', 'active')
                        ->where(function ($query) {
                            $query->whereNull('current_period_ends_at')
                                ->orWhere('current_period_ends_at', '>=', now());
                        })
                        ->with('plan')
                        ->first();

                    $latestSub = $tenant->subscriptions()->latest('id')->with('plan')->first();
                    $hasPendingTransfer = SubscriptionBankTransfer::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('status', 'pending')
                        ->exists();

                    $isSubscribed = ($activeSub !== null);

                    $statusString = 'none';
                    if ($activeSub) {
                        $statusString = 'active';
                    } elseif ($hasPendingTransfer || ($latestSub && ($latestSub->status === 'pending' || (is_object($latestSub->status) && $latestSub->status->value === 'pending')))) {
                        $statusString = 'pending_approval';
                    } elseif ($latestSub) {
                        $statusString = is_object($latestSub->status) ? $latestSub->status->value : (string) $latestSub->status;
                    }

                    $curr = strtoupper((string) ($tenant->currency ?? 'USD'));
                    $currencySymbol = match ($curr) {
                        'USD' => '$',
                        'EUR' => '€',
                        'GBP' => '£',
                        'INR' => '₹',
                        'NPR' => 'रू',
                        'MYR' => 'RM',
                        'CAD' => 'CA$',
                        'AUD' => 'A$',
                        'SGD' => 'S$',
                        'AED' => 'AED',
                        'SAR' => 'SAR',
                        'QAR' => 'QAR',
                        'JPY', 'CNY' => '¥',
                        default => $curr,
                    };

                    return [
                        'id' => $tenant->id,
                        'public_id' => $tenant->public_id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'status' => $tenant->status instanceof TenantStatus ? $tenant->status->value : (string) $tenant->status,
                        'timezone' => $tenant->timezone,
                        'currency' => $tenant->currency ?: 'USD',
                        'currency_symbol' => $currencySymbol,
                        'country_code' => $tenant->country_code ?: 'US',
                        'locale' => $tenant->locale ?: app()->getLocale(),
                        'industry' => $tenant->industry,
                        'is_hospitality' => in_array(strtolower((string) $tenant->industry), ['restaurant', 'food_beverage', 'hospitality', 'hotel']),
                        'is_subscribed' => $isSubscribed,
                        'subscription_status' => $statusString,
                        'plan_name' => $activeSub?->plan?->name ?? ($latestSub?->plan?->name ?? null),
                        'modules' => [
                            'accounting' => method_exists($tenant, 'isModuleEnabled') ? $tenant->isModuleEnabled('accounting') : true,
                            'mrp' => method_exists($tenant, 'isModuleEnabled') ? $tenant->isModuleEnabled('mrp') : true,
                            'pos' => method_exists($tenant, 'isModuleEnabled') ? $tenant->isModuleEnabled('pos') : true,
                            'inventory' => method_exists($tenant, 'isModuleEnabled') ? $tenant->isModuleEnabled('inventory') : true,
                            'hrm' => method_exists($tenant, 'isModuleEnabled') ? $tenant->isModuleEnabled('hrm') : true,
                            'payroll' => method_exists($tenant, 'isModuleEnabled') ? $tenant->isModuleEnabled('payroll') : true,
                            'tax' => method_exists($tenant, 'isModuleEnabled') ? $tenant->isModuleEnabled('tax') : true,
                        ],
                    ];
                } catch (\Throwable) {
                    return null;
                }
            },
            'user_tenants' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return [];
                }

                return $user->tenants()
                    ->wherePivot('status', 'active')
                    ->get(['tenants.id', 'tenants.public_id', 'tenants.name', 'tenants.slug'])
                    ->toArray();
            },
            'impersonation' => function () use ($request) {
                $tenantId = $request->session()->get('impersonated_tenant_id');
                if (! $tenantId) {
                    return null;
                }

                $tenant = Tenant::query()->find($tenantId);

                return [
                    'is_impersonating' => true,
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant?->name ?? 'Organization',
                    'by_user_id' => $request->session()->get('impersonated_by_user_id'),
                ];
            },
            'current_membership' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return null;
                }

                try {
                    $currentTenant = app(CurrentTenant::class);
                    if (! $currentTenant->has()) {
                        return null;
                    }
                    $tenantId = $currentTenant->id();

                    $authService = app(OrganizationAuthorizationService::class);
                    $permissions = $authService->getPermissions($user, $tenantId);

                    // Support SuperAdmin impersonation mode
                    $impersonatedTenantId = $request->session()->get('impersonated_tenant_id');
                    if ($impersonatedTenantId !== null && (int) $impersonatedTenantId === $tenantId && $user->hasRole('SuperAdmin')) {
                        return [
                            'id' => 0,
                            'status' => 'active',
                            'role' => [
                                'id' => 0,
                                'name' => 'Admin',
                                'slug' => 'admin',
                                'is_system' => true,
                            ],
                            'permissions' => $permissions,
                            'is_admin' => true,
                        ];
                    }

                    $userData = $authService->resolveUserData($tenantId, $user->id);

                    if (! $userData['is_active']) {
                        return null;
                    }

                    $membership = TenantMembership::query()
                        ->where('tenant_id', $tenantId)
                        ->where('user_id', $user->id)
                        ->with(['role'])
                        ->first();

                    if (! $membership) {
                        return null;
                    }

                    return [
                        'id' => $membership->id,
                        'status' => $membership->status->value,
                        'role' => $membership->role ? [
                            'id' => $membership->role->id,
                            'name' => $membership->role->name,
                            'slug' => $membership->role->slug,
                            'is_system' => $membership->role->is_system,
                        ] : null,
                        'permissions' => $permissions,
                        'is_admin' => $userData['is_admin'],
                    ];
                } catch (\Throwable) {
                    return null;
                }
            },
            'pending_bank_transfers_count' => function () use ($request) {
                try {
                    $user = $request->user();
                    if ($user && $user->hasRole('SuperAdmin')) {
                        return SubscriptionBankTransfer::query()
                            ->where('status', 'pending')
                            ->count();
                    }
                } catch (\Throwable) {
                    return 0;
                }

                return 0;
            },
        ];
    }

    /**
     * Resolve platform branding settings for global Inertia props.
     *
     * @return array{name: string, logo_url: ?string, favicon_url: ?string}
     */
    private function platformBranding(): array
    {
        try {
            if (! class_exists(PlatformSettings::class)) {
                return [
                    'name' => config('app.name', 'SathiSaaS'),
                    'logo_url' => null,
                    'favicon_url' => null,
                ];
            }

            /** @var PlatformSettings $settings */
            $settings = app(PlatformSettings::class);
            $branding = $settings->group('branding');
            $general = $settings->group('general');

            $logoUrl = $branding['logo_url'] ?? null;
            if (! empty($branding['logo_media_id']) && class_exists(Media::class)) {
                $media = Media::query()->find($branding['logo_media_id']);
                if ($media) {
                    $logoUrl = $media->url;
                }
            }

            $faviconUrl = $branding['favicon_url'] ?? null;
            if (! empty($branding['favicon_media_id']) && class_exists(Media::class)) {
                $media = Media::query()->find($branding['favicon_media_id']);
                if ($media) {
                    $faviconUrl = $media->url;
                }
            }

            return [
                'name' => $general['platform_name'] ?? config('app.name', 'SathiSaaS'),
                'logo_url' => $logoUrl,
                'favicon_url' => $faviconUrl,
            ];
        } catch (\Throwable) {
            return [
                'name' => config('app.name', 'SathiSaaS'),
                'logo_url' => null,
                'favicon_url' => null,
            ];
        }
    }
}
