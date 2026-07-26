<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use App\Support\UserPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonalSettingsController extends Controller
{
    private const THEME_LABELS = [
        'white'=>'Trắng hiện đại', 'gradient-ocean'=>'Gradient đại dương', 'gradient-aurora'=>'Gradient cực quang',
        'gradient-sunset'=>'Gradient hoàng hôn', 'gradient-galaxy'=>'Gradient ngân hà', 'gradient-berry'=>'Gradient berry',
        'gradient-forest'=>'Gradient rừng xanh', 'gradient-candy'=>'Gradient kẹo ngọt', 'gradient-fire'=>'Gradient ánh lửa',
        'pastel-blue'=>'Pastel xanh dương', 'pastel-sky'=>'Pastel xanh trời',
        'pastel-mint'=>'Pastel bạc hà', 'pastel-green'=>'Pastel xanh lá', 'pastel-lavender'=>'Pastel lavender',
        'pastel-purple'=>'Pastel tím', 'pastel-pink'=>'Pastel hồng', 'pastel-peach'=>'Pastel đào',
        'pastel-yellow'=>'Pastel vàng', 'pastel-gray'=>'Pastel xám', 'blue'=>'Xanh dương', 'navy'=>'Xanh navy',
        'azure'=>'Xanh biển sáng', 'sky'=>'Xanh trời', 'cyan'=>'Xanh cyan', 'teal'=>'Xanh ngọc', 'mint'=>'Bạc hà',
        'emerald'=>'Ngọc lục bảo', 'green'=>'Xanh lá', 'lime'=>'Xanh chanh', 'olive'=>'Olive', 'yellow'=>'Vàng',
        'amber'=>'Hổ phách', 'orange'=>'Cam', 'coral'=>'San hô', 'red'=>'Đỏ', 'rose'=>'Hồng đỏ', 'pink'=>'Hồng',
        'fuchsia'=>'Fuchsia', 'purple'=>'Tím', 'violet'=>'Tím violet', 'indigo'=>'Chàm', 'brown'=>'Nâu',
        'slate'=>'Xám xanh', 'graphite'=>'Graphite',
    ];

    public function edit(Request $request): View
    {
        $user = $request->user()->load('preferences');

        return view('personal-settings.edit', [
            'user'=>$user,
            'themes'=>self::THEME_LABELS,
            'landingPages'=>UserPreferences::landingPages($user),
            'defaultPerPage'=>UserPreferences::value($user,'default_per_page'),
            'landingPage'=>UserPreferences::value($user,'landing_page','welcome'),
            'sidebarMode'=>UserPreferences::value($user,'sidebar_mode','remember'),
            'visualEffect'=>UserPreferences::value($user,'visual_effect',null),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user()->load('preferences');
        $landingKeys = array_keys(UserPreferences::landingPages($user));
        $data = $request->validate([
            'theme_color'=>['nullable',Rule::in(ThemeController::THEMES)],
            'default_per_page'=>['nullable','integer',Rule::in([10,20,30,50,100])],
            'landing_page'=>['required',Rule::in($landingKeys)],
            'sidebar_mode'=>['required',Rule::in(['remember','expanded','collapsed'])],
            'visual_effect'=>['nullable',Rule::in(['standard','soft','glass','glow'])],
            'notifications_enabled'=>['nullable','boolean'],
        ]);

        $before = [
            'theme_color'=>$user->theme_color,
            'notifications_enabled'=>$user->notifications_enabled,
            'default_per_page'=>UserPreferences::value($user,'default_per_page'),
            'landing_page'=>UserPreferences::value($user,'landing_page'),
            'sidebar_mode'=>UserPreferences::value($user,'sidebar_mode'),
            'visual_effect'=>UserPreferences::value($user,'visual_effect'),
        ];
        $user->update([
            'theme_color'=>$data['theme_color']??null,
            'notifications_enabled'=>$request->boolean('notifications_enabled'),
        ]);
        foreach (['default_per_page','landing_page','sidebar_mode','visual_effect'] as $key) {
            $value = $data[$key] ?? null;
            if ($value === null || $value === '') $user->preferences()->where('key',$key)->delete();
            else $user->preferences()->updateOrCreate(['key'=>$key],['value'=>(string)$value]);
        }
        $after = $before;
        $after['theme_color']=$user->theme_color;
        $after['notifications_enabled']=$user->notifications_enabled;
        foreach (['default_per_page','landing_page','sidebar_mode','visual_effect'] as $key) $after[$key]=$data[$key]??null;
        ActivityLogger::log('profile','update_preferences','Cập nhật cài đặt cá nhân',$user,$before,$after);

        return back()->with('success','Đã lưu cài đặt cá nhân.');
    }
}
