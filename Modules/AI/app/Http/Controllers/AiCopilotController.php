<?php

namespace Modules\AI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AI\Services\AiAgentService;

class AiCopilotController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly AiAgentService $aiAgentService,
    ) {}

    /**
     * Get AI Copilot status and subscription capability for the current tenant.
     */
    public function status(): JsonResponse
    {
        $tenant = $this->currentTenant->get();
        $isModuleEnabled = $tenant->isModuleEnabled('ai') || $tenant->isModuleEnabled('ai_assistant');
        $aiSettings = $tenant->settings['ai'] ?? [];

        $hasApiKey = filled($aiSettings['ai_api_key'] ?? null);
        $isEnabled = (bool) ($aiSettings['is_enabled'] ?? true);
        $provider = $aiSettings['ai_provider'] ?? 'gemini';
        $model = $aiSettings['ai_model'] ?? 'gemini-1.5-flash';

        return response()->json([
            'is_module_enabled' => $isModuleEnabled,
            'is_configured' => $hasApiKey,
            'is_enabled' => $isEnabled,
            'provider' => $provider,
            'model' => $model,
            'tenant_name' => $tenant->name,
        ]);
    }

    /**
     * Process chat prompt with the AI Copilot.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant,system'],
            'messages.*.content' => ['required', 'string', 'max:5000'],
        ]);

        $tenant = $this->currentTenant->get();
        $aiSettings = $tenant->settings['ai'] ?? [];

        if (isset($aiSettings['is_enabled']) && ! $aiSettings['is_enabled']) {
            return response()->json([
                'role' => 'assistant',
                'content' => 'AI Copilot is currently disabled in your Company Settings.',
                'is_disabled' => true,
            ]);
        }

        $result = $this->aiAgentService->chat($tenant, $validated['messages']);

        return response()->json($result);
    }
}
