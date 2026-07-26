<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\User;
use App\Support\UserPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ZaloAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        return $this->begin($request, 'login');
    }

    public function connect(Request $request): RedirectResponse
    {
        return $this->begin($request, 'connect');
    }

    public function callback(Request $request): RedirectResponse
    {
        $mode = $request->session()->pull('zalo_oauth_mode');
        $expectedState = $request->session()->pull('zalo_oauth_state');
        $verifier = $request->session()->pull('zalo_oauth_verifier');
        if (! $mode || ! $expectedState || ! hash_equals($expectedState, (string) $request->query('state')) || ! $verifier) {
            return $this->failure($mode, 'Phiên xác thực Zalo không hợp lệ hoặc đã hết hạn.');
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->failure($mode, 'Bạn đã hủy hoặc Zalo từ chối yêu cầu đăng nhập.');
        }

        try {
            $tokenResponse = Http::asForm()->withHeaders(['secret_key' => config('zalo.app_secret')])
                ->timeout(10)->post(config('zalo.token_url'), [
                    'app_id' => config('zalo.app_id'),
                    'code' => $request->string('code')->toString(),
                    'grant_type' => 'authorization_code',
                    'code_verifier' => $verifier,
                ])->throw()->json();
            $token = is_array($tokenResponse['data'] ?? null) ? $tokenResponse['data'] : $tokenResponse;
            $accessToken = $token['access_token'] ?? null;
            if (! $accessToken) throw new \RuntimeException('Zalo không trả về access token.');
            $profileHeaders = ['access_token' => $accessToken];
            if (config('zalo.appsecret_proof')) {
                $profileHeaders['appsecret_proof'] = hash_hmac('sha256', $accessToken, (string) config('zalo.app_secret'));
            }
            $profileResponse = Http::withHeaders($profileHeaders)->timeout(10)
                ->get(config('zalo.profile_url'), ['fields' => 'id,name,picture'])
                ->throw()->json();
            $profile = is_array($profileResponse['data'] ?? null) ? $profileResponse['data'] : $profileResponse;
            $zaloId = trim((string) ($profile['id'] ?? ''));
            if ($zaloId === '') {
                Log::warning('Zalo không trả về hồ sơ hợp lệ.', [
                    'error_code' => $profileResponse['error'] ?? $profileResponse['error_code'] ?? null,
                    'message' => $profileResponse['message'] ?? $profileResponse['error_name'] ?? null,
                    'keys' => array_keys($profileResponse),
                ]);
                throw new \RuntimeException('Không đọc được mã tài khoản Zalo.');
            }
        } catch (Throwable $exception) {
            report($exception);
            return $this->failure($mode, 'Không thể kết nối Zalo lúc này. Vui lòng thử lại.');
        }

        if ($mode === 'connect') {
            $user = $request->user();
            if (! $user) return redirect()->route('login')->withErrors(['email' => 'Vui lòng đăng nhập lại trước khi liên kết Zalo.']);
            if (User::where('zalo_id', $zaloId)->whereKeyNot($user->id)->exists()) {
                return redirect()->route('personal-settings.edit')->with('warning', 'Tài khoản Zalo này đã liên kết với một tài khoản khác.');
            }
            $user->update(['zalo_id' => $zaloId, 'zalo_name' => $profile['name'] ?? null, 'zalo_linked_at' => now()]);
            return redirect()->route('personal-settings.edit')->with('success', 'Đã liên kết tài khoản Zalo thành công.');
        }

        $user = User::where('zalo_id', $zaloId)->where('active', true)->first();
        if (! $user) return redirect()->route('login')->withErrors(['email' => 'Zalo này chưa được liên kết với tài khoản hệ thống.']);
        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        LoginLog::create(['user_id'=>$user->id,'email'=>$user->email,'event'=>'login_success','success'=>true,
            'ip_address'=>$request->ip(),'user_agent'=>mb_substr((string)$request->userAgent(),0,500),'created_at'=>now()]);

        return redirect()->route(UserPreferences::landingRoute($user));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'string']]);
        if (! Hash::check($request->string('current_password')->toString(), $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }
        $request->user()->update(['zalo_id' => null, 'zalo_name' => null, 'zalo_linked_at' => null]);
        return back()->with('success', 'Đã ngắt liên kết tài khoản Zalo.');
    }

    private function begin(Request $request, string $mode): RedirectResponse
    {
        if (! $this->enabled()) return $this->failure($mode, 'Đăng nhập Zalo chưa được quản trị viên cấu hình.');
        $state = Str::random(64);
        $verifier = Str::random(96);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $request->session()->put(['zalo_oauth_mode'=>$mode, 'zalo_oauth_state'=>$state, 'zalo_oauth_verifier'=>$verifier]);

        return redirect()->away(config('zalo.authorize_url').'?'.http_build_query([
            'app_id' => config('zalo.app_id'), 'redirect_uri' => $this->redirectUri(),
            'code_challenge' => $challenge, 'state' => $state,
        ]));
    }

    private function enabled(): bool
    {
        return filled(config('zalo.app_id')) && filled(config('zalo.app_secret')) && filled($this->redirectUri());
    }

    private function redirectUri(): string
    {
        return config('zalo.redirect_uri') ?: route('zalo.callback');
    }

    private function failure(?string $mode, string $message): RedirectResponse
    {
        return redirect()->route($mode === 'connect' ? 'personal-settings.edit' : 'login')->with('warning', $message);
    }
}
