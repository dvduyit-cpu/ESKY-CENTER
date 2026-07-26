<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Support\UserPreferences;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $email = trim((string) $request->input('email'));
        if ($email !== '' && ! str_contains($email, '@')) {
            $email .= '@'.config('auth.login_email_domain');
        }
        $request->merge(['email' => mb_strtolower($email)]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $remember = $request->boolean('remember');
        $knownUser = User::withTrashed()->where('email', $credentials['email'])->first();

        if (! Auth::attempt(array_merge($credentials, ['active' => 1]), $remember)) {
            LoginLog::create([
                'user_id' => $knownUser?->id,
                'email' => $credentials['email'],
                'event' => 'login_failed',
                'success' => false,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
            return back()->withErrors(['email' => 'Email, mật khẩu không đúng hoặc tài khoản đã bị khóa.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'event' => 'login_success',
            'success' => true,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);

        return redirect()->route(UserPreferences::landingRoute($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            LoginLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => 'logout',
                'success' => true,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
