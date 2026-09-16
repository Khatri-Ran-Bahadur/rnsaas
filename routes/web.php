<?php

use App\Http\Controllers\PageViewerController;
use App\Support\ReferenceData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Subscription\Http\Controllers\CouponController;
use Modules\Subscription\Models\Plan;
use Modules\SuperAdmin\Http\Controllers\DemoRequestController;
use Modules\SuperAdmin\Models\CustomPage;
use Modules\SuperAdmin\Services\PlatformSettings;

Route::get('/', function () {
    $plans = Plan::where('is_active', true)
        ->with('features')
        ->orderBy('sort_order')
        ->get()
        ->map(function (Plan $plan): array {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => (float) $plan->price,
                'currency' => $plan->currency,
                'billing_cycle' => $plan->billing_cycle?->value ?? 'monthly',
                'trial_days' => (int) $plan->trial_days,
                'is_popular' => $plan->slug === 'business',
                'features' => $plan->features->map(fn ($f): array => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'slug' => $f->slug,
                    'description' => $f->description,
                ])->all(),
            ];
        });

    $headerPages = class_exists(CustomPage::class)
        ? CustomPage::where('is_published', true)
            ->where('show_in_header', true)
            ->orderBy('sort_order')
            ->select('id', 'title', 'slug')
            ->get()
        : collect();

    $footerPages = class_exists(CustomPage::class)
        ? CustomPage::where('is_published', true)
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->select('id', 'title', 'slug')
            ->get()
        : collect();

    $general = [];
    $branding = [];
    if (class_exists(PlatformSettings::class)) {
        try {
            $settingsService = app(PlatformSettings::class);
            $general = $settingsService->group('general');
            $branding = $settingsService->group('branding');
        } catch (Throwable) {
        }
    }

    $footerSettings = [
        'platform_name' => $general['platform_name'] ?? config('app.name', 'SathiSaaS'),
        'support_email' => $general['support_email'] ?? 'support@sathisaas.com',
        'support_phone' => $general['support_phone'] ?? '+1 (555) 019-2834',
        'address' => $general['company_address'] ?? 'Global Cloud Headquarters',
        'tagline' => $general['platform_tagline'] ?? 'Unified Enterprise ERP & Multi-Tenant Operating Platform',
        'copyright' => '© '.date('Y').' '.($general['platform_name'] ?? 'SathiSaaS').' Technologies Inc. All rights reserved.',
        'logo_url' => $branding['logo_url'] ?? null,
    ];

    return Inertia::render('Welcome', [
        'plans' => $plans,
        'headerPages' => $headerPages,
        'footerPages' => $footerPages,
        'footerSettings' => $footerSettings,
    ]);
})->name('home');

// Public CMS Dynamic Pages
Route::get('/page/{slug}', [PageViewerController::class, 'show'])->name('pages.show');

// Public Request Demo submission
Route::post('/demo-request', [DemoRequestController::class, 'storePublic'])->name('demo-request.store');

// Public Coupon Code validation
Route::post('/api/coupons/validate', [CouponController::class, 'validateCode'])->name('coupons.validate');

Route::middleware(['auth'])->get('/profile', function (Request $request) {
    if ($request->user()->hasRole('SuperAdmin') && ! $request->session()->has('current_tenant_id')) {
        return redirect()->route('superadmin.profile.edit');
    }

    return redirect()->route('admin.profile.edit');
})->name('profile');

// Dynamic Universal Language / Locale Switcher
Route::post('/locale/{locale}', function (string $locale, Request $request) {
    $supported = array_column(ReferenceData::locales(), 'code');
    if (in_array($locale, $supported, true)) {
        $request->session()->put('locale', $locale);
        app()->setLocale($locale);
        if ($user = $request->user()) {
            $user->update(['locale' => $locale]);
        }
    }

    return back();
})->name('locale.switch');

require __DIR__.'/installer.php';
