@php
    $appLocale = app()->getLocale();
    $isRtl = in_array($appLocale, ['ar', 'he', 'fa', 'ur'], true);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $appLocale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            use Modules\Media\Models\Media;
            use Modules\SuperAdmin\Services\PlatformSettings;

            $dynamicFavicon = null;
            try {
                if (class_exists(PlatformSettings::class)) {
                    $platformSettings = app(PlatformSettings::class);
                    $favId = $platformSettings->get('branding', 'favicon_media_id');
                    if ($favId && class_exists(Media::class)) {
                        $dynamicFavicon = Media::query()->find($favId)?->url;
                    }
                    if (!$dynamicFavicon) {
                        $dynamicFavicon = $platformSettings->get('branding', 'favicon_url');
                    }
                }
            } catch (\Throwable) {
                $dynamicFavicon = null;
            }
        @endphp
        @if ($dynamicFavicon)
            <link rel="icon" href="{{ $dynamicFavicon }}">
            <link rel="apple-touch-icon" href="{{ $dynamicFavicon }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

        <script>
            (function () {
                const preference = localStorage.getItem('sathisaas_theme_preference') || 'light';
                const isDark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                const locale = localStorage.getItem('app_locale') || '{{ $appLocale }}';
                const rtlLocales = ['ar', 'he', 'fa', 'ur'];
                const isRtl = rtlLocales.includes(locale.toLowerCase());
                document.documentElement.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
                document.documentElement.setAttribute('lang', locale);
            })();
        </script>

        @fonts

        @php
            $viteAssets = ['resources/css/app.css', 'resources/js/app.ts'];
            $inertiaComponent = $page['component'] ?? null;

            if (
                is_string($inertiaComponent)
                && $inertiaComponent !== ''
                && is_file(resource_path('js/pages/'.$inertiaComponent.'.vue'))
            ) {
                $viteAssets[] = 'resources/js/pages/'.$inertiaComponent.'.vue';
            }
        @endphp
        @vite($viteAssets)
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
