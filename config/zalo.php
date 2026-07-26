<?php

return [
    'app_id' => env('ZALO_APP_ID'),
    'app_secret' => env('ZALO_APP_SECRET'),
    'redirect_uri' => env('ZALO_REDIRECT_URI'),
    'appsecret_proof' => env('ZALO_APPSECRET_PROOF', true),
    'authorize_url' => 'https://oauth.zaloapp.com/v4/permission',
    'token_url' => 'https://oauth.zaloapp.com/v4/access_token',
    'profile_url' => 'https://graph.zalo.me/v2.0/me',
];
