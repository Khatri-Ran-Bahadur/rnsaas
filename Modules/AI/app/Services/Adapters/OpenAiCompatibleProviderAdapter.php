<?php

namespace Modules\AI\Services\Adapters;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Services\Contracts\AiProviderInterface;
use Modules\AI\Services\ModuleToolRegistry;
use Modules\Tenancy\Models\Tenant;

class OpenAiCompatibleProviderAdapter implements AiProviderInterface
{
    public function __construct(
        private readonly string $defaultBaseUrl = 'https://api.openai.com/v1/chat/completions',
        private readonly string $providerName = 'OpenAI',
    ) {}

    /**
     * Test connection for OpenAI compatible endpoints.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $apiKey, string $model, ?string $apiBaseUrl = null): array
    {
        $url = $apiBaseUrl ?? $this->defaultBaseUrl;

        try {
            $response = Http::withToken($apiKey)->timeout(10)->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello! Please reply with: "AI connection successful"'],
                ],
                'max_tokens' => 30,
            ]);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorMsg = $errorData['error']['message'] ?? $response->body();

                return [
                    'success' => false,
                    'message' => "{$this->providerName} API Error: {$errorMsg}",
                ];
            }

            $reply = $response->json('choices.0.message.content') ?? 'Connected!';

            return [
                'success' => true,
                'message' => "{$this->providerName} connection verified! Response: ".trim($reply),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "{$this->providerName} error: ".$e->getMessage(),
            ];
        }
    }

    /**
     * Process chat with OpenAI compatible REST API.
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
    ): array {
        $url = $this->defaultBaseUrl;

        $openAiTools = array_map(function ($tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['parameters'],
                ],
            ];
        }, $tools);

        $formattedMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$messages,
        ];

        try {
            $response = Http::withToken($apiKey)->timeout(30)->post($url, [
                'model' => $model,
                'messages' => $formattedMessages,
                'tools' => $openAiTools,
                'temperature' => 0.4,
            ]);

            if (! $response->successful()) {
                $err = $response->json('error.message') ?? $response->body();
                Log::error("{$this->providerName} API Error for Tenant {$tenant->id}: {$err}");

                return [
                    'role' => 'assistant',
                    'content' => "माफ गर्नुहोस्, {$this->providerName} AI सँग सञ्चार गर्दा समस्या आयो: {$err}",
                    'error' => true,
                ];
            }

            $message = $response->json('choices.0.message');

            if (! empty($message['tool_calls'])) {
                $toolCall = $message['tool_calls'][0];
                $toolName = $toolCall['function']['name'];
                $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                $toolResult = $toolRegistry->execute($toolName, $toolArgs, $tenant);

                $formattedMessages[] = $message;
                $formattedMessages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => json_encode($toolResult),
                ];

                $secondResponse = Http::withToken($apiKey)->timeout(30)->post($url, [
                    'model' => $model,
                    'messages' => $formattedMessages,
                ]);

                if ($secondResponse->successful()) {
                    return [
                        'role' => 'assistant',
                        'content' => $secondResponse->json('choices.0.message.content') ?? 'डेटा प्राप्त भयो।',
                        'tool_executed' => $toolName,
                        'tool_data' => $toolResult,
                    ];
                }
            }

            return [
                'role' => 'assistant',
                'content' => $message['content'] ?? 'कुनै जवाफ प्राप्त भएन।',
            ];
        } catch (Exception $e) {
            Log::error("{$this->providerName} Exception: ".$e->getMessage());

            return [
                'role' => 'assistant',
                'content' => 'त्रुटि (Error): '.$e->getMessage(),
                'error' => true,
            ];
        }
    }
}
