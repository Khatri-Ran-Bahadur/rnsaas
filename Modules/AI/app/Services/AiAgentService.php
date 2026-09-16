<?php

namespace Modules\AI\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Modules\AI\Services\Adapters\GeminiProviderAdapter;
use Modules\AI\Services\Adapters\OpenAiCompatibleProviderAdapter;
use Modules\AI\Services\Contracts\AiProviderInterface;
use Modules\Tenancy\Models\Tenant;

class AiAgentService
{
    public function __construct(
        private readonly ModuleToolRegistry $toolRegistry,
    ) {}

    /**
     * Resolve provider adapter instance.
     */
    public function getAdapter(string $provider): AiProviderInterface
    {
        return match ($provider) {
            'gemini' => new GeminiProviderAdapter,
            'groq' => new OpenAiCompatibleProviderAdapter(
                defaultBaseUrl: 'https://api.groq.com/openai/v1/chat/completions',
                providerName: 'Groq Cloud',
            ),
            'openai' => new OpenAiCompatibleProviderAdapter(
                defaultBaseUrl: 'https://api.openai.com/v1/chat/completions',
                providerName: 'OpenAI',
            ),
            default => new GeminiProviderAdapter,
        };
    }

    /**
     * Test connection and verify API key for a tenant.
     *
     * @param  array<string, mixed>  $settings
     * @return array{success: bool, message: string}
     */
    public function testConnection(array $settings, ?string $rawApiKey = null): array
    {
        $provider = $settings['ai_provider'] ?? 'gemini';
        $model = $settings['ai_model'] ?? ($provider === 'gemini' ? 'gemini-1.5-flash' : 'gpt-4o-mini');

        $apiKey = $rawApiKey;
        if (empty($apiKey) && ! empty($settings['ai_api_key'])) {
            try {
                $apiKey = Crypt::decryptString($settings['ai_api_key']);
            } catch (Exception) {
                $apiKey = $settings['ai_api_key'];
            }
        }

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key is missing. Please enter a valid API key.',
            ];
        }

        $adapter = $this->getAdapter($provider);

        return $adapter->testConnection($apiKey, $model);
    }

    /**
     * Process chat prompt for tenant.
     *
     * @param  array<int, array<string, string>>  $messages
     * @return array<string, mixed>
     */
    public function chat(Tenant $tenant, array $messages): array
    {
        $tenantSettings = $tenant->settings['ai'] ?? [];
        $provider = $tenantSettings['ai_provider'] ?? 'gemini';
        $model = $tenantSettings['ai_model'] ?? ($provider === 'gemini' ? 'gemini-3.6-flash' : 'gpt-4o-mini');
        if ($provider === 'gemini' && in_array($model, ['gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-2.0-flash-exp'])) {
            $model = 'gemini-3.6-flash';
        }

        $encryptedKey = $tenantSettings['ai_api_key'] ?? null;
        if (empty($encryptedKey)) {
            return [
                'role' => 'assistant',
                'content' => "⚠️ **AI Assistant Setup Required**\n\nयस कम्पनीको AI API Key कन्फिगर गरिएको छैन। कृपया **Company Settings -> 🤖 AI Assistant** मा गएर आफ्नो Google Gemini (Free) वा OpenAI Key सेटअप गर्नुहोस्।",
                'is_configured' => false,
            ];
        }

        try {
            $apiKey = Crypt::decryptString($encryptedKey);
        } catch (Exception) {
            $apiKey = $encryptedKey;
        }

        $systemPrompt = $this->buildSystemPrompt($tenant, $tenantSettings['custom_system_prompt'] ?? null);
        $adapter = $this->getAdapter($provider);
        $tools = $this->toolRegistry->getToolDeclarations();

        return $adapter->chat($tenant, $apiKey, $model, $systemPrompt, $messages, $tools, $this->toolRegistry);
    }

    /**
     * Build the multi-tenant contextual system prompt with security guardrails.
     */
    private function buildSystemPrompt(Tenant $tenant, ?string $customPrompt): string
    {
        $tenantName = $tenant->name;
        $currency = $tenant->currency ?? 'NPR';
        $currentTime = Carbon::now()->toDateTimeString();

        $basePrompt = <<<PROMPT
You are "Sathi AI Copilot", an expert, helpful, and highly intelligent business advisor and ERP assistant integrated inside SathiSaaS ERP/CRM.

Context:
- Current Company / Tenant: "{$tenantName}" (ID: {$tenant->id})
- Default Currency: {$currency}
- Current Date/Time: {$currentTime}

Your Capabilities:
- You have access to real-time tools to query sales, invoices, inventory stock, POS sales, and financial profit & loss for "{$tenantName}".
- When the user asks about data (e.g. sales, unpaid bills, low stock, profits), use the appropriate tool to fetch live data before answering.
- Respond in clear, friendly, and professional language. If the user asks in Nepali, respond in fluent Nepali with English terminology where helpful. If in English, respond in English.
- Format responses beautifully with Markdown headings, bullet points, and tables when presenting data.

Security Guardrails:
- Strictly never disclose internal passwords, API keys, or raw database connection strings.
- Only provide information related to "{$tenantName}".
PROMPT;

        if (! empty($customPrompt)) {
            $basePrompt .= "\n\nAdditional Company Instructions:\n".$customPrompt;
        }

        return $basePrompt;
    }
}
