<?php

namespace Modules\SuperAdmin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Media\Models\Media;
use Modules\SuperAdmin\Actions\Settings\ClearPlatformSettingsCacheAction;
use Modules\SuperAdmin\Actions\Settings\UpdatePlatformSettingsAction;
use Modules\SuperAdmin\Http\Requests\UpdatePlatformSettingsRequest;
use Modules\SuperAdmin\Services\PlatformSettings;

class PlatformSettingsController
{
    public function __construct(
        private readonly PlatformSettings $settings,
    ) {}

    public function index(): Response
    {
        return Inertia::render('SuperAdmin/Settings/Index', [
            'settings' => [
                'general' => $this->settings->group('general'),
                'branding' => $this->brandingSettings(),
                'system' => $this->settings->group('system'),
                'mail' => $this->mailSettings(),
                'pusher' => $this->settings->group('pusher'),
                'cookie' => $this->settings->group('cookie'),
                'seo' => $this->settings->group('seo'),
                'recaptcha' => $this->settings->group('recaptcha'),
                'bank_transfer' => $this->settings->group('bank_transfer'),
            ],
            'timezones' => timezone_identifiers_list(),
            'currencies' => [
                'USD',
                'EUR',
                'GBP',
                'INR',
                'MYR',
                'NPR',
                'SGD',
                'AED',
                'SAR',
            ],
            'emailProviders' => [
                'smtp' => [
                    'name' => 'Custom SMTP Server',
                    'driver' => 'smtp',
                    'host' => '',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username_placeholder' => 'user@yourdomain.com',
                ],
                'mailgun' => [
                    'name' => 'Mailgun',
                    'driver' => 'smtp',
                    'host' => 'smtp.mailgun.org',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username_placeholder' => 'postmaster@your-domain.mailgun.org',
                ],
                'sendgrid' => [
                    'name' => 'SendGrid',
                    'driver' => 'smtp',
                    'host' => 'smtp.sendgrid.net',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username_placeholder' => 'apikey',
                ],
                'ses' => [
                    'name' => 'Amazon Simple Email Service (SES)',
                    'driver' => 'smtp',
                    'host' => 'email-smtp.us-east-1.amazonaws.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username_placeholder' => 'AWS SES SMTP Username',
                ],
                'postmark' => [
                    'name' => 'Postmark',
                    'driver' => 'smtp',
                    'host' => 'smtp.postmarkapp.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username_placeholder' => 'Postmark Server API Token',
                ],
                'resend' => [
                    'name' => 'Resend',
                    'driver' => 'smtp',
                    'host' => 'smtp.resend.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username_placeholder' => 'resend',
                ],
                'gmail' => [
                    'name' => 'Google Workspace / Gmail SMTP',
                    'driver' => 'smtp',
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username_placeholder' => 'your-email@gmail.com',
                ],
                'log' => [
                    'name' => 'Local Log (Development / Testing)',
                    'driver' => 'log',
                    'host' => '127.0.0.1',
                    'port' => 1025,
                    'encryption' => 'none',
                    'username_placeholder' => 'not-required',
                ],
            ],
        ]);
    }

    public function update(
        UpdatePlatformSettingsRequest $request,
        UpdatePlatformSettingsAction $action,
    ): RedirectResponse {
        $action->execute(
            data: $request->validated(),
            actor: $request->user(),
        );

        return back()->with(
            'success',
            'Platform settings updated successfully.',
        );
    }

    private function mailSettings(): array
    {
        $mail = $this->settings->group('mail');

        /*
         * Never expose the encrypted SMTP password
         * to the browser.
         */
        if (array_key_exists('password', $mail)) {
            $mail['password_configured'] = filled($mail['password']);

            unset($mail['password']);
        } else {
            $mail['password_configured'] = false;
        }

        return $mail;
    }

    private function brandingSettings(): array
    {
        $branding = $this->settings->group('branding');

        if (! empty($branding['logo_media_id'])) {
            $media = Media::query()->find($branding['logo_media_id']);
            if ($media) {
                $branding['logo_url'] = $media->url;
            }
        }

        if (! empty($branding['favicon_media_id'])) {
            $media = Media::query()->find($branding['favicon_media_id']);
            if ($media) {
                $branding['favicon_url'] = $media->url;
            }
        }

        if (! empty($branding['login_logo_media_id'])) {
            $media = Media::query()->find($branding['login_logo_media_id']);
            if ($media) {
                $branding['login_logo_url'] = $media->url;
            }
        }

        return $branding;
    }

    public function clearCache(
        Request $request,
        ClearPlatformSettingsCacheAction $action,
    ): RedirectResponse {
        $action->execute($request->user());

        return back()->with(
            'success',
            'Platform settings cache cleared successfully.',
        );
    }
}
