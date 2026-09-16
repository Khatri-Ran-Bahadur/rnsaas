<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\SystemSetupSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

#[Signature('app:setup {--fresh : Wipe the database and run fresh migrations} {--with-demo : Include demo mock datasets for showcase} {--admin-email= : Super admin user email} {--admin-password= : Super admin user password}')]
#[Description('Initialize clean core system architecture, statutory tax, CoA, and roles for production deployment')]
class SystemSetupCommand extends Command
{
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('         SathiSaaS System Initializer               ');
        $this->info('====================================================');
        $this->newLine();

        if ($this->option('fresh')) {
            if ($this->getLaravel()->environment('production') && ! $this->confirm('Application is in production. Are you SURE you want to run migrate:fresh?', false)) {
                $this->warn('Setup aborted.');

                return self::FAILURE;
            }

            $this->components->task('Executing fresh migrations', function () {
                $this->callSilent('migrate:fresh', ['--force' => true]);

                return true;
            });
        } else {
            $this->components->task('Running pending migrations', function () {
                $this->callSilent('migrate', ['--force' => true]);

                return true;
            });
        }

        $this->components->task('Seeding Core System Setup (Roles, Permissions, Tax Regimes, COA, Base Units, POS, MRP)', function () {
            $this->callSilent('db:seed', ['--class' => SystemSetupSeeder::class, '--force' => true]);

            return true;
        });

        // Ensure SuperAdmin User
        $adminEmail = $this->option('admin-email') ?: 'admin@sathisaas.com';
        $adminPassword = $this->option('admin-password') ?: 'password';

        $superAdmin = User::firstWhere('email', $adminEmail);
        if (! $superAdmin) {
            $superAdmin = User::create([
                'name' => 'Super Administrator',
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]);
            $this->components->info("Super Admin user created: {$adminEmail} (password: {$adminPassword})");
        } else {
            if (! $superAdmin->email_verified_at) {
                $superAdmin->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $superAdminRole = Role::findByName('SuperAdmin', 'web');
        if ($superAdminRole && ! $superAdmin->hasRole('SuperAdmin')) {
            $superAdmin->assignRole($superAdminRole);
        }

        // Demo data if requested
        if ($this->option('with-demo')) {
            $this->newLine();
            $this->info('Importing optional demo showcase datasets...');
            $this->call('demo:import');
        }

        // Lock installation
        file_put_contents(storage_path('installed'), now()->toIso8601String());

        $this->newLine();
        $this->info('Core system setup completed successfully! The application is ready.');
        $this->info('Login URL: '.url('/login')." | Admin Email: {$adminEmail}");

        return self::SUCCESS;
    }
}
