<?php

namespace Modules\AI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AI\Services\AiAgentService;

class AiSettingsController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly AiAgentService $aiAgentService,
    ) {}

    /**
     * Test AI Connection with provided or saved API key.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ai_provider' => ['required', 'string', 'in:gemini,openai,groq,ollama'],
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
