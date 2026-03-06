<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\System;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | KPI Stats
        |--------------------------------------------------------------------------
        */

        $totalSystems = System::count();

        $logsToday = Log::whereDate('logged_at', $now->toDateString())->count();

        $logsThisWeek = Log::whereBetween('logged_at', [
            $now->copy()->startOfWeek(),
            $now->copy()->endOfWeek(),
        ])->count();

        $logsThisMonth = Log::whereMonth('logged_at', $now->month)
            ->whereYear('logged_at', $now->year)
            ->count();

        $highCriticalThisMonth = Log::whereMonth('logged_at', $now->month)
            ->whereYear('logged_at', $now->year)
            ->whereIn('impact', ['high', 'critical'])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Activity Trend
        |--------------------------------------------------------------------------
        */

        $logsPerDay = Log::selectRaw('DATE(logged_at) as date, COUNT(*) as total')
            ->whereMonth('logged_at', $now->month)
            ->whereYear('logged_at', $now->year)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Distribution Charts
        |--------------------------------------------------------------------------
        */

        $logsPerType = Log::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get();

        $logsPerImpact = Log::selectRaw('impact, COUNT(*) as total')
            ->groupBy('impact')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Logs
        |--------------------------------------------------------------------------
        */

        $recentLogs = Log::with('system')
            ->latest('logged_at')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Critical Events
        |--------------------------------------------------------------------------
        */

        $criticalLogs = Log::with('system')
            ->whereIn('impact', ['high', 'critical'])
            ->latest('logged_at')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | System Health
        |--------------------------------------------------------------------------
        */

        $systemHealth = [
            'active' => System::where('status', 'active')->count(),
            'maintenance' => System::where('status', 'maintenance')->count(),
            'deprecated' => System::where('status', 'deprecated')->count(),
        ];

        $start = Carbon::now()->subDays(29);
        $end = Carbon::now();

        $logs = Log::selectRaw('DATE(logged_at) as date, COUNT(*) as total')
            ->whereBetween('logged_at', [$start, $end])
            ->groupBy('date')
            ->pluck('total', 'date');

        $activityHeatmap = [];

        for ($date = $start; $date <= $end; $date->addDay()) {
            $day = $date->format('Y-m-d');

            $activityHeatmap[] = [
                'date' => $day,
                'total' => $logs[$day] ?? 0
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalSystems' => $totalSystems,
                'logsToday' => $logsToday,
                'logsThisWeek' => $logsThisWeek,
                'logsThisMonth' => $logsThisMonth,
                'highCriticalThisMonth' => $highCriticalThisMonth,
            ],

            'logsPerDay' => $logsPerDay,

            'logsPerType' => $logsPerType,

            'logsPerImpact' => $logsPerImpact,

            'recentLogs' => $recentLogs,

            'criticalLogs' => $criticalLogs,

            'systemHealth' => $systemHealth,

            'activityHeatmap' => $activityHeatmap,
        ]);
    }
}