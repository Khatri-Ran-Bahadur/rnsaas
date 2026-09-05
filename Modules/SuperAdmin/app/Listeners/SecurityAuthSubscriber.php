<?php

namespace Modules\SuperAdmin\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;
use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;

final class SecurityAuthSubscriber
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Handle successful login.
     */
    public function handleLogin(Login $event): void
    {
        $this->auditLogger->record(
            event: 'auth.login.success',
            actor: $event->user,
            auditable: $event->user,
            metadata: [
                'guard' => $event->guard,
                'remember' => $event->remember,
            ],
            context: $this->context(),
        );
    }

    /**
     * Handle failed login.
     */
    public function handleFailed(Failed $event): void
    {
        $usernameField = config('fortify.username', 'email');

        $identifier = Arr::get(
            $event->credentials,
            $usernameField,
        );

        $this->auditLogger->record(
            event: 'auth.login.failed',
            auditable: $event->user,
            metadata: [
                'guard' => $event->guard,
                'identifier' => is_string($identifier)
                    ? $identifier
                    : null,
                'user_found' => $event->user !== null,
            ],
            context: $this->context(),
        );
    }

    /**
     * Handle logout.
     */
    public function handleLogout(Logout $event): void
    {
        $this->auditLogger->record(
            event: 'auth.logout',
            actor: $event->user,
            auditable: $event->user,
            metadata: [
                'guard' => $event->guard,
            ],
            context: $this->context(),
        );
    }

    /**
     * Handle authentication lockout.
     */
    public function handleLockout(Lockout $event): void
    {
        $this->auditLogger->record(
            event: 'auth.lockout',
            metadata: [
                'path' => $event->request->path(),
                'method' => $event->request->method(),
            ],
            context: $this->context($event->request),
        );
    }

    /**
     * Handle password reset completion.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->auditLogger->record(
            event: 'auth.password.reset.completed',
            actor: $event->user,
            auditable: $event->user,
            context: $this->context(),
        );
    }

    /**
     * Handle password reset link delivery.
     */
    public function handlePasswordResetLinkSent(
        PasswordResetLinkSent $event,
    ): void {
        $this->auditLogger->record(
            event: 'auth.password.reset.requested',
            auditable: $event->user,
            context: $this->context(),
        );
    }

    /**
     * Register authentication listeners.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
            PasswordResetLinkSent::class => 'handlePasswordResetLinkSent',
        ];
    }

    private function context(?object $request = null): AuditContext
    {
        $request = $request ?? (
            app()->bound('request')
                ? request()
                : null
        );

        return new AuditContext(
            requestId: $request?->header('X-Request-ID'),
            ipAddress: $request?->ip(),
            userAgent: $request?->userAgent(),
        );
    }
}
