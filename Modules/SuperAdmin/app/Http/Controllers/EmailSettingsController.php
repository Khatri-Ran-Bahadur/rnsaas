<?php

namespace Modules\SuperAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\SuperAdmin\Actions\Email\SendTestEmailAction;
use Modules\SuperAdmin\Http\Requests\SendTestEmailRequest;
use Modules\SuperAdmin\Services\SmtpTestService;

class EmailSettingsController
{
    /**
     * Send a test email to verify SMTP configuration and delivery.
     */
    public function sendTestEmail(
        SendTestEmailRequest $request,
        SendTestEmailAction $action,
    ): JsonResponse|RedirectResponse {
        $result = $action->execute(
            data: $request->validated(),
            actor: $request->user(),
        );

        if ($request->wantsJson()) {
            return response()->json(
                data: $result,
                status: $result['success'] ? 200 : 422,
            );
        }

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Test the raw SMTP connection without dispatching an email.
     */
    public function testConnection(
        Request $request,
        SmtpTestService $service,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
        ]);

        $result = $service->testConnection($validated);

        if ($request->wantsJson()) {
            return response()->json(
                data: $result,
                status: $result['success'] ? 200 : 422,
            );
        }

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
