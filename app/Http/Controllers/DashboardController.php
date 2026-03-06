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

        // KPI STATS
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

        // LOGS PER DAY (trend chart)
        $logsPerDay = Log::selectRaw('DATE(logged_at) as date, COUNT(*) as total')
            ->whereMonth('logged_at', $now->month)
            ->whereYear('logged_at', $now->year)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // LOGS PER TYPE
        $logsPerType = Log::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get();

        // LOGS PER IMPACT (NEW)
        $logsPerImpact = Log::selectRaw('impact, COUNT(*) as total')
            ->groupBy('impact')
            ->get();

        // RECENT LOGS (NEW)
        $recentLogs = Log::with('system')
            ->latest('logged_at')
            ->limit(5)
            ->get();

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
        ]);
    }
}