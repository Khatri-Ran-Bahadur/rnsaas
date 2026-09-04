<?php

namespace Modules\SuperAdmin\Services;

use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class SmtpTestService
{
    private readonly MailManager $mailManager;

    private readonly PlatformSettings $settings;

    public function __construct(
        ?MailManager $mailManager = null,
        ?PlatformSettings $settings = null,
    ) {
        $this->mailManager = $mailManager ?? app('mail.manager');
        $this->settings = $settings ?? app(PlatformSettings::class);
    }

    /**
     * Build the resolved SMTP configuration array.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function buildConfig(array $overrides = []): array
    {
        try {
            $savedMail = $this->settings->group('mail');
        } catch (Throwable) {
            $savedMail = [];
        }

        $host = $overrides['host']
            ?? $savedMail['host']
            ?? config('mail.mailers.smtp.host', '127.0.0.1');

        $port = (int) (
            $overrides['port']
            ?? $savedMail['port']
            ?? config('mail.mailers.smtp.port', 2525)
        );

        $encryption = $overrides['encryption']
            ?? $savedMail['encryption']
            ?? config('mail.mailers.smtp.encryption', 'tls');

        if ($encryption === 'none') {
            $encryption = null;
        }

        $scheme = $overrides['scheme']
            ?? (
                ($encryption === 'ssl' || $port === 465)
                    ? 'smtps'
                    : 'smtp'
            );

        $username = $overrides['username']
            ?? $savedMail['username']
            ?? config('mail.mailers.smtp.username');

        // Retain saved or config password when override is blank or not provided
        $password = ! empty($overrides['password'])
            ? $overrides['password']
            : ($savedMail['password'] ?? config('mail.mailers.smtp.password'));

        $fromAddress = $overrides['from_address']
            ?? $savedMail['from_address']
            ?? config('mail.from.address', 'support@sathisaas.com');

        $fromName = $overrides['from_name']
            ?? $savedMail['from_name']
            ?? config('mail.from.name', 'SathiSaaS');

        return [
            'transport' => 'smtp',
            'host' => (string) $host,
            'port' => $port,
            'encryption' => $encryption,
            'scheme' => $scheme,
            'username' => $username ? (string) $username : null,
            'password' => $password ? (string) $password : null,
            'timeout' => 10,
            'from_address' => (string) $fromAddress,
            'from_name' => (string) $fromName,
        ];
    }

    /**
     * Test the raw SMTP transport connection and authentication.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{success: bool, message: string, host: string, port: int}
     */
    public function testConnection(array $overrides = []): array
    {
        $config = $this->buildConfig($overrides);

        try {
            $transport = $this->mailManager->createSymfonyTransport([
                'transport' => 'smtp',
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'],
                'scheme' => $config['scheme'],
                'username' => $config['username'],
                'password' => $config['password'],
                'timeout' => $config['timeout'],
            ]);

            if (method_exists($transport, 'start')) {
                $transport->start();
            }

            if (method_exists($transport, 'stop')) {
                $transport->stop();
            }

            return [
                'success' => true,
                'message' => "SMTP connection to {$config['host']}:{$config['port']} succeeded.",
                'host' => $config['host'],
                'port' => $config['port'],
            ];
        } catch (TransportExceptionInterface|Throwable $e) {
            return [
                'success' => false,
                'message' => "SMTP connection failed: {$e->getMessage()}",
                'host' => $config['host'],
                'port' => $config['port'],
            ];
        }
    }

    /**
     * Send an actual HTML test email to the specified recipient.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{success: bool, message: string, recipient: string}
     */
    public function sendTestEmail(string $recipient, array $overrides = []): array
    {
        $config = $this->buildConfig($overrides);

        try {
            $mailer = $this->mailManager->build([
                'transport' => 'smtp',
                'host' => $config['host'],
                'port' => $config['port'],
                'encryption' => $config['encryption'],
                'scheme' => $config['scheme'],
                'username' => $config['username'],
                'password' => $config['password'],
                'timeout' => $config['timeout'],
            ]);

            $fromAddress = $config['from_address'];
            $fromName = $config['from_name'];
            try {
                $platformName = (string) (
                    $this->settings->get('general', 'platform_name')
                    ?? config('app.name', 'SathiSaaS')
                );
            } catch (Throwable) {
                $platformName = (string) config('app.name', 'SathiSaaS');
            }
            $sentAt = now()->toDateTimeString();
            $encryptionLabel = $config['encryption'] ? strtoupper((string) $config['encryption']) : 'None';

            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{$platformName} SMTP Test</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f4f5; padding: 40px 20px; color: #18181b; margin: 0;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="background-color: #4f46e5; padding: 24px 32px; color: #ffffff;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 600; color: #ffffff;">{$platformName} - SMTP Configuration Test</h1>
        </div>
        <div style="padding: 32px;">
            <p style="font-size: 15px; line-height: 24px; margin-top: 0; color: #27272a;">
                Hello,
            </p>
            <p style="font-size: 15px; line-height: 24px; color: #27272a;">
                This test email confirms that your SMTP mail settings on <strong>{$platformName}</strong> are properly configured and operational.
            </p>
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 24px 0;">
                <table style="width: 100%; font-size: 13px; color: #475569; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; font-weight: 600; width: 130px; color: #334155;">SMTP Host:</td>
                        <td style="padding: 6px 0; font-family: monospace; color: #0f172a;">{$config['host']}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-weight: 600; color: #334155;">SMTP Port:</td>
                        <td style="padding: 6px 0; font-family: monospace; color: #0f172a;">{$config['port']}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-weight: 600; color: #334155;">Encryption:</td>
                        <td style="padding: 6px 0; color: #0f172a;">{$encryptionLabel}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-weight: 600; color: #334155;">Sender:</td>
                        <td style="padding: 6px 0; color: #0f172a;">{$fromName} &lt;{$fromAddress}&gt;</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-weight: 600; color: #334155;">Dispatched At:</td>
                        <td style="padding: 6px 0; color: #0f172a;">{$sentAt}</td>
                    </tr>
                </table>
            </div>
            <p style="font-size: 14px; line-height: 20px; color: #16a34a; font-weight: 600; margin-bottom: 0;">
                ✓ SMTP connection and email delivery verified successfully!
            </p>
        </div>
        <div style="background-color: #fafafa; padding: 16px 32px; border-top: 1px solid #f4f4f5; text-align: center; font-size: 12px; color: #a1a1aa;">
            Sent by {$platformName} SuperAdmin Platform Settings.
        </div>
    </div>
</body>
</html>
HTML;

            $mailer->html($html, function ($message) use ($recipient, $fromAddress, $fromName, $platformName) {
                $message->to($recipient)
                    ->from($fromAddress, $fromName)
                    ->subject("{$platformName} - SMTP Configuration Test");
            });

            return [
                'success' => true,
                'message' => "Test email successfully sent to {$recipient}.",
                'recipient' => $recipient,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => "Failed to send test email: {$e->getMessage()}",
                'recipient' => $recipient,
            ];
        }
    }
}
