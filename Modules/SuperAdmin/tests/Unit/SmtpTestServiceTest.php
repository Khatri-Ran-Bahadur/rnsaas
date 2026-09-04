<?php

use Illuminate\Mail\MailManager;
use Modules\SuperAdmin\Services\PlatformSettings;
use Modules\SuperAdmin\Services\SmtpTestService;
use Tests\TestCase;

uses(TestCase::class);

it('builds smtp configuration with fallbacks', function (): void {
    $mailManager = app(MailManager::class);
    $platformSettings = app(PlatformSettings::class);

    $service = new SmtpTestService($mailManager, $platformSettings);

    $config = $service->buildConfig([
        'host' => 'sandbox.smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'eb73aa4b0bff24',
        'password' => '87fb4126577f90',
        'encryption' => 'tls',
    ]);

    expect($config['host'])->toBe('sandbox.smtp.mailtrap.io')
        ->and($config['port'])->toBe(2525)
        ->and($config['username'])->toBe('eb73aa4b0bff24')
        ->and($config['password'])->toBe('87fb4126577f90')
        ->and($config['encryption'])->toBe('tls')
        ->and($config['scheme'])->toBe('smtp');
});

it('uses smtps scheme when port is 465 or encryption is ssl', function (): void {
    $mailManager = app(MailManager::class);
    $platformSettings = app(PlatformSettings::class);

    $service = new SmtpTestService($mailManager, $platformSettings);

    $sslConfig = $service->buildConfig([
        'host' => 'smtp.example.com',
        'port' => 465,
        'encryption' => 'ssl',
    ]);

    expect($sslConfig['scheme'])->toBe('smtps');
});

it('returns failure response when connection cannot be established', function (): void {
    $mailManager = app(MailManager::class);
    $platformSettings = app(PlatformSettings::class);

    $service = new SmtpTestService($mailManager, $platformSettings);

    // Testing an unreachable host/port returns a graceful failure array rather than crashing
    $result = $service->testConnection([
        'host' => '127.0.0.1',
        'port' => 9999,
        'timeout' => 1,
    ]);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('SMTP connection failed');
});
