<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LicenseService;
use Database\Seeders\SystemSetupSeeder;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PDO;
use Spatie\Permission\Models\Role;

class InstallController extends Controller
{
    /**
     * Step 1: Requirements and Directory Permissions Check
     */
    public function welcome(): View
    {
        $requirements = [
            'php_version' => [
                'label' => 'PHP Version >= 8.2',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            'pdo' => [
                'label' => 'PDO Extension',
                'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('pdo'),
            ],
            'pdo_mysql' => [
                'label' => 'MySQL or SQLite PDO Driver',
                'current' => (extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite')) ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'),
            ],
            'mbstring' => [
                'label' => 'Mbstring Extension',
                'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('mbstring'),
            ],
            'openssl' => [
                'label' => 'OpenSSL Extension',
                'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('openssl'),
            ],
            'tokenizer' => [
                'label' => 'Tokenizer Extension',
                'current' => extension_loaded('tokenizer') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('tokenizer'),
            ],
            'xml' => [
                'label' => 'XML Extension',
                'current' => extension_loaded('xml') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('xml'),
            ],
            'curl' => [
                'label' => 'cURL Extension',
                'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('curl'),
            ],
            'fileinfo' => [
                'label' => 'Fileinfo Extension',
                'current' => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled',
                'status' => extension_loaded('fileinfo'),
            ],
        ];

        $permissions = [
            'storage_framework' => [
                'path' => 'storage/framework',
                'status' => is_writable(storage_path('framework')),
            ],
            'storage_logs' => [
                'path' => 'storage/logs',
                'status' => is_writable(storage_path('logs')),
            ],
            'bootstrap_cache' => [
                'path' => 'bootstrap/cache',
                'status' => is_writable(base_path('bootstrap/cache')),
            ],
            'env_writable' => [
                'path' => '.env',
                'status' => file_exists(base_path('.env')) ? is_writable(base_path('.env')) : is_writable(base_path()),
            ],
        ];

        $canProceed = collect($requirements)->every(fn ($r) => $r['status'])
            && collect($permissions)->every(fn ($p) => $p['status']);

        return view('installer.welcome', compact('requirements', 'permissions', 'canProceed'));
    }

    /**
     * Step 2: License Verification & Code Activation View
     */
    public function license(): View
    {
        $licenseInfo = (new LicenseService)->getLicenseInfo();
        $saved = session('installer_license', []);

        $currentLicense = [
            'key' => $saved['key'] ?? ($licenseInfo['key'] ?? env('LICENSE_KEY', '')),
            'buyer_name' => $saved['buyer_name'] ?? ($licenseInfo['buyer_name'] ?? env('LICENSE_BUYER_NAME', '')),
            'buyer_email' => $saved['buyer_email'] ?? ($licenseInfo['buyer_email'] ?? env('LICENSE_BUYER_EMAIL', '')),
            'license_type' => $saved['license_type'] ?? ($licenseInfo['license_type'] ?? env('LICENSE_TYPE', 'extended')),
        ];

        return view('installer.license', compact('currentLicense'));
    }

    /**
     * Step 2: Verify & Activate License Key
     */
    public function verifyLicense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'min:8'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'license_type' => ['nullable', 'string', 'in:regular,extended'],
        ]);

        try {
            $licenseService = new LicenseService;
            $result = $licenseService->verify(
                $validated['license_key'],
                $validated['buyer_name'],
                $validated['buyer_email'] ?? null,
                $validated['license_type'] ?? 'extended'
            );

            session(['installer_license' => $result]);

            $this->updateEnv([
                'LICENSE_KEY' => $result['key'],
                'LICENSE_BUYER_NAME' => $result['buyer_name'],
                'LICENSE_BUYER_EMAIL' => $result['buyer_email'],
                'LICENSE_TYPE' => $result['license_type'],
            ]);

            return redirect()->route('installer.database')->with('success', 'License successfully verified and activated!');
        } catch (Exception $e) {
            return back()->withInput()->withErrors([
                'license_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Step 3: Database Connection Configuration View
     */
    public function database(): View
    {
        $currentDb = [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'rnsaas2'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ];

        return view('installer.database', compact('currentDb'));
    }

    /**
     * Step 3: Test & Save Database Configuration
     */
    public function saveDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        $host = $validated['db_host'];
        $port = $validated['db_port'];
        $database = $validated['db_database'];
        $username = $validated['db_username'];
        $password = $validated['db_password'] ?? '';

        // Test PDO connection & auto-create database if needed
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Unknown database')) {
                try {
                    $rootDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $pdo = new PDO($rootDsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                    ]);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                } catch (Exception $createEx) {
                    return back()->withInput()->withErrors([
                        'db_error' => "Database '{$database}' does not exist and could not be created automatically: ".$createEx->getMessage(),
                    ]);
                }
            } else {
                return back()->withInput()->withErrors([
                    'db_error' => 'Database connection failed: '.$e->getMessage(),
                ]);
            }
        }

        // Save to .env
        $this->updateEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ]);

        if (empty(env('APP_KEY'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        return redirect()->route('installer.application')->with('success', 'Database connection verified and credentials saved!');
    }

    /**
     * Step 3: Application Configuration & Migrations View
     */
    public function application(): View
    {
        $appName = env('APP_NAME', 'SathiSaaS');
        $appUrl = env('APP_URL', url('/'));

        return view('installer.application', compact('appName', 'appUrl'));
    }

    /**
     * Step 3: Run Database Migrations & Initial Setup
     */
    public function runMigrationAndSetup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url'],
            'currency' => ['nullable', 'string', 'max:10'],
            'with_demo' => ['nullable', 'boolean'],
        ]);

        // Update basic app env
        $this->updateEnv([
            'APP_NAME' => $validated['app_name'],
            'APP_URL' => $validated['app_url'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'SESSION_DRIVER' => 'file',
        ]);

        try {
            // Run Migrations
            Artisan::call('migrate', ['--force' => true]);

            // Seed clean system setup
            Artisan::call('db:seed', [
                '--class' => SystemSetupSeeder::class,
                '--force' => true,
            ]);

            // Optional Demo Data
            if ($request->boolean('with_demo')) {
                Artisan::call('demo:import');
            }
        } catch (Exception $e) {
            return back()->withInput()->withErrors([
                'migration_error' => 'Setup execution failed: '.$e->getMessage(),
            ]);
        }

        return redirect()->route('installer.admin')->with('success', 'Database tables and core system successfully initialized!');
    }

    /**
     * Step 4: Super Admin Account Setup View
     */
    public function admin(): View
    {
        return view('installer.admin');
    }

    /**
     * Step 4: Create Super Admin Account & Finalize Installation
     */
    public function createAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = User::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                    'email_verified_at' => now(),
                ]
            );

            $role = Role::findOrCreate('SuperAdmin', 'web');
            $user->assignRole($role);

            // Lock installation permanently with full license info
            $licenseData = session('installer_license') ?: (new LicenseService)->getLicenseInfo();

            file_put_contents(
                storage_path('installed'),
                json_encode([
                    'installed_at' => now()->toIso8601String(),
                    'app_version' => '1.0.0',
                    'admin_email' => $user->email,
                    'license_key' => $licenseData['key'] ?? env('LICENSE_KEY', 'RN-SAAS-PRO-2026-ACTIVE'),
                    'buyer_name' => $licenseData['buyer_name'] ?? env('LICENSE_BUYER_NAME', $user->name),
                    'buyer_email' => $licenseData['buyer_email'] ?? env('LICENSE_BUYER_EMAIL', $user->email),
                    'license_type' => $licenseData['license_type'] ?? env('LICENSE_TYPE', 'Extended SaaS Commercial License'),
                    'license_status' => 'active',
                    'domain' => request()->getHost(),
                ], JSON_PRETTY_PRINT)
            );

            // Clear configuration cache so new .env values take effect
            Artisan::call('optimize:clear');
        } catch (Exception $e) {
            return back()->withInput()->withErrors([
                'admin_error' => 'Failed to create SuperAdmin user: '.$e->getMessage(),
            ]);
        }

        return redirect()->route('installer.complete');
    }

    /**
     * Step 5: Installation Complete Screen
     */
    public function complete(): View
    {
        $installedData = [];
        if (file_exists(storage_path('installed'))) {
            $installedData = json_decode(file_get_contents(storage_path('installed')), true) ?: [];
        }

        return view('installer.complete', compact('installedData'));
    }

    /**
     * Helper to reliably update key-value pairs in the .env file
     */
    protected function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $content = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $valStr = (string) $value;
            $formattedValue = (str_contains($valStr, ' ') || str_contains($valStr, '#')) ? '"'.$valStr.'"' : $valStr;

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$formattedValue}", $content);
            } else {
                $content .= "\n{$key}={$formattedValue}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
