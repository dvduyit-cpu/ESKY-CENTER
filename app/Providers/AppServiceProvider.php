<?php

namespace App\Providers;

use App\Http\Controllers\ThemeController;
use App\Models\SystemSetting;
use App\Models\UpcomingPlan;
use App\Models\LanguageLead;
use App\Models\LanguageMonthlyTargetRecord;
use App\Models\LanguageTargetSubmission;
use App\Models\LanguageTuitionPayment;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;
use App\Support\UserPreferences;
use App\Support\RealtimeNotifier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('pagination.app');

        foreach ([LanguageLead::class, LanguageMonthlyTargetRecord::class, LanguageTargetSubmission::class, LanguageTuitionPayment::class] as $model) {
            $model::saved(fn () => RealtimeNotifier::system());
            $model::deleted(fn () => RealtimeNotifier::system());
        }
        UpcomingPlan::created(fn (UpcomingPlan $plan) => RealtimeNotifier::user(
            $plan->user_id,
            ($plan->kind === 'task' ? 'Bạn được giao công việc: ' : 'Lịch nhắc mới: ').$plan->title
        ));
        UpcomingPlan::updated(fn (UpcomingPlan $plan) => RealtimeNotifier::user($plan->user_id, 'Đã cập nhật: '.$plan->title));
        UpcomingPlan::deleted(fn (UpcomingPlan $plan) => RealtimeNotifier::user($plan->user_id, 'Đã xóa: '.$plan->title));

        try {
            $settings = Schema::hasTable('system_settings')
                ? SystemSetting::query()->whereIn('key', ['theme_color', 'software_name', 'logo_path', 'loading_style', 'visual_effect', 'footer_text'])->pluck('value', 'key')
                : collect();
        } catch (Throwable) {
            $settings = collect();
        }
        $theme = $settings->get('theme_color', 'blue');
        $systemTheme = in_array($theme, ThemeController::THEMES, true) ? $theme : 'blue';
        $systemVisualEffect = in_array($settings->get('visual_effect'), ['standard','soft','glass','glow'], true) ? $settings->get('visual_effect') : 'standard';
        $logoPath = $settings->get('logo_path');
        $systemLogo = is_string($logoPath)
            && str_starts_with($logoPath, 'uploads/branding/')
            && is_file(public_path($logoPath))
                ? $logoPath
                : null;

        View::share([
            'systemTheme' => $systemTheme,
            'defaultSystemTheme' => $systemTheme,
            'systemName' => $settings->get('software_name') ?: 'E-SKY CENTER',
            'systemLogo' => $systemLogo,
            'systemLoadingStyle' => in_array($settings->get('loading_style'), ['center', 'top'], true) ? $settings->get('loading_style') : 'center',
            'systemVisualEffect' => $systemVisualEffect,
            'systemCopyright' => e($settings->get('footer_text') ?: '© 2026 E-sky center v1.0.0 | Phát triển bởi Đặng Việt Duy'),
        ]);

        View::composer('layouts.app', function ($view) use ($systemTheme, $systemVisualEffect): void {
            $reminders = collect();
            $personalTheme = Auth::check() ? Auth::user()->theme_color : null;
            $sidebarMode = Auth::check() ? UserPreferences::value(Auth::user(), 'sidebar_mode', 'remember') : 'remember';
            $visualEffect = Auth::check() ? UserPreferences::value(Auth::user(), 'visual_effect', $systemVisualEffect) : $systemVisualEffect;
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
            $view->with([
                'planReminders' => $reminders,
                'systemTheme' => in_array($personalTheme, ThemeController::THEMES, true) ? $personalTheme : $systemTheme,
                'personalSidebarMode' => in_array($sidebarMode, ['remember','expanded','collapsed'], true) ? $sidebarMode : 'remember',
                'personalVisualEffect' => in_array($visualEffect, ['standard','soft','glass','glow'], true) ? $visualEffect : 'standard',
            ]);
        });
    }
}
