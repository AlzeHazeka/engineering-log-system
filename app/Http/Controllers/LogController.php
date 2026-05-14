<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogRequest;
use App\Models\Feature;
use App\Models\Log;
use App\Models\System;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogController extends Controller
{
   public function index(Request $request)
    {
        $systemId = $request->integer('system_id');

        $query = Log::query()
            ->with([
                'system:id,name',
                'feature:id,system_id,title',
                'references:id,type,impact,logged_at,title',
            ])
            ->when($systemId, fn ($q) => $q->where('system_id', $systemId));

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
        ]);
    }

    public function create()
    {
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

        return redirect()->route('logs.index', [
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
        ]);
    }

    public function edit(Log $log)
    {
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

        return redirect()->route('logs.index', [
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

        return redirect()->route('logs.index', [
            'system_id' => $systemId,
        ]);
    }
}
