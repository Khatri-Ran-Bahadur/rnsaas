<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureApplicationIsInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $isInstallRoute = $request->is('install*');
        $isUpdateRoute = $request->is('update*');
        $isAsset = $request->is('build/*', 'vendor/*', 'favicon.ico', 'up', 'assets/*');

        $installed = $this->isInstalled();

        if (! $installed) {
            // Force file sessions and file cache in installer mode to avoid DB connection errors
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);

            // Clear any lingering authenticated user session keys from previous databases
            if ($request->hasSession()) {
                foreach (array_keys($request->session()->all()) as $key) {
                    if (str_starts_with($key, 'login_')) {
                        $request->session()->forget($key);
                    }
                }
            }

            if ($isInstallRoute || $isUpdateRoute || $isAsset) {
                return $next($request);
            }

            return redirect()->route('installer.welcome');
        }

        // When already installed, protect the installer from re-running
        if ($isInstallRoute) {
            if ($request->is('install/complete')) {
                return $next($request);
            }

            return redirect()->to('/')->with('info', 'Application is already installed.');
        }

        return $next($request);
    }

    /**
     * Check whether application is installed and database is migrated.
     */
    protected function isInstalled(): bool
    {
        $installedFile = storage_path('installed');
        if (! file_exists($installedFile)) {
            return false;
        }

        // Even if installed file exists, check if database tables exist
        // If the database was changed (e.g. to rnsaas2) without migrations,
        // it must trigger the installation wizard!
        try {
            if (! Schema::hasTable('users') || ! Schema::hasTable('migrations')) {
                @unlink($installedFile);

                return false;
            }
        } catch (Throwable) {
            @unlink($installedFile);

            return false;
        }

        return true;
    }
}
