<?php

namespace Modules\AI\Services\Adapters;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Services\Contracts\AiProviderInterface;
use Modules\AI\Services\ModuleToolRegistry;
use Modules\Tenancy\Models\Tenant;

class GeminiProviderAdapter implements AiProviderInterface
{
    /**
     * Test Gemini API connection.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $apiKey, string $model, ?string $apiBaseUrl = null): array
    {
        $cleanModel = preg_replace('/^models\//', '', trim($model));
        $baseUrl = $apiBaseUrl ?? 'https://generativelanguage.googleapis.com/v1beta/models/';
        $url = rtrim($baseUrl, '/')."/{$cleanModel}:generateContent?key={$apiKey}";

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(45)->connectTimeout(15)->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => 'Hello! Reply with exactly: "AI connection successful"'],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text') ?? 'Connected!';

                return [
                    'success' => true,
                    'message' => "Gemini connection verified with model [{$cleanModel}]! Response: ".trim($text),
                ];
            }

            // Auto-detect recommended model from error
            $errorData = $response->json();
            $errorMsg = $errorData['error']['message'] ?? $response->body();

            if (preg_match('/models\/(gemini-[\w.-]+)/i', $errorMsg, $matches)) {
                $recommendedModel = $matches[1];
                $retryUrl = rtrim($baseUrl, '/')."/{$recommendedModel}:generateContent?key={$apiKey}";
                $retryResponse = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->connectTimeout(15)->post($retryUrl, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => 'Hello! Reply with: "AI connection successful"']],
                        ],
                    ],
                ]);

                if ($retryResponse->successful()) {
                    $retryText = $retryResponse->json('candidates.0.content.parts.0.text') ?? 'Connected!';

                    return [
                        'success' => true,
                        'message' => "Gemini connection verified with Google's recommended model [{$recommendedModel}]! Response: ".trim($retryText),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => "Gemini API Error: {$errorMsg}",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Gemini connection error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Process chat with Google Gemini REST API.
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
        $cleanModel = preg_replace('/^models\//', '', trim($model));
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$cleanModel}:generateContent?key={$apiKey}";

        $formattedTools = $this->formatGeminiTools($tools);
        $geminiTools = ! empty($formattedTools) ? [['function_declarations' => $formattedTools]] : [];

        $contents = [];
        foreach ($messages as $msg) {
            $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $msg['content']],
                ],
            ];
        }

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 2048,
            ],
        ];

        if (! empty($geminiTools)) {
            $payload['tools'] = $geminiTools;
        }

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->connectTimeout(15)->post($url, $payload);

            if (! $response->successful()) {
                $err = $response->json('error.message') ?? $response->body();

                // If model is deprecated / not available, try with recommended model
                if (preg_match('/models\/(gemini-[\w.-]+)/i', $err, $matches) || $cleanModel !== 'gemini-3.6-flash') {
                    $retryModel = $matches[1] ?? 'gemini-3.6-flash';
                    $retryUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$retryModel}:generateContent?key={$apiKey}";
                    $retryResponse = Http::withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(60)->connectTimeout(15)->post($retryUrl, $payload);

                    if ($retryResponse->successful()) {
                        $response = $retryResponse;
                    } else {
                        Log::error("Gemini API Error for Tenant {$tenant->id}: {$err}");

                        return [
                            'role' => 'assistant',
                            'content' => "माफ गर्नुहोस्, Gemini AI सँग सञ्चार गर्दा समस्या आयो: {$err}",
                            'error' => true,
                        ];
                    }
                } else {
                    Log::error("Gemini API Error for Tenant {$tenant->id}: {$err}");

                    return [
                        'role' => 'assistant',
                        'content' => "माफ गर्नुहोस्, Gemini AI सँग सञ्चार गर्दा समस्या आयो: {$err}",
                        'error' => true,
                    ];
                }
            }

            $parts = $response->json('candidates.0.content.parts') ?? [];
            $functionCall = null;
            $initialText = '';

            foreach ($parts as $part) {
                if (isset($part['functionCall'])) {
                    $functionCall = $part['functionCall'];
                    break;
                }
                if (isset($part['text']) && empty($part['thought'])) {
                    $initialText .= $part['text'];
                }
            }

            if ($functionCall) {
                $toolName = $functionCall['name'];
                $args = $functionCall['args'] ?? [];

                // Execute securely scoped to tenant
                $toolResult = $toolRegistry->execute($toolName, $args, $tenant);

                $contents[] = [
                    'role' => 'model',
                    'parts' => [
                        ['functionCall' => $functionCall],
                    ],
                ];

                $contents[] = [
                    'role' => 'function',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $toolName,
                                'response' => [
                                    'name' => $toolName,
                                    'content' => $toolResult,
                                ],
                            ],
                        ],
                    ],
                ];

                $secondPayload = [
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                ];

                $secondResponse = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(60)->connectTimeout(15)->post($url, $secondPayload);

                $finalText = '';
                if ($secondResponse->successful()) {
                    $secondParts = $secondResponse->json('candidates.0.content.parts') ?? [];
                    foreach ($secondParts as $p) {
                        if (isset($p['text']) && empty($p['thought'])) {
                            $finalText .= $p['text'];
                        }
                    }
                }

                // If Gemini second response was empty, generate friendly fallback text from toolResult
                if (empty(trim($finalText))) {
                    $finalText = $this->formatFallbackToolResponse($toolName, $toolResult, $tenant);
                }

                return [
                    'role' => 'assistant',
                    'content' => trim($finalText),
                    'tool_executed' => $toolName,
                    'tool_data' => $toolResult,
                ];
            }

            $text = ! empty(trim($initialText)) ? trim($initialText) : 'सोधिएका प्रश्नको जवाफ प्राप्त भएन। कृपया आफ्नो प्रश्न स्पष्ट गरी पुन: सोध्नुहोस्।';

            return [
                'role' => 'assistant',
                'content' => $text,
            ];
        } catch (Exception $e) {
            Log::error('Gemini Exception: '.$e->getMessage());

            $msg = $e->getMessage();
            if (str_contains($msg, 'timed out') || str_contains($msg, 'cURL error 28')) {
                $msg = 'गुगल सर्भर प्रतिक्रिया ढिलो भई टाइमआउट भयो (Request Timed Out)। कृपया पुन: प्रयास गर्नुहोस्।';
            }

            return [
                'role' => 'assistant',
                'content' => 'त्रुटि (Error): '.$msg,
                'error' => true,
            ];
        }
    }

    /**
     * Format a guaranteed Nepali fallback response if LLM summary misses.
     *
     * @param  array<string, mixed>  $data
     */
    private function formatFallbackToolResponse(string $toolName, array $data, Tenant $tenant): string
    {
        $currency = $tenant->currency ?? 'NPR';

        return match ($toolName) {
            'get_financial_summary' => sprintf(
                "📊 **%s को फाइनान्सियल सारांश (Financial Summary):**\n\n- **बिक्री आम्दानी (Sales Revenue):** %s %s\n- **खरिद खर्च (Purchase Expenses):** %s %s\n- **खुद नाफा / नोक्सान (Net Profit/Loss):** %s %s\n- **मार्जिन प्रतिशत (Margin):** %s%%\n\n*अवधि (Period): %s देखि %s सम्म*",
                $tenant->name,
                $currency,
                number_format((float) ($data['total_sales_revenue'] ?? 0), 2),
                $currency,
                number_format((float) ($data['total_purchase_expenses'] ?? 0), 2),
                $currency,
                number_format((float) ($data['net_profit_or_loss'] ?? 0), 2),
                $data['margin_percentage'] ?? 0,
                $data['from'] ?? '',
                $data['to'] ?? ''
            ),
            'get_sales_overview' => sprintf(
                "📈 **%s को बिक्री सारांश (%s दिनको):**\n\n- **जम्मा इनभ्वाइसहरू:** %d\n- **प्राप्त आम्दानी (Paid Revenue):** %s %s\n- **उठ्न बाँकी रकम (Unpaid Amount):** %s %s",
                $tenant->name,
                $data['period_days'] ?? 30,
                $data['total_invoices_count'] ?? 0,
                $currency,
                number_format((float) ($data['total_paid_revenue'] ?? 0), 2),
                $currency,
                number_format((float) ($data['total_unpaid_amount'] ?? 0), 2)
            ),
            'get_unpaid_invoices' => sprintf(
                "⚠️ **उठ्न बाँकी इनभ्वाइसहरू (Unpaid Invoices):**\n\nहाल जम्मा **%d** वटा इनभ्वाइसहरूको भुक्तानी बाँकी छ।",
                $data['unpaid_invoices_count'] ?? 0
            ),
            'get_inventory_status' => sprintf(
                "📦 **इन्भेन्टरी स्टक स्थिति:**\n\n- **जम्मा उत्पादन संख्या:** %d\n- **जम्मा स्टक मूल्याङ्कन:** %s %s",
                $data['total_catalog_items'] ?? 0,
                $currency,
                number_format((float) ($data['total_inventory_valuation'] ?? 0), 2)
            ),
            'get_pos_sales_summary' => sprintf(
                "🛒 **POS बिक्री सारांश (%s):**\n\n- **जम्मा अर्डर संख्या:** %d\n- **जम्मा बिक्री:** %s %s\n- **नगद (Cash):** %s %s\n- **अनलाइन / कार्ड:** %s %s",
                $data['date'] ?? 'आज',
                $data['total_orders'] ?? 0,
                $currency,
                number_format((float) ($data['total_sales'] ?? 0), 2),
                $currency,
                number_format((float) ($data['cash_sales'] ?? 0), 2),
                $currency,
                number_format((float) ($data['card_or_online_sales'] ?? 0), 2)
            ),
            default => 'डेटा प्राप्त भयो: '.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        };
    }

    /**
     * Format OpenAPI function declarations for Gemini schema (uppercase types).
     *
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function formatGeminiTools(array $tools): array
    {
        return array_map(function ($tool) {
            $formatted = [
                'name' => $tool['name'],
                'description' => $tool['description'] ?? '',
            ];

            if (isset($tool['parameters'])) {
                $formatted['parameters'] = $this->uppercaseSchemaTypes($tool['parameters']);
            }

            return $formatted;
        }, $tools);
    }

    /**
     * Recursively convert schema types to uppercase for Gemini (OBJECT, STRING, INTEGER, etc.).
     */
    private function uppercaseSchemaTypes(mixed $schema): mixed
    {
        if (! is_array($schema)) {
            return $schema;
        }

        $result = [];
        foreach ($schema as $key => $val) {
            if ($key === 'type' && is_string($val)) {
                $result[$key] = strtoupper($val);
            } elseif (is_array($val)) {
                $result[$key] = $this->uppercaseSchemaTypes($val);
            } else {
                $result[$key] = $val;
            }
        }

        return $result;
    }
}
