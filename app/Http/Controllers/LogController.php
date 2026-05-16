<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogRequest;
use App\Models\Feature;
use App\Models\Log;
use App\Models\System;
use App\Support\ReturnUrl;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
    private function redirectBackToReturn(Request $request, array $fallbackRouteParams = [])
    {
        $return = ReturnUrl::sanitize($request->query('return'));
        if ($return) {
            return redirect()->to($return);
        }

        return redirect()->route('logs.index', $fallbackRouteParams);
    }

   public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', 'in:progress,bug,fix,deployment,maintenance,decision,idea'],
            'status' => ['nullable', 'in:open,resolved,ignored,on_progress,done'],
            'impact' => ['nullable', 'in:low,medium,high,critical'],
            'system_id' => ['nullable', 'integer', 'exists:systems,id'],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'date_range' => ['nullable', 'in:all,today,this_week,this_month,last_30_days,custom'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $systemId = isset($validated['system_id']) ? (int) $validated['system_id'] : null;
        $featureId = isset($validated['feature_id']) ? (int) $validated['feature_id'] : null;
        $search = trim((string) ($validated['search'] ?? ''));
        $type = $validated['type'] ?? null;
        $status = $validated['status'] ?? null;
        $impact = $validated['impact'] ?? null;

        $dateRange = $validated['date_range'] ?? 'all';
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;

        $query = Log::query()
            ->with([
                'system:id,name',
                'feature:id,system_id,title',
                'references:id,type,impact,logged_at,title',
            ])
            ->when($systemId, fn ($q) => $q->where('system_id', $systemId))
            ->when($featureId, fn ($q) => $q->where('feature_id', $featureId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($impact, fn ($q) => $q->where('impact', $impact))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhereHas('system', fn ($s) => $s->where('name', 'like', $like))
                        ->orWhereHas('feature', fn ($f) => $f->where('title', 'like', $like));
                });
            });

        // Date range filter (based on `logged_at`)
        if ($dateRange && $dateRange !== 'all') {
            $startAt = null;
            $endAt = null;

            if ($dateRange === 'today') {
                $startAt = now()->startOfDay();
                $endAt = now()->endOfDay();
            } elseif ($dateRange === 'this_week') {
                $startAt = now()->startOfWeek()->startOfDay();
                $endAt = now()->endOfWeek()->endOfDay();
            } elseif ($dateRange === 'this_month') {
                $startAt = now()->startOfMonth()->startOfDay();
                $endAt = now()->endOfMonth()->endOfDay();
            } elseif ($dateRange === 'last_30_days') {
                $startAt = now()->subDays(30)->startOfDay();
                $endAt = now()->endOfDay();
            } elseif ($dateRange === 'custom' && $fromDate && $toDate) {
                $startAt = \Carbon\Carbon::parse($fromDate)->startOfDay();
                $endAt = \Carbon\Carbon::parse($toDate)->endOfDay();
            }

            if ($startAt && $endAt) {
                $query->whereBetween('logged_at', [$startAt, $endAt]);
            }
        }

        $systems = System::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $features = Feature::query()
            ->select(['id', 'system_id', 'title'])
            ->with(['system:id,name'])
            ->orderBy('title')
            ->limit(1000)
            ->get()
            ->map(fn (Feature $f) => [
                'id' => $f->id,
                'system_id' => $f->system_id,
                'title' => $f->title,
                'system_name' => $f->system?->name,
            ]);

        return Inertia::render('Logs/Index', [
            'logs' => $query
                ->latest('logged_at')
                ->paginate(20)
                ->through(function (Log $log) {
                    $data = $log->toArray();

                    $data['duration_days'] = $log->getDurationInDays();
                    $data['sla_on_time'] = $log->isResolvedOnTime();
                    $data['sla_days'] = $log->getSlaDays();
                    $data['sla_duration_days'] = $log->getSlaDurationInDays();

                    return $data;
                })
                ->withQueryString(),
            'system' => $systemId
                ? System::query()->select(['id', 'name'])->find($systemId)
                : null,
            'systems' => $systems,
            'features' => $features,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'impact' => $impact,
                'system_id' => $systemId ? (string) $systemId : '',
                'feature_id' => $featureId ? (string) $featureId : '',
                'date_range' => $dateRange,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }

    public function create()
    {
        $returnUrl = ReturnUrl::sanitize(request()->query('return'));

        $systems = System::query()
            ->select(['id', 'name'])
            ->with(['features:id,system_id,title'])
            ->orderBy('name')
            ->get();

        $referenceLogs = Log::query()
            ->select(['id', 'system_id', 'feature_id', 'type', 'status', 'title', 'logged_at'])
            ->latest('logged_at')
            ->limit(300)
            ->get();

        return Inertia::render('Logs/Create', [
            'systems' => $systems,
            'referenceLogs' => $referenceLogs,
            'selectedReferenceIds' => [],
            'log' => null,
            'returnUrl' => $returnUrl,
        ]);
    }

    public function store(LogRequest $request)
    {
        $validated = $request->validated();
        $referenceIds = $validated['reference_ids'] ?? [];
        unset($validated['reference_ids']);

        // Default status by type (if empty)
        if (!$validated['status'] && $validated['type'] === 'bug') {
            $validated['status'] = 'open';
        }
        if (!$validated['status'] && $validated['type'] === 'fix') {
            $validated['status'] = 'resolved';
        }
        if (!$validated['status'] && $validated['type'] === 'progress') {
            $validated['status'] = 'on_progress';
        }

        if ($validated['type'] === 'fix' && $validated['status'] === 'resolved') {
            // Important: SLA is computed based on the user-input log datetime,
            // not the record creation datetime.
            $validated['resolved_at'] = $validated['logged_at'] ?? now();
        } else {
            $validated['resolved_at'] = null;
        }

        $log = Log::create($validated);
        $log->references()->sync($referenceIds);

        // Smart behavior: resolved fix auto-resolves referenced open bugs.
        if ($log->type === 'fix' && $log->status === 'resolved' && count($referenceIds) > 0) {
            Log::query()
                ->whereIn('id', $referenceIds)
                ->where('type', 'bug')
                ->where('status', 'open')
                ->update(['status' => 'resolved']);
        }

        if ($log->feature_id) {
            $log->feature?->recalculateProgressFromLogs();
        }

        return $this->redirectBackToReturn($request, [
            'system_id' => $validated['system_id'],
        ]);
    }

    public function show(Log $log)
    {
        $log->load([
            'system:id,name',
            'feature:id,system_id,title',
            'references:id,title,type,impact,logged_at',
        ]);

        return Inertia::render('Logs/Show', [
            'log' => $log->toArray() + [
                'duration_days' => $log->getDurationInDays(),
                'sla_on_time' => $log->isResolvedOnTime(),
                'sla_days' => $log->getSlaDays(),
                'sla_duration_days' => $log->getSlaDurationInDays(),
            ],
            'returnUrl' => ReturnUrl::sanitize(request()->query('return')),
        ]);
    }

    public function edit(Log $log)
    {
        $returnUrl = ReturnUrl::sanitize(request()->query('return'));

        $systems = System::query()
            ->select(['id', 'name'])
            ->with(['features:id,system_id,title'])
            ->orderBy('name')
            ->get();

        $referenceLogs = Log::query()
            ->select(['id', 'system_id', 'feature_id', 'type', 'status', 'title', 'logged_at'])
            ->where('id', '!=', $log->id)
            ->latest('logged_at')
            ->limit(300)
            ->get();

        return Inertia::render('Logs/Edit', [
            'log' => $log,
            'systems' => $systems,
            'referenceLogs' => $referenceLogs,
            'selectedReferenceIds' => $log->references()->pluck('logs.id')->values(),
            'returnUrl' => $returnUrl,
        ]);
    }

    public function update(LogRequest $request, Log $log)
    {
        $originalFeatureId = $log->feature_id;

        $validated = $request->validated();
        $referenceIds = $validated['reference_ids'] ?? [];
        unset($validated['reference_ids']);

        // Default status by type (if empty)
        if (!$validated['status'] && $validated['type'] === 'bug') {
            $validated['status'] = 'open';
        }
        if (!$validated['status'] && $validated['type'] === 'fix') {
            $validated['status'] = 'resolved';
        }
        if (!$validated['status'] && $validated['type'] === 'progress') {
            $validated['status'] = 'on_progress';
        }

        if ($validated['type'] === 'fix' && $validated['status'] === 'resolved') {
            $validated['resolved_at'] = $validated['logged_at'] ?? ($log->resolved_at ?? now());
        } else {
            $validated['resolved_at'] = null;
        }

        $log->update($validated);
        $log->references()->sync($referenceIds);

        // Smart behavior: resolved fix auto-resolves referenced open bugs.
        if ($log->type === 'fix' && $log->status === 'resolved' && count($referenceIds) > 0) {
            Log::query()
                ->whereIn('id', $referenceIds)
                ->where('type', 'bug')
                ->where('status', 'open')
                ->update(['status' => 'resolved']);
        }

        if ($originalFeatureId && $originalFeatureId !== $log->feature_id) {
            Feature::query()->find($originalFeatureId)?->recalculateProgressFromLogs();
        }

        if ($log->feature_id) {
            $log->feature?->recalculateProgressFromLogs();
        }

        return $this->redirectBackToReturn($request, [
            'system_id' => $validated['system_id'],
        ]);
    }

    public function destroy(Log $log)
    {
        $systemId = $log->system_id;
        $featureId = $log->feature_id;

        $log->delete();

        if ($featureId) {
            Feature::query()->find($featureId)?->recalculateProgressFromLogs();
        }

        return $this->redirectBackToReturn(request(), [
            'system_id' => $systemId,
        ]);
    }

    public function markDone(Request $request, Log $log)
    {
        // Quick action: only progress logs can be marked as done.
        if ($log->type !== 'progress') {
            abort(400, 'Only progress logs can be marked as done.');
        }

        if ($log->status === 'done') {
            return $this->redirectBackToReturn($request);
        }

        // Keep it simple: on_progress -> done
        $log->update(['status' => 'done']);

        if ($log->feature_id) {
            $log->feature?->recalculateProgressFromLogs();
        }

        return $this->redirectBackToReturn($request);
    }
}
