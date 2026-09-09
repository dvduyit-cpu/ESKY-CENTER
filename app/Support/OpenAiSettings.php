<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

class OpenAiSettings
{
    public const PROVIDERS = [
        'openai' => 'OpenAI',
        'gemini' => 'Google Gemini',
    ];

    public const MODELS = [
        'openai' => [
            'gpt-5.6-luna' => 'GPT-5.6 Luna — nhanh, tiết kiệm',
            'gpt-5.6-terra' => 'GPT-5.6 Terra — cân bằng',
            'gpt-5.6-sol' => 'GPT-5.6 Sol — chất lượng cao',
        ],
        'gemini' => [
            'gemini-2.5-flash' => 'Gemini 2.5 Flash — nhanh, tiết kiệm',
            'gemini-2.5-pro' => 'Gemini 2.5 Pro — phân tích kỹ',
        ],
    ];

    public function provider(): string
    {
        $provider = (string) SystemSetting::valueOf('ai_report_provider', 'openai');

        return array_key_exists($provider, self::PROVIDERS) ? $provider : 'openai';
    }

    public function enabled(): bool
    {
        $stored = SystemSetting::valueOf('ai_enabled');
        $stored ??= SystemSetting::valueOf('openai_enabled');

        return $stored === null ? $this->apiKey() !== '' : $stored === '1';
    }

    public function apiKey(): string
    {
        $encrypted = (string) SystemSetting::valueOf($this->apiKeySetting(), '');
        if ($encrypted === '' && $this->provider() === 'openai') {
            $encrypted = (string) SystemSetting::valueOf('openai_api_key_encrypted', '');
        }

        if ($encrypted !== '') {
            try {
                return trim(Crypt::decryptString($encrypted));
            } catch (\Throwable) {
                // APP_KEY may have changed; the environment key remains a safe fallback.
            }
        }

        return trim((string) config('ai.'.$this->provider().'.api_key'));
    }

    public function hasStoredApiKey(): bool
    {
        return (string) SystemSetting::valueOf($this->apiKeySetting(), '') !== ''
            || ($this->provider() === 'openai' && (string) SystemSetting::valueOf('openai_api_key_encrypted', '') !== '');
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey() !== '';
    }

    public function model(): string
    {
        $model = (string) SystemSetting::valueOf('ai_report_model', '');
        if ($model === '') {
            $model = $this->provider() === 'openai'
                ? (string) SystemSetting::valueOf('openai_report_model', config('ai.openai.model', 'gpt-5.6-luna'))
                : (string) config('ai.'.$this->provider().'.model');
        }

        return $this->validModel($this->provider(), $model)
            ? $model
            : array_key_first(self::MODELS[$this->provider()]);
    }

    public function timeout(): int
    {
        $timeout = (int) SystemSetting::valueOf('ai_timeout', SystemSetting::valueOf('openai_timeout', config('ai.'.$this->provider().'.timeout', 45)));

        return in_array($timeout, [15, 30, 45, 60, 90], true) ? $timeout : 45;
    }

    public function validModel(string $provider, string $model): bool
    {
        if (! array_key_exists($provider, self::PROVIDERS)) return false;

        return array_key_exists($model, self::MODELS[$provider]);
    }

    public function apiKeySetting(): string
    {
        return 'ai_'.$this->provider().'_api_key_encrypted';
    }
}
