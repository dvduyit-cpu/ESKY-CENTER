<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_REPORT_MODEL', 'gpt-5.6-luna'),
        'endpoint' => env('OPENAI_RESPONSES_URL', 'https://api.openai.com/v1/responses'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 45),
    ],
];
