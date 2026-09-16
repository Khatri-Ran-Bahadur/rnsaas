<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class UpdateController extends Controller
{
    /**
     * Display System Updater Dashboard
     */
    public function index(): View
    {
        $installedInfo = [];
        if (file_exists(storage_path('installed'))) {
            $installedInfo = json_decode(file_get_contents(storage_path('installed')), true) ?: [];
        }

        $currentVersion = $installedInfo['app_version'] ?? '1.0.0';
        $targetVersion = config('app.version', '1.1.0');

        return view('installer.update', compact('currentVersion', 'targetVersion', 'installedInfo'));
    }

    /**
     * Run Pending Migrations, Seeds & Optimizations
     */
    public function runUpdate(Request $request): RedirectResponse
    {
        try {
            // Run pending migrations
            Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = Artisan::output();

            // Clear cache and rebuild
            Artisan::call('optimize:clear');

            // Update installation lock with new version
            $targetVersion = config('app.version', '1.1.0');
            $installedData = [
                'installed_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
                'app_version' => $targetVersion,
            ];

            file_put_contents(
                storage_path('installed'),
                json_encode($installedData, JSON_PRETTY_PRINT)
            );

            return back()->with('success', 'System updated successfully! Database migrations executed and system caches cleared.')->with('output', $migrationOutput);
        } catch (Exception $e) {
            return back()->withErrors(['update_error' => 'System update failed: '.$e->getMessage()]);
        }
    }
}
