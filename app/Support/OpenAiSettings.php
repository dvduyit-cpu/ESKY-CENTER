<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

class OpenAiSettings
{
    public const MODELS = ['gpt-5.6-luna', 'gpt-5.6-terra', 'gpt-5.6-sol'];

    public function enabled(): bool
    {
        $stored = SystemSetting::valueOf('openai_enabled');

        return $stored === null ? $this->apiKey() !== '' : $stored === '1';
    }

    public function apiKey(): string
    {
        $encrypted = (string) SystemSetting::valueOf('openai_api_key_encrypted', '');

        if ($encrypted !== '') {
            try {
                return trim(Crypt::decryptString($encrypted));
            } catch (\Throwable) {
                // APP_KEY may have changed; the environment key remains a safe fallback.
            }
        }

        return trim((string) config('ai.openai.api_key'));
    }

    public function hasStoredApiKey(): bool
    {
        return (string) SystemSetting::valueOf('openai_api_key_encrypted', '') !== '';
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey() !== '';
    }

    public function model(): string
    {
        $model = (string) SystemSetting::valueOf('openai_report_model', config('ai.openai.model', 'gpt-5.6-luna'));

        return in_array($model, self::MODELS, true) ? $model : 'gpt-5.6-luna';
    }

    public function timeout(): int
    {
        $timeout = (int) SystemSetting::valueOf('openai_timeout', config('ai.openai.timeout', 45));

        return in_array($timeout, [15, 30, 45, 60, 90], true) ? $timeout : 45;
    }
}
