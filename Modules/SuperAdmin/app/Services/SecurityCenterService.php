<?php

namespace Modules\SuperAdmin\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Models\AuditLog;

class SecurityCenterService
{
    /**
     * Authentication-related audit events.
     *
     * @var array<int, string>
     */
    private const AUTH_EVENTS = [
        'auth.login.success',
        'auth.login.failed',
        'auth.logout',
        'auth.lockout',
        'auth.password.reset.requested',
        'auth.password.reset.completed',
    ];

    /**
     * Return the security dashboard overview.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $today = now()->startOfDay();

        $counts = AuditLog::query()
            ->select('event', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('event', self::AUTH_EVENTS)
            ->where('created_at', '>=', $today)
            ->groupBy('event')
            ->pluck('aggregate', 'event');

        return [
            'successful_logins_today' => (int) ($counts['auth.login.success'] ?? 0),
            'failed_logins_today' => (int) ($counts['auth.login.failed'] ?? 0),
            'logouts_today' => (int) ($counts['auth.logout'] ?? 0),
            'lockouts_today' => (int) ($counts['auth.lockout'] ?? 0),
            'password_reset_requests_today' => (int) (
                $counts['auth.password.reset.requested'] ?? 0
            ),
            'password_resets_today' => (int) (
                $counts['auth.password.reset.completed'] ?? 0
            ),
            'active_sessions' => $this->activeSessionCount(),
        ];
    }

    /**
     * Return recent security events.
     */
    public function recentEvents(int $limit = 10): Collection
    {
        return $this->securityEventQuery()
            ->with('actor:id,name,email')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $audit) => $this->transformAudit($audit));
    }

    /**
     * Return paginated authentication activity.
     */
    public function loginActivity(
        ?string $search = null,
        ?string $event = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = AuditLog::query()
            ->with('actor:id,name,email')
            ->whereIn('event', self::AUTH_EVENTS);

        $this->applySearch($query, $search);

        if ($event !== null && in_array($event, self::AUTH_EVENTS, true)) {
            $query->where('event', $event);
        }

        return $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(
                fn (AuditLog $audit) => $this->transformAudit($audit)
            );
    }

    /**
     * Return paginated security events.
     */
    public function securityEvents(
        ?string $search = null,
        ?string $event = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = $this->securityEventQuery()
            ->with('actor:id,name,email');

        $this->applySearch($query, $search);

        if ($event !== null) {
            $query->where('event', $event);
        }

        return $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(
                fn (AuditLog $audit) => $this->transformAudit($audit)
            );
    }

    /**
     * Return available authentication event types.
     *
     * @return array<int, string>
     */
    public function authenticationEvents(): array
    {
        return self::AUTH_EVENTS;
    }

    /**
     * Return the number of currently active database sessions.
     */
    public function activeSessionCount(): int
    {
        if (! $this->databaseSessionsAvailable()) {
            return 0;
        }

        return DB::table($this->sessionTable())
            ->whereNotNull('user_id')
            ->where(
                'last_activity',
                '>=',
                now()->subMinutes($this->sessionLifetime()),
            )
            ->count();
    }

    /**
     * Return currently active sessions.
     */
    public function activeSessions(int $perPage = 20): LengthAwarePaginator
    {
        if (! $this->databaseSessionsAvailable()) {
            return $this->emptyPaginator($perPage);
        }

        return DB::table($this->sessionTable())
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->select([
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->whereNotNull('sessions.user_id')
            ->where(
                'sessions.last_activity',
                '>=',
                now()->subMinutes($this->sessionLifetime()),
            )
            ->orderByDesc('sessions.last_activity')
            ->paginate($perPage)
            ->withQueryString()
            ->through(
                fn (object $session) => $this->transformSession($session)
            );
    }

    /**
     * Build the base security-event query.
     */
    private function securityEventQuery(): Builder
    {
        return AuditLog::query()
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('event', self::AUTH_EVENTS)
                    ->orWhere('event', 'like', 'security.%');
            });
    }

    /**
     * Apply user/event/IP search.
     */
    private function applySearch(
        Builder $query,
        ?string $search,
    ): void {
        if ($search === null || trim($search) === '') {
            return;
        }

        $search = trim($search);

        $query->where(function (Builder $query) use ($search): void {
            $query
                ->where('event', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhereHas('actor', function (Builder $actorQuery) use ($search): void {
                    $actorQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Transform audit data into a frontend-safe representation.
     *
     * @return array<string, mixed>
     */
    private function transformAudit(AuditLog $audit): array
    {
        return [
            'id' => $audit->public_id,
            'event' => $audit->event,
            'actor' => $audit->actor
                ? [
                    'id' => $audit->actor->id,
                    'name' => $audit->actor->name,
                    'email' => $audit->actor->email,
                ]
                : null,
            'ip_address' => $audit->ip_address,
            'user_agent' => $audit->user_agent,
            'metadata' => $audit->metadata,
            'created_at' => $audit->created_at?->toISOString(),
        ];
    }

    /**
     * Transform a database session into a frontend-safe representation.
     *
     * @return array<string, mixed>
     */
    private function transformSession(object $session): array
    {
        return [
            'id' => $session->id,
            'user_id' => $session->user_id,
            'user' => [
                'name' => $session->user_name,
                'email' => $session->user_email,
            ],
            'ip_address' => $session->ip_address,
            'user_agent' => $session->user_agent,
            'last_activity' => $session->last_activity,
        ];
    }

    /**
     * Return the configured session table.
     */
    private function sessionTable(): string
    {
        return config('session.table', 'sessions');
    }

    /**
     * Return the configured session lifetime in minutes.
     */
    private function sessionLifetime(): int
    {
        return (int) config('session.lifetime', 120);
    }

    /**
     * Check whether the database session table exists.
     */
    private function databaseSessionsAvailable(): bool
    {
        return DB::getSchemaBuilder()->hasTable($this->sessionTable());
    }

    /**
     * Return an empty paginator when sessions are unavailable.
     */
    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new PaginationLengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: $perPage,
            currentPage: 1,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }
}