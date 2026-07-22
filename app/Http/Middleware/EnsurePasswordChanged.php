<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return redirect()->route('profile.password')->with('warning', 'Vui lòng đổi mật khẩu trước khi tiếp tục.');
        }
        return $next($request);
    }
}
