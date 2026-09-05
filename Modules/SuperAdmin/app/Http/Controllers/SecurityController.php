<?php

namespace Modules\SuperAdmin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SuperAdmin\Actions\Security\RevokeUserSessionAction;
use Modules\SuperAdmin\Services\SecurityCenterService;
use RuntimeException;

class SecurityController
{
    public function __construct(
        private readonly SecurityCenterService $securityCenter,
    ) {}

    public function index(Request $request): Response
    {
        $perPage = $request->integer('per_page') ?: 20;

        $perPage = min(
            max($perPage, 10),
            100,
        );

        $search = $request->string('search')->trim()->value();
        $event = $request->string('event')->trim()->value();

        return Inertia::render('Security/Index', [
            'overview' => $this->securityCenter->overview(),

            'loginActivity' => $this->securityCenter->loginActivity(
                search: $search !== '' ? $search : null,
                event: $event !== '' ? $event : null,
                perPage: $perPage,
            ),

            'recentEvents' => $this->securityCenter->recentEvents(10),

            'activeSessions' => $this->securityCenter->activeSessions(
                perPage: $perPage,
            ),

            'authenticationEvents' => $this->securityCenter
                ->authenticationEvents(),

            'filters' => [
                'search' => $search,
                'event' => $event,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function revokeSession(
        string $session,
        Request $request,
        RevokeUserSessionAction $action,
    ): RedirectResponse {
        try {
            $action->handle(
                sessionId: $session,
                actor: $request->user(),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'session' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            'Session revoked successfully.',
        );
    }
}
