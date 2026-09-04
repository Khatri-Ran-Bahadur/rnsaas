<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Media\Models\Media;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Modules\SuperAdmin\Models\PlatformSetting;
use Modules\SuperAdmin\Services\PlatformSettings;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SuperAdminDatabaseSeeder::class);

    $this->superAdmin = User::factory()->create();

    $this->superAdmin->assignRole(
        Role::findByName('SuperAdmin', 'web')
    );
});

it('allows a super admin to view platform settings', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('admin.settings.index'));

    $response
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('settings')
                ->has('settings.general')
                ->has('settings.system')
                ->has('settings.mail')
                ->has('timezones')
                ->has('currencies')
                ->has('platform')
                ->has('platform.name')
        );
});

it('updates general platform settings', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->put(route('admin.settings.update'), [
            'general' => [
                'platform_name' => 'SathiSaaS Pro',
                'support_email' => 'support@example.com',
                'support_phone' => '+9779800000000',
                'timezone' => 'Asia/Kathmandu',
                'currency' => 'NPR',
                'date_format' => 'Y-m-d',
            ],
            'system' => [
                'maintenance_mode' => false,
                'maintenance_message' => 'Maintenance in progress.',
            ],
            'mail' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'mailer@example.com',
                'password' => 'secret-password',
                'encryption' => 'tls',
                'from_address' => 'noreply@example.com',
                'from_name' => 'SathiSaaS',
            ],
        ]);

    $response
        ->assertSessionHas('success');

    expect(
        app(PlatformSettings::class)
            ->get('general', 'platform_name')
    )->toBe('SathiSaaS Pro');

    expect(
        app(PlatformSettings::class)
            ->get('general', 'currency')
    )->toBe('NPR');

    expect(
        app(PlatformSettings::class)
            ->get('general', 'timezone')
    )->toBe('Asia/Kathmandu');

    $mailPassword = PlatformSetting::query()
        ->where('group', 'mail')
        ->where('key', 'password')
        ->first();

    expect($mailPassword)->not->toBeNull();
    expect($mailPassword->is_secret)->toBeTrue();

    expect($mailPassword->value)
        ->not->toBe('secret-password');
});

it('does not overwrite an existing mail password with a blank value', function (): void {
    $settings = app(PlatformSettings::class);

    $settings->set(
        group: 'mail',
        key: 'password',
        value: 'original-password',
        type: 'string',
        isSecret: true,
    );

    $this
        ->actingAs($this->superAdmin)
        ->put(route('admin.settings.update'), [
            'general' => [
                'platform_name' => 'SathiSaaS',
                'support_email' => null,
                'support_phone' => null,
                'timezone' => 'UTC',
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
            ],
            'system' => [
                'maintenance_mode' => false,
                'maintenance_message' => null,
            ],
            'mail' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'mailer@example.com',
                'password' => null,
                'encryption' => 'tls',
                'from_address' => 'noreply@example.com',
                'from_name' => 'SathiSaaS',
            ],
        ]);

    expect($settings->get('mail', 'password'))
        ->toBe('original-password');
});

it('validates invalid timezone', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->put(route('admin.settings.update'), [
            'general' => [
                'platform_name' => 'SathiSaaS',
                'support_email' => null,
                'support_phone' => null,
                'timezone' => 'Invalid/Timezone',
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
            ],
            'system' => [
                'maintenance_mode' => false,
                'maintenance_message' => null,
            ],
            'mail' => [],
        ]);

    $response->assertSessionHasErrors([
        'general.timezone',
    ]);
});

it('denies users without settings permission', function (): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('admin.settings.index'));

    $response->assertForbidden();
});

it('allows a super admin to clear platform settings cache', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->post(route('admin.settings.cache.clear'));

    $response
        ->assertSessionHas(
            'success',
            'Platform settings cache cleared successfully.',
        );
});

it('updates and persists branding platform settings across page refresh', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->put(route('admin.settings.update'), [
            'general' => [
                'platform_name' => 'SathiSaaS Pro',
                'support_email' => 'support@example.com',
                'support_phone' => '+9779800000000',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
            ],
            'branding' => [
                'logo_media_id' => 12,
                'favicon_media_id' => 34,
                'login_logo_media_id' => 56,
                'logo_url' => 'https://example.com/logo.png',
                'favicon_url' => 'https://example.com/favicon.png',
                'login_logo_url' => 'https://example.com/login_logo.png',
            ],
            'system' => [
                'maintenance_mode' => false,
                'maintenance_message' => null,
            ],
            'mail' => [],
        ]);

    $response->assertSessionHas('success');

    $settings = app(PlatformSettings::class);
    expect($settings->get('branding', 'logo_url'))->toBe('https://example.com/logo.png');
    expect($settings->get('branding', 'favicon_url'))->toBe('https://example.com/favicon.png');
    expect($settings->get('branding', 'login_logo_url'))->toBe('https://example.com/login_logo.png');
    expect($settings->get('branding', 'logo_media_id'))->toBe(12);

    // Assert that index reloads with the updated branding
    $indexResponse = $this
        ->actingAs($this->superAdmin)
        ->get(route('admin.settings.index'));

    $indexResponse
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('settings.branding.logo_url', 'https://example.com/logo.png')
                ->where('settings.branding.favicon_url', 'https://example.com/favicon.png')
                ->where('settings.branding.login_logo_url', 'https://example.com/login_logo.png')
        );
});

it('resolves branding media urls dynamically from Media models', function (): void {
    $media = Media::query()->create([
        'name' => 'platform-logo',
        'file_name' => 'platform-logo.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'size' => 1024,
    ]);

    $this
        ->actingAs($this->superAdmin)
        ->put(route('admin.settings.update'), [
            'general' => [
                'platform_name' => 'SathiSaaS',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
            ],
            'branding' => [
                'logo_media_id' => $media->id,
                'favicon_media_id' => null,
                'login_logo_media_id' => null,
                'logo_url' => null,
                'favicon_url' => null,
                'login_logo_url' => null,
            ],
            'system' => [
                'maintenance_mode' => false,
                'maintenance_message' => null,
            ],
        ])
        ->assertSessionHas('success');

    $response = $this
        ->actingAs($this->superAdmin)
        ->get(route('admin.settings.index'));

    $response
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('settings.branding.logo_media_id', $media->id)
                ->where('settings.branding.logo_url', $media->url)
        );
});
