<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Media\Models\Media;
use Modules\SuperAdmin\Services\PlatformSettings;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;

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
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'platform' => $this->platformBranding(),
            'current_tenant' => function () {
                try {
                    $currentTenant = app(CurrentTenant::class);
                    if (! $currentTenant->has()) {
                        return null;
                    }

                    $tenant = $currentTenant->get();

                    return [
                        'id' => $tenant->id,
                        'public_id' => $tenant->public_id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'status' => $tenant->status instanceof TenantStatus ? $tenant->status->value : (string) $tenant->status,
                        'timezone' => $tenant->timezone,
                        'currency' => $tenant->currency,
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
