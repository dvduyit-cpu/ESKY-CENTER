<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\Personnel;
use App\Models\User;
use App\Support\KpiCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly KpiCalculator $calculator) {}

    public function index(Request $request): View
    {
        $year = $request->integer('year', now()->year);
        $user = $request->user()->load('personnel', 'role');
        $canViewAll = $user->isAdmin() || $user->allowed('kpi_dashboard_all');
        $filters = ['year' => $year, 'period_type' => 'year', 'period_value' => 0];
        if (! $canViewAll) {
            $filters['personnel_id'] = $user->personnel_id ?: -1;
        }
        $report = $this->calculator->report($filters);

        $monthly = collect();
        for ($month = 1; $month <= 12; $month++) {
            $monthFilters = ['year' => $year, 'period_type' => 'month', 'period_value' => $month];
            if (! $canViewAll) {
                $monthFilters['personnel_id'] = $user->personnel_id ?: -1;
            }
            $monthReport = $this->calculator->report($monthFilters);
            $monthly->put($month, $monthReport['totals']);
        }

        return view('dashboard', [
            'year' => $year,
            'canViewAll' => $canViewAll,
            'user' => $user,
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'monthly' => $monthly,
            'personnelCount' => Personnel::where('type', '!=', 'collaborator')->where('active', true)->count(),
            'collaboratorCount' => Personnel::where('type', 'collaborator')->where('active', true)->count(),
            'userCount' => User::where('active', true)->count(),
            'recentImports' => ImportBatch::with('user')->latest()->limit(5)->get(),
        ]);
    }
}
