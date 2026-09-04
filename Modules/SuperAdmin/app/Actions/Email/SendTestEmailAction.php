<?php

namespace Modules\SuperAdmin\Actions\Email;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\SuperAdmin\Services\SmtpTestService;

class SendTestEmailAction
{
    public function __construct(
        private readonly SmtpTestService $smtpTestService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Send a test email using either provided parameters or saved settings.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, recipient?: string}
     */
    public function execute(array $data, ?object $actor = null): array
    {
        $recipient = (string) (
            $data['email']
            ?? $data['recipient_email']
            ?? ($actor && isset($actor->email) ? $actor->email : null)
        );

        $overrides = array_filter([
            'host' => $data['host'] ?? null,
            'port' => isset($data['port']) ? (int) $data['port'] : null,
            'username' => $data['username'] ?? null,
            'password' => $data['password'] ?? null,
            'encryption' => $data['encryption'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'from_name' => $data['from_name'] ?? null,
        ], fn ($val) => $val !== null && $val !== '');

        $result = $this->smtpTestService->sendTestEmail($recipient, $overrides);

        $this->auditLogger->record(
            event: 'platform.mail.test_sent',
            actor: $actor,
            metadata: [
                'recipient' => $recipient,
                'success' => $result['success'],
                'message' => $result['message'],
            ],
        );

        return $result;
    }
}
