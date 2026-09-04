<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $dynamicFavicon = null;
            try {
                if (class_exists(\Modules\SuperAdmin\Services\PlatformSettings::class)) {
                    $platformSettings = app(\Modules\SuperAdmin\Services\PlatformSettings::class);
                    $favId = $platformSettings->get('branding', 'favicon_media_id');
                    if ($favId && class_exists(\Modules\Media\Models\Media::class)) {
                        $dynamicFavicon = \Modules\Media\Models\Media::query()->find($favId)?->url;
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
            })();
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
