<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Modules\SuperAdmin\Services\SmtpTestService;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SuperAdminDatabaseSeeder::class);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(
        Role::findByName('SuperAdmin', 'web')
    );
});

it('prevents unauthenticated users from testing email settings', function (): void {
    $this->postJson(route('superadmin.settings.email.test'), [
        'email' => 'recipient@example.com',
    ])->assertUnauthorized();
});

it('validates recipient email when testing email dispatch', function (): void {
    $response = $this
        ->actingAs($this->superAdmin)
        ->postJson(route('superadmin.settings.email.test'), [
            'email' => 'invalid-email-address',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('dispatches a test email successfully through the action and logs audit', function (): void {
    $mockService = Mockery::mock(SmtpTestService::class);
    $mockService->shouldReceive('sendTestEmail')
        ->once()
        ->with('test@example.com', Mockery::any())
        ->andReturn([
            'success' => true,
            'message' => 'Test email successfully sent to test@example.com.',
            'recipient' => 'test@example.com',
        ]);

    $this->app->instance(SmtpTestService::class, $mockService);

    $response = $this
        ->actingAs($this->superAdmin)
        ->postJson(route('superadmin.settings.email.test'), [
            'email' => 'test@example.com',
            'host' => 'sandbox.smtp.mailtrap.io',
            'port' => 2525,
            'username' => 'eb73aa4b0bff24',
            'password' => '87fb4126577f90',
            'encryption' => 'tls',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Test email successfully sent to test@example.com.',
        ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'platform.mail.test_sent',
    ]);
});

it('handles test connection endpoint', function (): void {
    $mockService = Mockery::mock(SmtpTestService::class);
    $mockService->shouldReceive('testConnection')
        ->once()
        ->andReturn([
            'success' => true,
            'message' => 'SMTP connection established.',
            'host' => 'sandbox.smtp.mailtrap.io',
            'port' => 2525,
        ]);

    $this->app->instance(SmtpTestService::class, $mockService);

    $response = $this
        ->actingAs($this->superAdmin)
        ->postJson(route('superadmin.settings.email.test-connection'), [
            'host' => 'sandbox.smtp.mailtrap.io',
            'port' => 2525,
            'username' => 'eb73aa4b0bff24',
            'password' => '87fb4126577f90',
            'encryption' => 'tls',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'SMTP connection established.',
        ]);
});
