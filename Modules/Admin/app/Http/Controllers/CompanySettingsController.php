<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MailConfigService;
use App\Support\Tenancy\CurrentTenant;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AI\Services\AiAgentService;

class CompanySettingsController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly AiAgentService $aiAgentService,
    ) {}

    /**
     * Show company email, notifications, bank transfer, and AI settings.
     */
    public function edit(): Response
    {
        $tenant = $this->currentTenant->get();
        $settings = $tenant->settings ?? [];
        $isAiModuleEnabled = $tenant->isModuleEnabled('ai') || $tenant->isModuleEnabled('ai_assistant');

        $emailSettings = array_merge([
            'enable_custom_smtp' => false,
            'mail_driver' => 'smtp',
            'mail_host' => '',
            'mail_port' => '587',
            'mail_username' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => '',
            'mail_from_name' => $tenant->name,
            'has_mail_password' => filled($settings['email']['mail_password'] ?? null),
        ], collect($settings['email'] ?? [])->except('mail_password')->all());

        $emailNotifications = array_merge([
            'new_user' => true,
            'customer_invoice_send' => true,
            'payment_reminder' => true,
            'invoice_payment_create' => true,
            'proposal_status_updated' => true,
            'new_helpdesk_ticket' => true,
            'new_helpdesk_ticket_reply' => true,
            'purchase_send' => true,
            'purchase_payment_create' => true,
        ], $settings['email_notifications'] ?? []);

        $bankTransfer = array_merge([
            'enable_bank_transfer' => false,
            'bank_details' => '',
        ], $settings['bank_transfer'] ?? []);

        $aiSettings = array_merge([
            'is_enabled' => true,
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-1.5-flash',
            'custom_system_prompt' => '',
            'has_ai_api_key' => filled($settings['ai']['ai_api_key'] ?? null),
        ], collect($settings['ai'] ?? [])->except('ai_api_key')->all());

        return Inertia::render('Admin/CompanySettings/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'email_settings' => $emailSettings,
            'email_notifications' => $emailNotifications,
            'bank_transfer' => $bankTransfer,
            'ai_settings' => $aiSettings,
            'is_ai_module_enabled' => $isAiModuleEnabled,
        ]);
    }

    /**
     * Update company settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_settings.enable_custom_smtp' => ['boolean'],
            'email_settings.mail_driver' => ['nullable', 'string'],
            'email_settings.mail_host' => ['nullable', 'string', 'max:255'],
            'email_settings.mail_port' => ['nullable', 'string', 'max:10'],
            'email_settings.mail_username' => ['nullable', 'string', 'max:255'],
            'email_settings.mail_password' => ['nullable', 'string', 'max:255'],
            'email_settings.mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'email_settings.mail_from_address' => ['nullable', 'email', 'max:255'],
            'email_settings.mail_from_name' => ['nullable', 'string', 'max:255'],

            'email_notifications.new_user' => ['boolean'],
            'email_notifications.customer_invoice_send' => ['boolean'],
            'email_notifications.payment_reminder' => ['boolean'],
            'email_notifications.invoice_payment_create' => ['boolean'],
            'email_notifications.proposal_status_updated' => ['boolean'],
            'email_notifications.new_helpdesk_ticket' => ['boolean'],
            'email_notifications.new_helpdesk_ticket_reply' => ['boolean'],
            'email_notifications.purchase_send' => ['boolean'],
            'email_notifications.purchase_payment_create' => ['boolean'],

            'bank_transfer.enable_bank_transfer' => ['boolean'],
            'bank_transfer.bank_details' => ['nullable', 'string'],

            'ai_settings.is_enabled' => ['boolean'],
            'ai_settings.ai_provider' => ['required', 'string', 'in:gemini,openai,groq'],
            'ai_settings.ai_model' => ['required', 'string', 'max:100'],
            'ai_settings.ai_api_key' => ['nullable', 'string', 'max:500'],
            'ai_settings.custom_system_prompt' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenant = $this->currentTenant->get();
        $settings = $tenant->settings ?? [];

        if (isset($validated['email_settings'])) {
            $emailSettings = $validated['email_settings'];
            $incomingPassword = $emailSettings['mail_password'] ?? null;
            unset($emailSettings['mail_password']);

            if (filled($incomingPassword)) {
                $emailSettings['mail_password'] = Crypt::encryptString($incomingPassword);
            } elseif (filled($settings['email']['mail_password'] ?? null)) {
                $emailSettings['mail_password'] = $settings['email']['mail_password'];
            }

            $settings['email'] = $emailSettings;
        }

        if (isset($validated['email_notifications'])) {
            $settings['email_notifications'] = $validated['email_notifications'];
        }

        if (isset($validated['bank_transfer'])) {
            $settings['bank_transfer'] = $validated['bank_transfer'];
        }

        if (isset($validated['ai_settings'])) {
            $aiSettings = $validated['ai_settings'];
            $incomingApiKey = $aiSettings['ai_api_key'] ?? null;
            unset($aiSettings['ai_api_key']);

            if (filled($incomingApiKey)) {
                $aiSettings['ai_api_key'] = Crypt::encryptString($incomingApiKey);
            } elseif (filled($settings['ai']['ai_api_key'] ?? null)) {
                $aiSettings['ai_api_key'] = $settings['ai']['ai_api_key'];
            }

            $settings['ai'] = $aiSettings;
        }

        $tenant->settings = $settings;
        $tenant->save();

        return back()->with('success', 'Company settings updated successfully.');
    }

    /**
     * Send test email to verify SMTP connection.
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        try {
            MailConfigService::setDynamicConfig();

            $tenant = $this->currentTenant->get();

            Mail::raw("This is a test notification from {$tenant->name} on SathiSaaS Platform.\nYour email SMTP configuration is working properly!", function ($message) use ($validated, $tenant): void {
                $message->to($validated['test_email'])
                    ->subject("Test Email from {$tenant->name}");
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully! Please check your inbox.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test AI Connection with provided or saved API key.
     */
    public function testAiConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ai_provider' => ['required', 'string', 'in:gemini,openai,groq'],
            'ai_model' => ['required', 'string', 'max:100'],
            'ai_api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = $this->currentTenant->get();
        $savedSettings = $tenant->settings['ai'] ?? [];

        $result = $this->aiAgentService->testConnection(
            settings: array_merge($savedSettings, $validated),
            rawApiKey: $validated['ai_api_key'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
