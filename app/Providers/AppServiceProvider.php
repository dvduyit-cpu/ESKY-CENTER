<?php

namespace App\Providers;

use App\Http\Controllers\ThemeController;
use App\Models\SystemSetting;
use App\Models\UpcomingPlan;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('pagination.app');

        $settings = Schema::hasTable('system_settings')
            ? SystemSetting::query()->whereIn('key', ['theme_color', 'software_name', 'logo_path', 'loading_style', 'footer_text'])->pluck('value', 'key')
            : collect();
        $theme = $settings->get('theme_color', 'blue');

        View::share([
            'systemTheme' => in_array($theme, ThemeController::THEMES, true) ? $theme : 'blue',
            'systemName' => $settings->get('software_name') ?: 'E-SKY CENTER',
            'systemLogo' => $settings->get('logo_path') ?: null,
            'systemLoadingStyle' => in_array($settings->get('loading_style'), ['center', 'top'], true) ? $settings->get('loading_style') : 'center',
            'systemCopyright' => e($settings->get('footer_text') ?: '© 2026 E-sky center v1.0.0 | Phát triển bởi Đặng Việt Duy'),
        ]);

        View::composer('layouts.app', function ($view): void {
            $reminders = collect();
            if (Auth::check() && (bool) Auth::user()->notifications_enabled && Schema::hasTable('upcoming_plans')) {
                $reminders = UpcomingPlan::query()
                    ->where('user_id', Auth::id())
                    ->whereNull('completed_at')
                    ->where('scheduled_for', '<=', now()->addDays(30)->endOfDay())
                    ->orderBy('scheduled_for')
                    ->get()
                    ->filter->is_due_for_reminder
                    ->take(10)
                    ->values();
            }
            $view->with('planReminders', $reminders);
        });
    }
}
