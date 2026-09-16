<?php

return [
    'name' => 'AI',

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "gemini", "openai", "groq", "ollama", "deepseek"
    |
    */
    'default_provider' => env('DEFAULT_AI_PROVIDER', 'gemini'),

    'providers' => [
        'gemini' => [
            'name' => 'Google Gemini',
            'api_base_url' => 'https://generativelanguage.googleapis.com/v1beta/models/',
            'default_model' => 'gemini-3.6-flash',
            'available_models' => [
                'gemini-3.6-flash' => 'Gemini 3.6 Flash (Recommended & Fastest)',
                'gemini-3.6-pro' => 'Gemini 3.6 Pro (Advanced Reasoning)',
                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
            ],
            'timeout' => 30,
        ],
        'groq' => [
            'name' => 'Groq Cloud',
            'api_base_url' => 'https://api.groq.com/openai/v1/chat/completions',
            'default_model' => 'llama-3.3-70b-versatile',
            'available_models' => [
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile',
                'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant',
                'mixtral-8x7b-32768' => 'Mixtral 8x7B',
            ],
            'timeout' => 20,
        ],
        'openai' => [
            'name' => 'OpenAI',
            'api_base_url' => 'https://api.openai.com/v1/chat/completions',
            'default_model' => 'gpt-4o-mini',
            'available_models' => [
                'gpt-4o-mini' => 'GPT-4o Mini (Cost Effective)',
                'gpt-4o' => 'GPT-4o (Commercial Grade)',
            ],
            'timeout' => 30,
        ],
        'ollama' => [
            'name' => 'Ollama Local / Self-Hosted',
            'api_base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/api/chat'),
            'default_model' => 'llama3',
            'available_models' => [
                'llama3' => 'Llama 3 (8B)',
                'mistral' => 'Mistral 7B',
                'deepseek-r1' => 'DeepSeek R1 (Local)',
            ],
            'timeout' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Feature Configuration
    |--------------------------------------------------------------------------
    |
    | Defines the required feature slug in tenant's subscription plan.
    |
    */
    'feature_slug' => 'ai_assistant',
    'module_name' => 'ai',
];
