<?php

namespace Modules\SuperAdmin\Actions\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\Audit\Domain\ValueObjects\AuditContext;
use RuntimeException;

class RevokeUserSessionAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        string $sessionId,
        User $actor,
    ): void {
        DB::transaction(function () use ($sessionId, $actor): void {
            $session = DB::table($this->sessionTable())
                ->where('id', $sessionId)
                ->first();

            if ($session === null) {
                throw new RuntimeException('Session not found.');
            }

            if ((int) $session->user_id === $actor->id) {
                throw new RuntimeException(
                    'You cannot revoke your current session.',
                );
            }

            DB::table($this->sessionTable())
                ->where('id', $sessionId)
                ->delete();

            $this->auditLogger->record(
                event: 'security.session.revoked',
                actor: $actor,
                metadata: [
                    'session_id' => $sessionId,
                    'revoked_user_id' => $session->user_id,
                    'session_ip' => $session->ip_address,
                ],
                context: $this->context(),
            );
        });
    }

    private function sessionTable(): string
    {
        return config('session.table', 'sessions');
    }

    private function context(): AuditContext
    {
        return new AuditContext(
            requestId: request()->header('X-Request-ID'),
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );
    }
}