<?php

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Modules\Audit\Models\AuditLog;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'email' => 'security@example.com',
    ]);
});

it('records successful login as an audit event', function (): void {
    event(new Login('web', $this->user, false));

    $audit = AuditLog::query()
        ->where('event', 'auth.login.success')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($this->user->id)
        ->and($audit->auditable_id)->toBe($this->user->id)
        ->and($audit->metadata)->toMatchArray([
            'guard' => 'web',
            'remember' => false,
        ]);
});

it('records failed login without storing the password', function (): void {
    event(new Failed(
        'web',
        $this->user,
        [
            'email' => 'security@example.com',
            'password' => 'super-secret-password',
        ],
    ));

    $audit = AuditLog::query()
        ->where('event', 'auth.login.failed')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->metadata)->toMatchArray([
            'guard' => 'web',
            'identifier' => 'security@example.com',
            'user_found' => true,
        ])
        ->and($audit->metadata)->not->toHaveKey('password');
});

it('records failed login when the user does not exist', function (): void {
    event(new Failed(
        'web',
        null,
        [
            'email' => 'unknown@example.com',
            'password' => 'super-secret-password',
        ],
    ));

    $audit = AuditLog::query()
        ->where('event', 'auth.login.failed')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBeNull()
        ->and($audit->auditable_id)->toBeNull()
        ->and($audit->metadata)->toMatchArray([
            'guard' => 'web',
            'identifier' => 'unknown@example.com',
            'user_found' => false,
        ])
        ->and($audit->metadata)->not->toHaveKey('password');
});

it('records logout as an audit event', function (): void {
    event(new Logout('web', $this->user));

    $audit = AuditLog::query()
        ->where('event', 'auth.logout')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($this->user->id)
        ->and($audit->auditable_id)->toBe($this->user->id)
        ->and($audit->metadata)->toMatchArray([
            'guard' => 'web',
        ]);
});

it('records password reset completion', function (): void {
    event(new PasswordReset($this->user));

    $audit = AuditLog::query()
        ->where('event', 'auth.password.reset.completed')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($this->user->id)
        ->and($audit->auditable_id)->toBe($this->user->id);
});

it('records password reset request', function (): void {
    event(new PasswordResetLinkSent($this->user));

    $audit = AuditLog::query()
        ->where('event', 'auth.password.reset.requested')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->auditable_id)->toBe($this->user->id);
});