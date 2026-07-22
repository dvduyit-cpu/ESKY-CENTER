<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()->load('personnel', 'role')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'email' => ['required','email','max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable','string','max:30'],
            'notifications_enabled' => ['nullable','boolean'],
        ]);
        $before = $user->only(['name','email','notifications_enabled']);
        $user->update(['name'=>$data['name'],'email'=>$data['email'],'notifications_enabled'=>$request->boolean('notifications_enabled')]);
        if ($user->personnel) $user->personnel->update(['name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']]);
        ActivityLogger::log('profile','update','Cập nhật hồ sơ cá nhân',$user,$before,$user->only(['name','email','notifications_enabled']));
        return back()->with('success','Đã cập nhật hồ sơ và cài đặt thông báo.');
    }

    public function password(): View { return view('profile.password'); }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data=$request->validate(['current_password'=>['required','current_password'],'password'=>['required','string','min:8','confirmed']]);
        $user=$request->user();
        $user->update(['password'=>Hash::make($data['password']),'must_change_password'=>false]);
        ActivityLogger::log('profile','change_password','Đổi mật khẩu cá nhân',$user);
        return redirect()->route('welcome')->with('success','Đã đổi mật khẩu.');
    }
}
