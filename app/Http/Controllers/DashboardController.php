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

        // Basic counts
        $totalSystems = System::count();

        $logsToday = Log::whereDate('logged_at', $now->toDateString())->count();

        $logsThisWeek = Log::whereBetween('logged_at', [
            $now->startOfWeek(),
            $now->endOfWeek()
        ])->count();

        $logsThisMonth = Log::whereMonth('logged_at', $now->month)
            ->whereYear('logged_at', $now->year)
            ->count();

        $highCriticalThisMonth = Log::whereMonth('logged_at', $now->month)
            ->whereYear('logged_at', $now->year)
            ->whereIn('impact', ['high', 'critical'])
            ->count();

        // Logs per day (current month)
        $logsPerDay = Log::selectRaw('DATE(logged_at) as date, COUNT(*) as total')
            ->whereMonth('logged_at', $now->month)
            ->whereYear('logged_at', $now->year)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Logs per type
        $logsPerType = Log::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
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
        ]);
    }
}