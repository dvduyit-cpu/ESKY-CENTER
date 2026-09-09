<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_REPORT_MODEL', 'gpt-5.6-luna'),
        'endpoint' => env('OPENAI_RESPONSES_URL', 'https://api.openai.com/v1/responses'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 45),
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_REPORT_MODEL', 'gemini-2.5-flash'),
        'endpoint' => env('GEMINI_GENERATE_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 45),
    ],
];
