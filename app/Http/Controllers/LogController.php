<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $activity = ActivityLog::with('user')->latest('created_at');
        $login = LoginLog::with('user')->latest('created_at');
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $activity->where('description','like',"%{$q}%");
            $login->where(fn ($b) => $b->where('email','like',"%{$q}%")->orWhere('ip_address','like',"%{$q}%"));
        }
        if ($request->filled('module')) $activity->where('module', $request->string('module'));
        if ($request->filled('event')) $login->where('event', $request->string('event'));
        return view('logs.index', [
            'activities' => $activity->paginate(20, ['*'], 'activity_page')->withQueryString(),
            'logins' => $login->paginate(20, ['*'], 'login_page')->withQueryString(),
        ]);
    }
}
