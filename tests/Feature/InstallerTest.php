<?php

use App\Models\User;
use Database\Seeders\SystemSetupSeeder;
use Illuminate\Support\Facades\File;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Ensure test environment uses clean state
    if (File::exists(storage_path('installed'))) {
        File::delete(storage_path('installed'));
    }
});

afterEach(function () {
    // Re-lock so subsequent tests run in an installed app environment
    File::put(storage_path('installed'), now()->toIso8601String());
});

test('uninstalled application redirects to installer welcome page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('installer.welcome'));
});

test('installer welcome page displays system requirements', function () {
    $response = $this->get(route('installer.welcome'));

    $response->assertStatus(200);
    $response->assertSee('System Requirements');
    $response->assertSee('PHP Version');
});

test('installer license step renders correctly', function () {
    $response = $this->get(route('installer.license'));

    $response->assertStatus(200);
    $response->assertSee('Software License');
    $response->assertSee('License Key / Purchase Code');
});

test('installer license verification validates and advances to database step', function () {
    $response = $this->post(route('installer.license.verify'), [
        'license_key' => 'RN-SAAS-PRO-2026-ACTIVE',
        'buyer_name' => 'Ran Bahadur Khatri',
        'buyer_email' => 'ran@example.com',
        'license_type' => 'extended',
    ]);

    $response->assertRedirect(route('installer.database'));
    $response->assertSessionHas('success');
    expect(session('installer_license'))->not->toBeNull();
    expect(session('installer_license.status'))->toBe('active');
});

test('installer license verification rejects invalid key', function () {
    $response = $this->post(route('installer.license.verify'), [
        'license_key' => 'INVALID-KEY-12345',
        'buyer_name' => 'Ran Bahadur Khatri',
        'buyer_email' => 'ran@example.com',
        'license_type' => 'extended',
    ]);

    $response->assertSessionHasErrors(['license_error']);
});

test('installer database step renders correctly', function () {
    $response = $this->get(route('installer.database'));

    $response->assertStatus(200);
    $response->assertSee('Database Configuration');
    $response->assertSee('Database Host');
});

test('installer admin creation generates super admin and writes installed lock', function () {
    Role::findOrCreate('SuperAdmin', 'web');

    $response = $this->post(route('installer.admin.create'), [
        'name' => 'Owner Admin',
        'email' => 'owner@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('installer.complete'));

    expect(File::exists(storage_path('installed')))->toBeTrue();

    $user = User::where('email', 'owner@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('SuperAdmin'))->toBeTrue();
});

test('installed application prevents access to installer welcome and redirects', function () {
    file_put_contents(storage_path('installed'), json_encode(['installed_at' => now()->toIso8601String()]));

    $response = $this->get(route('installer.welcome'));

    $response->assertRedirect('/');
});

test('updater page is accessible', function () {
    $response = $this->get(route('updater.index'));

    $response->assertStatus(200);
    $response->assertSee('System Updater');
});

test('system setup seeder executes cleanly without column errors', function () {
    $this->artisan('db:seed', ['--class' => SystemSetupSeeder::class, '--force' => true])
        ->assertSuccessful();

    $tenant = Tenant::where('slug', 'main-company')->first();
    expect($tenant)->not->toBeNull();
    expect($tenant->status)->toBe(TenantStatus::Active);
});

test('app:setup artisan command runs successfully and creates super admin', function () {
    $this->artisan('app:setup', [
        '--admin-email' => 'setupadmin@example.com',
        '--admin-password' => 'secret1234',
    ])->assertSuccessful();

    $admin = User::where('email', 'setupadmin@example.com')->first();
    expect($admin)->not->toBeNull();
    expect($admin->hasRole('SuperAdmin'))->toBeTrue();
    expect(File::exists(storage_path('installed')))->toBeTrue();
});

test('installer handles stale session without 500 error when uninstalled', function () {
    if (File::exists(storage_path('installed'))) {
        File::delete(storage_path('installed'));
    }

    $response = $this->withSession([
        'login_web_59ba36addc2b2f9401560f0c40d5ca09f' => 99999,
    ])->get('/');

    $response->assertRedirect(route('installer.welcome'));
    $this->get(route('installer.welcome'))->assertStatus(200);
});
