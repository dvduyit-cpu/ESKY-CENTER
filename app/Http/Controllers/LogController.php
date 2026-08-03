<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $year = max(2020, min(2100, $request->integer('year', now()->year)));
        $month = $request->filled('month') ? max(1, min(12, $request->integer('month'))) : null;
        $perPage = \App\Support\Pagination::perPage();
        $activeTab = $request->input('tab', $request->filled('login_page') ? 'login' : 'activity');
        $canFilterUsers=$request->user()->isAdmin();
        $selectedUser=$canFilterUsers&&$request->integer('user_id')>0
            ? User::withTrashed()->find($request->integer('user_id'))
            : null;

        $activity = ActivityLog::with('user')->latest('created_at');
        $login = LoginLog::with('user')->latest('created_at');

        if ($selectedUser) {
            $activity->where('user_id',$selectedUser->id);
            $login->where(fn($query)=>$query->where('user_id',$selectedUser->id)
                ->orWhere('email',$selectedUser->email));
        }

        foreach ([$activity, $login] as $query) {
            $query->whereYear('created_at', $year);
            if ($month) $query->whereMonth('created_at', $month);
        }

        if ($request->filled('q')) {
            $keyword = trim($request->string('q')->toString());
            $activity->where(function ($query) use ($keyword) {
                $query->where('description', 'like', "%{$keyword}%")
                    ->orWhere('module', 'like', "%{$keyword}%")
                    ->orWhere('action', 'like', "%{$keyword}%")
                    ->orWhere('ip_address', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
            });
            $login->where(function ($query) use ($keyword) {
                $query->where('email', 'like', "%{$keyword}%")
                    ->orWhere('ip_address', 'like', "%{$keyword}%")
                    ->orWhere('user_agent', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$keyword}%"));
            });
        }
        if ($request->filled('module')) $activity->where('module', $request->string('module')->toString());
        if ($request->filled('action')) $activity->where('action', $request->string('action')->toString());
        if ($request->filled('event')) $login->where('event', $request->string('event')->toString());

        $activityCount = (clone $activity)->count();
        $loginCount = (clone $login)->count();
        $loginSuccess = (clone $login)->where('success', true)->count();

        return view('logs.index', [
            'activities' => $activity->paginate(\App\Support\Pagination::perPage(), ['*'], 'activity_page')->withQueryString(),
            'logins' => $login->paginate(\App\Support\Pagination::perPage(), ['*'], 'login_page')->withQueryString(),
            'modules' => ActivityLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->whereNotNull('action')->distinct()->orderBy('action')->pluck('action'),
            'year' => $year,
            'month' => $month,
            'perPage' => $perPage,
            'activeTab' => in_array($activeTab, ['activity', 'login'], true) ? $activeTab : 'activity',
            'activityCount' => $activityCount,
            'loginCount' => $loginCount,
            'loginSuccess' => $loginSuccess,
            'loginFailed' => $loginCount - $loginSuccess,
            'canFilterUsers'=>$canFilterUsers,
            'selectedUser'=>$selectedUser,
            'logUsers'=>$canFilterUsers
                ? User::withTrashed()->orderBy('name')->orderBy('email')->get(['id','name','email','deleted_at'])
                : collect(),
        ]);
    }
}
