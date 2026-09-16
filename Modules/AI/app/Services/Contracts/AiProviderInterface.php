<?php

namespace Modules\AI\Services\Contracts;

use Modules\AI\Services\ModuleToolRegistry;
use Modules\Tenancy\Models\Tenant;

interface AiProviderInterface
{
    /**
     * Test connection and verify API Key.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $apiKey, string $model, ?string $apiBaseUrl = null): array;

    /**
     * Process chat with function calling / tools execution.
     *
     * @param  array<int, array<string, string>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    public function chat(
        Tenant $tenant,
        string $apiKey,
        string $model,
        string $systemPrompt,
        array $messages,
        array $tools,
        ModuleToolRegistry $toolRegistry,
    ): array;
}
