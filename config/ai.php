<?php

return [
    'google' => [
        'api_key' => env('GOOGLE_AI_API_KEY'),
        'model' => env('GOOGLE_AI_MODEL', 'gemini-1.5-flash'),
        'max_tokens' => env('GOOGLE_AI_MAX_TOKENS', 1000),
        'demo_mode' => env('GOOGLE_AI_DEMO_MODE', false),
    ],
];
