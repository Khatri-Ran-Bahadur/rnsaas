<?php

namespace App\Services;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Modules\SuperAdmin\Services\PlatformSettings;

class MailConfigService
{
    /**
     * Configure runtime mailer from platform settings or tenant custom SMTP.
     */
    public static function setDynamicConfig(): void
    {
        // 1. Check if active tenant has custom SMTP configured
        if (app()->bound(CurrentTenant::class)) {
            $currentTenant = app(CurrentTenant::class);
            $tenant = $currentTenant->has() ? $currentTenant->get() : null;

            if ($tenant && ! empty($tenant->settings['email']['enable_custom_smtp'])) {
                $custom = $tenant->settings['email'];
                $encryption = $custom['mail_encryption'] ?? 'tls';

                Config::set([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $custom['mail_host'] ?? '127.0.0.1',
                    'mail.mailers.smtp.port' => (int) ($custom['mail_port'] ?? 587),
                    'mail.mailers.smtp.encryption' => ($encryption === 'none' || empty($encryption)) ? null : $encryption,
                    'mail.mailers.smtp.username' => $custom['mail_username'] ?? '',
                    'mail.mailers.smtp.password' => self::decryptSecret($custom['mail_password'] ?? ''),
                    'mail.from.address' => $custom['mail_from_address'] ?: config('mail.from.address'),
                    'mail.from.name' => $custom['mail_from_name'] ?: $tenant->name,
                ]);

                return;
            }
        }

        // 2. Fallback to platform-wide SuperAdmin mail settings
        if (! class_exists(PlatformSettings::class)) {
            return;
        }

        $settings = app(PlatformSettings::class);
        $mailSettings = $settings->group('mail');

        if (empty($mailSettings)) {
            return;
        }

        $driver = $mailSettings['driver'] ?? $mailSettings['provider'] ?? 'smtp';
        $host = $mailSettings['host'] ?? '127.0.0.1';
        $port = (int) ($mailSettings['port'] ?? 587);
        $username = $mailSettings['username'] ?? '';
        $password = $mailSettings['password'] ?? '';
        $encryption = $mailSettings['encryption'] ?? 'tls';
        $fromAddress = $mailSettings['from_address'] ?? config('mail.from.address', 'noreply@sathisaas.com');
        $fromName = $mailSettings['from_name'] ?? config('app.name', 'SathiSaaS');

        Config::set([
            'mail.default' => $driver === 'log' ? 'log' : 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.encryption' => ($encryption === 'none' || empty($encryption)) ? null : $encryption,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => self::decryptSecret($password),
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
        ]);
    }

    private static function decryptSecret(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
