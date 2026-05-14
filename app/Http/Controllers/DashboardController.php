<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Feature;
use App\Models\System;
use Inertia\Inertia;
use Throwable;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $systemsCount = System::count();

            $logsToday = Log::whereDate('logged_at', today())->count();

            $logsThisWeek = Log::whereBetween('logged_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count();

            $logsThisMonth = Log::whereMonth('logged_at', now()->month)
                ->whereYear('logged_at', now()->year)
                ->count();

            $highCritical = Log::whereIn('impact', ['high', 'critical'])->count();

            $systemsHealth = [
                'active' => System::where('status', 'active')->count(),
                'maintenance' => System::where('status', 'maintenance')->count(),
                'deprecated' => System::where('status', 'deprecated')->count(),
            ];

            $recentLogs = Log::with('system:id,name')
                ->latest('logged_at')
                ->take(10)
                ->get();

            $criticalEvents = Log::with('system:id,name')
                ->where('impact', 'critical')
                ->latest('logged_at')
                ->take(5)
                ->get();

            $completedFeatures = Feature::query()
                ->whereNotNull('completed_at')
                ->whereNotNull('due_date')
                ->get();

            $onTimeFeatures = $completedFeatures->filter(fn (Feature $f) => $f->isCompletedOnTime() === true);

            $featureOnTimeRate = $completedFeatures->count()
                ? (int) round(($onTimeFeatures->count() / $completedFeatures->count()) * 100)
                : 0;

            $resolvedFixLogs = Log::query()
                ->where('type', 'fix')
                ->where('status', 'resolved')
                ->whereNotNull('resolved_at')
                ->with(['references:id,type,impact,logged_at'])
                ->get();

            $onTimeResolved = $resolvedFixLogs->filter(fn (Log $log) => $log->isResolvedOnTime() === true);

            $bugOnTimeRate = $resolvedFixLogs->count()
                ? (int) round(($onTimeResolved->count() / $resolvedFixLogs->count()) * 100)
                : 0;
        } catch (Throwable) {
            $systemsCount = 0;
            $logsToday = 0;
            $logsThisWeek = 0;
            $logsThisMonth = 0;
            $highCritical = 0;
            $systemsHealth = [
                'active' => 0,
                'maintenance' => 0,
                'deprecated' => 0,
            ];
            $recentLogs = collect();
            $criticalEvents = collect();
            $featureOnTimeRate = 0;
            $bugOnTimeRate = 0;
        }

        return Inertia::render('Dashboard', [
            'systemsCount' => $systemsCount,
            'logsToday' => $logsToday,
            'logsThisWeek' => $logsThisWeek,
            'logsThisMonth' => $logsThisMonth,
            'highCritical' => $highCritical,
            'systemsHealth' => $systemsHealth,
            'recentLogs' => $recentLogs,
            'criticalEvents' => $criticalEvents,
            'featureOnTimeRate' => $featureOnTimeRate,
            'bugOnTimeRate' => $bugOnTimeRate,
        ]);
    }
}
