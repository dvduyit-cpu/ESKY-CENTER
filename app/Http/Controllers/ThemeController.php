<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public const THEMES = ['blue', 'yellow', 'red', 'green', 'pink', 'purple', 'orange', 'teal', 'indigo', 'slate'];

    public function edit(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('settings.theme', [
            'theme' => SystemSetting::valueOf('theme_color', 'blue'),
            'softwareName' => SystemSetting::valueOf('software_name', 'E-SKY CENTER'),
            'logoPath' => SystemSetting::valueOf('logo_path'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'software_name' => ['required', 'string', 'max:80'],
            'theme_color' => ['required', Rule::in(self::THEMES)],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'software_name.required' => 'Vui lòng nhập tên phần mềm.',
            'logo.image' => 'Logo phải là một tệp hình ảnh.',
            'logo.mimes' => 'Logo chỉ hỗ trợ PNG, JPG, WEBP hoặc SVG.',
            'logo.max' => 'Logo không được lớn hơn 2 MB.',
        ]);

        $logoPath = SystemSetting::valueOf('logo_path');
        if ($request->boolean('remove_logo') || $request->hasFile('logo')) {
            $this->deleteLogo($logoPath);
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            $directory = public_path('uploads/branding');
            File::ensureDirectoryExists($directory);
            $file = $request->file('logo');
            $filename = 'logo-'.now()->format('YmdHis').'.'.$file->extension();
            $file->move($directory, $filename);
            $logoPath = 'uploads/branding/'.$filename;
        }

        SystemSetting::updateOrCreate(['key' => 'software_name'], ['value' => trim($data['software_name'])]);
        SystemSetting::updateOrCreate(['key' => 'theme_color'], ['value' => $data['theme_color']]);
        SystemSetting::updateOrCreate(['key' => 'logo_path'], ['value' => $logoPath]);
        ActivityLogger::log('settings', 'update_branding', 'Cập nhật nhận diện và giao diện phần mềm');

        return back()->with('success', 'Đã cập nhật cấu hình phần mềm.');
    }

    private function deleteLogo(?string $logoPath): void
    {
        if (!$logoPath || !str_starts_with($logoPath, 'uploads/branding/')) {
            return;
        }

        $fullPath = public_path($logoPath);
        if (File::isFile($fullPath)) {
            File::delete($fullPath);
        }
    }
}