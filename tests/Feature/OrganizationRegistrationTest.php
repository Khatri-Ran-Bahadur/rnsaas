<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Subscription\Models\Plan;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Modules\SuperAdmin\Services\PlatformSettings;
use Modules\Tenancy\Models\Tenant;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('welcome landing page renders with plans', function () {
    Plan::create([
        'public_id' => (string) Str::uuid(),
        'name' => 'Pro Enterprise',
        'slug' => 'pro-enterprise',
        'price' => 99.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'is_active' => true,
    ]);

    $response = $this->get('/');

    $response->assertOk();
});

test('organization registration page renders', function () {
    $response = $this->get('/register');

    $response->assertOk();
});

test('user can register an organization and is sent email verification', function () {
    Notification::fake();

    $plan = Plan::create([
        'public_id' => (string) Str::uuid(),
        'name' => 'Starter Growth',
        'slug' => 'starter',
        'price' => 29.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'trial_days' => 14,
        'is_active' => true,
    ]);

    $response = $this->post('/register', [
        'name' => 'Alexander Hamilton',
        'email' => 'alexander@treasury.gov',
        'password' => 'SecurePass123!@#',
        'password_confirmation' => 'SecurePass123!@#',
        'company_name' => 'Treasury Department',
        'country_code' => 'US',
        'currency' => 'USD',
        'timezone' => 'America/New_York',
        'plan_slug' => 'starter',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'alexander@treasury.gov')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->toBeNull();

    $tenant = Tenant::where('name', 'Treasury Department')->first();
    expect($tenant)->not->toBeNull();
    expect($user->tenants->pluck('id'))->toContain($tenant->id);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('unverified registered user can view email verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/email/verify');

    $response->assertOk();
});

test('superadmin can update email settings with provider', function () {
    $this->seed(SuperAdminDatabaseSeeder::class);
    $role = Role::findByName('SuperAdmin', 'web');
    $admin = User::factory()->create();
    $admin->assignRole($role);

    $response = $this->actingAs($admin)->put('/superadmin/settings', [
        'general' => [
            'platform_name' => 'SathiSaaS Global',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
        ],
        'system' => [
            'maintenance_mode' => false,
            'allow_registrations' => true,
        ],
        'mail' => [
            'provider' => 'resend',
            'host' => 'smtp.resend.com',
            'port' => 587,
            'username' => 'resend',
            'password' => 're_123456789',
            'encryption' => 'tls',
            'from_address' => 'noreply@sathisaas.com',
            'from_name' => 'SathiSaaS System',
        ],
    ]);

    $response->assertRedirect();

    $settings = app(PlatformSettings::class);
    $mailSettings = $settings->group('mail');

    expect($mailSettings['provider'] ?? null)->toBe('resend');
    expect($mailSettings['host'] ?? null)->toBe('smtp.resend.com');
    expect((int) ($mailSettings['port'] ?? 0))->toBe(587);
});
