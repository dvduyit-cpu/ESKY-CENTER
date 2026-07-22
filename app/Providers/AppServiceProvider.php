<?php

namespace App\Providers;

use App\Http\Controllers\ThemeController;
use App\Models\SystemSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $settings = Schema::hasTable('system_settings')
            ? SystemSetting::query()->whereIn('key', ['theme_color', 'software_name', 'logo_path'])->pluck('value', 'key')
            : collect();
        $theme = $settings->get('theme_color', 'blue');

        View::share([
            'systemTheme' => in_array($theme, ThemeController::THEMES, true) ? $theme : 'blue',
            'systemName' => $settings->get('software_name') ?: 'E-SKY CENTER',
            'systemLogo' => $settings->get('logo_path') ?: null,
            'systemCopyright' => '© E-SKY CENTER — Được thiết kế bởi Đặng Việt Duy',
        ]);
    }
}