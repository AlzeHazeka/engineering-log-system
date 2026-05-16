<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\Log;
use App\Models\System;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'system_id' => ['nullable', 'integer', 'exists:systems,id'],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:progress,bug,fix'],
            'view_mode' => ['nullable', 'in:detail,summary,visual'],
            'report_view' => ['nullable', 'boolean'],
            'progress_status_period' => ['nullable', 'boolean'],
            'completion_trend_period' => ['nullable', 'boolean'],
            'backlog_trend_period' => ['nullable', 'boolean'],
        ]);

        $startAt = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->startOfWeek()->startOfDay();

        $endAt = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfWeek()->endOfDay();

        $systemId = $validated['system_id'] ?? null;
        $featureId = $validated['feature_id'] ?? null;
        $types = $validated['types'] ?? ['progress', 'bug', 'fix'];
        $viewMode = $validated['view_mode'] ?? 'detail';
        $reportView = (bool) ($validated['report_view'] ?? false);
        $progressStatusPeriod = (bool) ($validated['progress_status_period'] ?? false);
        $completionTrendPeriod = (bool) ($validated['completion_trend_period'] ?? false);
        $backlogTrendPeriod = (bool) ($validated['backlog_trend_period'] ?? false);

        // Filter dropdown data
        $systems = System::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $featureFilterQuery = Feature::query()
            ->select(['id', 'system_id', 'title'])
            ->with(['system:id,name'])
            ->orderBy('title');

        if ($systemId) {
            $featureFilterQuery->where('system_id', $systemId);
        }

        $filterFeatures = $featureFilterQuery
            ->limit(300)
            ->get()
            ->map(fn (Feature $f) => [
                'id' => $f->id,
                'system_id' => $f->system_id,
                'title' => $f->title,
                'system_name' => $f->system?->name,
            ]);

        /*
        |--------------------------------------------------------------------------
        | LOG-BASED Reporting (Source of Truth)
        |--------------------------------------------------------------------------
        |
        | All KPIs, table data, highlights, and charts must be derived from logs.
        | System/Feature are only grouping/context.
        |
        */

        $logsQuery = Log::query()
            ->with([
                'system:id,name',
                'feature:id,system_id,title',
                'references:id,type,impact,logged_at,title',
            ])
            ->whereBetween('logged_at', [$startAt, $endAt])
            ->when($systemId, fn ($q) => $q->where('system_id', $systemId))
            ->when($featureId, fn ($q) => $q->where('feature_id', $featureId))
            ->latest('logged_at')
            ;

        // If user unchecks all types, show no logs (predictable filter behavior).
        if (count($types) === 0) {
            $logs = collect();
        } else {
            $logs = $logsQuery->whereIn('type', $types)->get();
        }

        // KPI derived from logs (after filters)
        $totalLogs = $logs->count();
        $progressCount = $logs->where('type', 'progress')->count();
        $bugCount = $logs->where('type', 'bug')->count();
        $fixCount = $logs->where('type', 'fix')->count();

        $progressOnProgressCount = $logs
            ->where('type', 'progress')
            ->where('status', 'on_progress')
            ->count();

        $progressDoneCount = $logs
            ->where('type', 'progress')
            ->where('status', 'done')
            ->count();

        $resolvedBugCount = $logs
            ->where('type', 'bug')
            ->where('status', 'resolved')
            ->count();

        $resolvedFix = $logs
            ->where('type', 'fix')
            ->where('status', 'resolved')
            ->filter(fn (Log $log) => (bool) $log->resolved_at)
            ->values();

        $onTimeResolvedCount = $resolvedFix
            ->filter(fn (Log $log) => $log->isResolvedOnTime() === true)
            ->count();

        $lateResolvedCount = max(0, $resolvedFix->count() - $onTimeResolvedCount);

        // Open bug count (within the filtered range)
        $openBugCount = $logs
            ->where('type', 'bug')
            ->where('status', 'open')
            ->count();

        $uniqueSystemCount = $logs->pluck('system_id')->filter()->unique()->count();

        // Activity feed = the latest logs (already filtered)
        $activity = $logs->take(80)->values()->map(fn (Log $log) => [
            'id' => $log->id,
            'system_id' => $log->system_id,
            'system_name' => $log->system?->name,
            'feature_id' => $log->feature_id,
            'feature_title' => $log->feature?->title,
            'type' => $log->type,
            'impact' => $log->impact,
            'status' => $log->status,
            'title' => $log->title,
            'logged_at' => optional($log->logged_at)->toISOString(),
        ]);

        // Highlights for meeting mode (log-based)
        $lateFixes = $resolvedFix
            ->filter(fn (Log $log) => $log->isResolvedOnTime() === false)
            ->sortByDesc(fn (Log $log) => $log->resolved_at)
            ->take(5)
            ->values()
            ->map(fn (Log $log) => [
                'id' => $log->id,
                'system_id' => $log->system_id,
                'feature_id' => $log->feature_id,
                'title' => $log->title,
                'resolved_at' => optional($log->resolved_at)->toISOString(),
                'sla_days' => $log->getSlaDays(),
                'duration_days' => $log->getSlaDurationInDays(),
            ]);

        $recentFix = $resolvedFix
            ->sortByDesc(fn (Log $log) => $log->resolved_at)
            ->take(5)
            ->values()
            ->map(fn (Log $log) => [
                'id' => $log->id,
                'system_id' => $log->system_id,
                'feature_id' => $log->feature_id,
                'title' => $log->title,
                'resolved_at' => optional($log->resolved_at)->toISOString(),
                'sla_on_time' => $log->isResolvedOnTime(),
            ]);

        // Open bugs section: show latest open bugs (not limited by date range),
        // but still respects system/feature filters.
        $openBugs = Log::query()
            ->with(['system:id,name', 'feature:id,system_id,title'])
            ->select(['id', 'system_id', 'feature_id', 'type', 'status', 'title', 'logged_at', 'impact'])
            ->where('type', 'bug')
            ->where('status', 'open')
            ->when($systemId, fn ($q) => $q->where('system_id', $systemId))
            ->when($featureId, fn ($q) => $q->where('feature_id', $featureId))
            ->latest('logged_at')
            ->limit(5)
            ->get()
            ->map(fn (Log $log) => [
                'id' => $log->id,
                'system_id' => $log->system_id,
                'system_name' => $log->system?->name,
                'feature_id' => $log->feature_id,
                'feature_title' => $log->feature?->title,
                'title' => $log->title,
                'logged_at' => optional($log->logged_at)->toISOString(),
                'impact' => $log->impact,
            ]);

        // Chart data scopes (per chart):
        // - *_period=false: overall snapshot (ignores date range where meaningful)
        // - *_period=true: follow selected period

        $includeProgress = in_array('progress', $types, true);
        $includeBug = in_array('bug', $types, true);
        $includeFix = in_array('fix', $types, true);

        $overallLogs = Log::query()
            ->with([
                'system:id,name',
                'feature:id,system_id,title',
                'references:id,type,impact,logged_at,title',
            ])
            ->when($systemId, fn ($q) => $q->where('system_id', $systemId))
            ->when($featureId, fn ($q) => $q->where('feature_id', $featureId))
            ->when(count($types) === 0, fn ($q) => $q->whereRaw('1=0'))
            ->when(count($types) > 0, fn ($q) => $q->whereIn('type', $types))
            ->get();

        // Date labels always follow selected period (keeps charts readable)
        $dates = [];
        $cursor = $startAt->copy()->startOfDay();
        while ($cursor->lte($endAt)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $progressStatusBase = $progressStatusPeriod ? $logs : $overallLogs;
        $progressStatus = [
            'on_progress' => $progressStatusBase->where('type', 'progress')->where('status', 'on_progress')->count(),
            'done' => $progressStatusBase->where('type', 'progress')->where('status', 'done')->count(),
        ];

        // Impact distribution stays period-based by design (managerial for selected timeframe)
        $bugImpact = $logs
            ->where('type', 'bug')
            ->groupBy(fn (Log $log) => $log->impact ?: 'unknown')
            ->map(fn ($items) => $items->count());

        $impactDistribution = collect(['low', 'medium', 'high', 'critical', 'unknown'])
            ->mapWithKeys(fn ($k) => [$k => (int) ($bugImpact[$k] ?? 0)])
            ->all();

        // Completion Trend: split series (progress done vs fix resolved) per day.
        // NOTE: This is *not* cumulative. Each day shows the number of completions
        // on that date. So a future period with no activity stays at zero.
        // Toggle:
        // - period=true  -> use filtered logs (selected period)
        // - period=false -> use overall logs, but still only count events within the selected period range
        $completionBase = $completionTrendPeriod ? $logs : $overallLogs;
        $completionInPeriod = $completionBase
            ->filter(fn (Log $l) => $l->logged_at && $l->logged_at->between($startAt, $endAt));

        $progressDoneByDay = $completionInPeriod
            ->where('type', 'progress')
            ->where('status', 'done')
            ->groupBy(fn (Log $log) => optional($log->logged_at)->format('Y-m-d'))
            ->map(fn ($items) => $items->count());

        $fixResolvedByDay = $completionInPeriod
            ->where('type', 'fix')
            ->where('status', 'resolved')
            ->groupBy(fn (Log $log) => optional($log->logged_at)->format('Y-m-d'))
            ->map(fn ($items) => $items->count());

        $completionProgressSeries = collect($dates)
            ->map(fn ($d) => $includeProgress ? (int) ($progressDoneByDay[$d] ?? 0) : 0)
            ->values();

        $completionFixSeries = collect($dates)
            ->map(fn ($d) => $includeFix ? (int) ($fixResolvedByDay[$d] ?? 0) : 0)
            ->values();

        // Backlog Trend: split series (open bugs vs open progress), cumulative
        // Default (period=false): includes initial open backlog before startAt.
        // period=true: starts from 0 (only changes during the selected period).
        $backlogBase = $backlogTrendPeriod ? $logs : $overallLogs;
        $backlogInPeriod = $backlogBase
            ->filter(fn (Log $l) => $l->logged_at && $l->logged_at->between($startAt, $endAt));

        $bugsCreatedByDay = $backlogInPeriod
            ->where('type', 'bug')
            ->groupBy(fn (Log $log) => optional($log->logged_at)->format('Y-m-d'))
            ->map(fn ($items) => $items->count());

        $progressStartedByDay = $backlogInPeriod
            ->where('type', 'progress')
            ->where('status', 'on_progress')
            ->groupBy(fn (Log $log) => optional($log->logged_at)->format('Y-m-d'))
            ->map(fn ($items) => $items->count());

        $initialBugBacklog = 0;
        $initialProgressBacklog = 0;

        // For backlog initial state, we use DB counts (fast) and switch scope by analyticsPeriod.
        if (!$backlogTrendPeriod && $includeBug) {
            $initialBugBacklog = Log::query()
                ->where('type', 'bug')
                ->where('status', 'open')
                ->where('logged_at', '<', $startAt)
                ->when($systemId, fn ($q) => $q->where('system_id', $systemId))
                ->when($featureId, fn ($q) => $q->where('feature_id', $featureId))
                ->count();
        }

        if (!$backlogTrendPeriod && $includeProgress) {
            $initialProgressBacklog = Log::query()
                ->where('type', 'progress')
                ->where('status', 'on_progress')
                ->where('logged_at', '<', $startAt)
                ->when($systemId, fn ($q) => $q->where('system_id', $systemId))
                ->when($featureId, fn ($q) => $q->where('feature_id', $featureId))
                ->count();
        }

        $bugBacklogTrend = [];
        $progressBacklogTrend = [];
        $bugBacklog = $initialBugBacklog;
        $progressBacklog = $initialProgressBacklog;

        foreach ($dates as $d) {
            $createdBug = $includeBug ? (int) ($bugsCreatedByDay[$d] ?? 0) : 0;
            $createdProgress = $includeProgress ? (int) ($progressStartedByDay[$d] ?? 0) : 0;
            $completedFix = $includeFix ? (int) ($fixResolvedByDay[$d] ?? 0) : 0;
            $completedProgress = $includeProgress ? (int) ($progressDoneByDay[$d] ?? 0) : 0;

            $bugBacklog += $createdBug;
            $bugBacklog -= $completedFix;
            $bugBacklog = max(0, $bugBacklog);

            $progressBacklog += $createdProgress;
            $progressBacklog -= $completedProgress;
            $progressBacklog = max(0, $progressBacklog);

            $bugBacklogTrend[] = $bugBacklog;
            $progressBacklogTrend[] = $progressBacklog;
        }

        // Optional groupings (log-based)
        $groupBySystem = $logs
            ->groupBy(fn (Log $log) => $log->system?->name ?? 'Unknown')
            ->map(fn ($items) => $items->count());

        $groupByFeature = $logs
            ->groupBy(fn (Log $log) => $log->feature?->title ?? 'Global')
            ->map(fn ($items) => $items->count());

        // Log table (detail mode)
        $logTable = $logs
            ->take(200)
            ->values()
            ->map(function (Log $log) {
                $refs = $log->references
                    ?->where('type', 'bug')
                    ?->values()
                    ?->map(fn (Log $r) => [
                        'id' => $r->id,
                        'title' => $r->title,
                    ]) ?? collect();

                return [
                    'id' => $log->id,
                    'logged_at' => optional($log->logged_at)->toISOString(),
                    'system_name' => $log->system?->name,
                    'feature_title' => $log->feature?->title,
                    'type' => $log->type,
                    'status' => $log->status,
                    'impact' => $log->impact,
                    'title' => $log->title,
                    'references' => $refs,
                    'reference_count' => $refs->count(),
                    'sla_on_time' => $log->type === 'fix' && $log->status === 'resolved'
                        ? $log->isResolvedOnTime()
                        : null,
                    'sla_days' => $log->getSlaDays(),
                    'sla_duration_days' => $log->getSlaDurationInDays(),
                ];
            });

        return Inertia::render('Reports/Index', [
            'filters' => [
                'start_date' => $startAt->toDateString(),
                'end_date' => $endAt->toDateString(),
                'system_id' => $systemId,
                'feature_id' => $featureId,
                'types' => $types,
                'view_mode' => $viewMode,
                'report_view' => $reportView,
                'progress_status_period' => $progressStatusPeriod,
                'completion_trend_period' => $completionTrendPeriod,
                'backlog_trend_period' => $backlogTrendPeriod,
            ],
            'systems' => $systems,
            'features' => $filterFeatures,
            'kpis' => [
                'total_logs' => $totalLogs,
                'progress_logs' => $progressCount,
                'progress_on_progress' => $progressOnProgressCount,
                'progress_done' => $progressDoneCount,
                'bug_logs' => $bugCount,
                'fix_logs' => $fixCount,
                'resolved_bug_count' => $resolvedBugCount,
                'completion_rate' => ($progressCount + $fixCount) > 0
                    ? (int) round((($progressDoneCount + $resolvedFix->count()) / ($progressCount + $fixCount)) * 100)
                    : 0,
                'bug_sla_on_time_rate' => $resolvedFix->count()
                    ? (int) round(($onTimeResolvedCount / $resolvedFix->count()) * 100)
                    : 0,
                'open_bug_count' => $openBugCount,
            ],
            'filter_feedback' => [
                'total_logs' => $totalLogs,
                'system_count' => $uniqueSystemCount,
            ],
            'bug_summary' => [
                'total_bug' => $bugCount,
                'resolved_fix' => $resolvedFix->count(),
                'on_time' => $onTimeResolvedCount,
                'late' => $lateResolvedCount,
            ],
            'highlights' => [
                'late_fixes' => $lateFixes,
                'recent_fix' => $recentFix,
                'open_bugs' => $openBugs,
            ],
            'activity' => $activity,
            'log_table' => $logTable,
            'grouping' => [
                'by_system' => $groupBySystem,
                'by_feature' => $groupByFeature,
            ],
            'charts' => [
                'bug_vs_fix' => [
                    'bug' => $bugCount,
                    'fix' => $fixCount,
                ],
                'bug_sla_on_time' => [
                    'on_time' => $onTimeResolvedCount,
                    'late' => $lateResolvedCount,
                ],
                'progress_status' => $progressStatus,
                'impact_distribution' => $impactDistribution,
                'completion_trend' => [
                    'labels' => $dates,
                    'progress_done' => $completionProgressSeries,
                    'fix_resolved' => $completionFixSeries,
                ],
                'open_backlog_trend' => [
                    'labels' => $dates,
                    'open_bug' => $bugBacklogTrend,
                    'open_progress' => $progressBacklogTrend,
                ],
            ],
        ]);
    }

    public function export(Request $request)
    {
        // Backward-compatible export route.
        // Supports: Full Report (PDF) generation (HTML -> PDF via DomPDF).
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'system_id' => ['nullable', 'integer', 'exists:systems,id'],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:progress,bug,fix'],
            'format' => ['nullable', 'in:pdf'],
        ]);

        if (($validated['format'] ?? null) !== 'pdf') {
            return redirect()->route('reports.index');
        }

        $startAt = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->startOfWeek()->startOfDay();

        $endAt = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfWeek()->endOfDay();

        $systemId = $validated['system_id'] ?? null;
        $featureId = $validated['feature_id'] ?? null;
        $types = $validated['types'] ?? ['progress', 'bug', 'fix'];

        $system = $systemId
            ? System::query()->select(['id', 'name'])->find($systemId)
            : null;
        $feature = $featureId
            ? Feature::query()->select(['id', 'system_id', 'title'])->find($featureId)
            : null;

        $logsQuery = Log::query()
            ->with([
                'system:id,name',
                'feature:id,system_id,title',
                'references:id,type,impact,logged_at,title',
            ])
            ->whereBetween('logged_at', [$startAt, $endAt])
            ->when($systemId, fn ($q) => $q->where('system_id', $systemId))
            ->when($featureId, fn ($q) => $q->where('feature_id', $featureId))
            ->latest('logged_at')
            ->limit(300);

        $logs = count($types) === 0
            ? collect()
            : $logsQuery->whereIn('type', $types)->get();

        $totalLogs = $logs->count();
        $progressCount = $logs->where('type', 'progress')->count();
        $bugCount = $logs->where('type', 'bug')->count();
        $fixCount = $logs->where('type', 'fix')->count();
        $resolvedBugCount = $logs->where('type', 'bug')->where('status', 'resolved')->count();

        $progressOnProgressCount = $logs
            ->where('type', 'progress')
            ->where('status', 'on_progress')
            ->count();
        $progressDoneCount = $logs
            ->where('type', 'progress')
            ->where('status', 'done')
            ->count();

        $resolvedFix = $logs
            ->where('type', 'fix')
            ->where('status', 'resolved')
            ->filter(fn (Log $log) => (bool) $log->resolved_at)
            ->values();

        $onTimeResolvedCount = $resolvedFix
            ->filter(fn (Log $log) => $log->isResolvedOnTime() === true)
            ->count();

        $lateResolvedCount = max(0, $resolvedFix->count() - $onTimeResolvedCount);

        $slaOnTimeRate = $resolvedFix->count()
            ? (int) round(($onTimeResolvedCount / $resolvedFix->count()) * 100)
            : 0;

        $pdf = Pdf::loadView('reports.pdf', [
            'meta' => [
                'start_date' => $startAt->toDateString(),
                'end_date' => $endAt->toDateString(),
                'system' => $system?->name,
                'feature' => $feature?->title,
                'generated_at' => now(),
            ],
            'kpis' => [
                'total_logs' => $totalLogs,
                'progress_logs' => $progressCount,
                'bug_logs' => $bugCount,
                'fix_logs' => $fixCount,
                'resolved_bug_count' => $resolvedBugCount,
                'progress_on_progress' => $progressOnProgressCount,
                'progress_done' => $progressDoneCount,
                'sla_on_time_rate' => $slaOnTimeRate,
                'sla_on_time' => $onTimeResolvedCount,
                'sla_late' => $lateResolvedCount,
            ],
            'logs' => $logs,
            'bug_summary' => [
                'total_bug' => $bugCount,
                'resolved_bug' => $resolvedBugCount,
                'resolved_fix' => $resolvedFix->count(),
                'on_time' => $onTimeResolvedCount,
                'late' => $lateResolvedCount,
            ],
        ])
            ->setPaper('a4', 'portrait');

        $filename = sprintf(
            'report-full_%s_%s.pdf',
            $startAt->toDateString(),
            $endAt->toDateString()
        );

        return $pdf->download($filename);
    }
}
