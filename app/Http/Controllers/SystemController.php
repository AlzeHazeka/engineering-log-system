<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\System;
use App\Rules\FeatureBelongsToSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SystemController extends Controller
{
    public function index()
    {
        $systems = System::query()
            ->withCount(['features', 'logs'])
            ->withAvg('features', 'progress')
            ->latest()
            ->get()
            ->map(function (System $system) {
                $stage = $system->stage ?? ($system->released_at ? 'production' : 'development');

                return [
                    'id' => $system->id,
                    'name' => $system->name,
                    'slug' => $system->slug,
                    'kind' => $system->kind,
                    'status' => $system->status,
                    'released_at' => $system->released_at?->toDateString(),
                    'stage' => $stage,
                    'features_count' => $system->features_count ?? 0,
                    'logs_count' => $system->logs_count ?? 0,
                    'features_avg_progress' => $system->features_avg_progress ?? 0,
                ];
            });

        return Inertia::render('Systems/Index', [
            'systems' => $systems,
        ]);
    }

    public function create()
    {
        return Inertia::render('Systems/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kind' => 'nullable|in:app,server',
            'status' => 'required|string',
            'stage' => 'nullable|in:planning,development,production,maintenance',
            'released_at' => 'nullable|date',
            'description' => 'nullable|string',
            'repository_url' => 'nullable|url',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        System::create($validated);

        return redirect()->route('systems.index');
    }

    public function show(System $system)
    {
        $system->loadCount([
            'features',
            'logs',
            'features as features_done_count' => fn ($q) => $q->where('status', 'done'),
            'features as features_in_progress_count' => fn ($q) => $q->where('status', 'in_progress'),
        ])->loadAvg('features', 'progress');

        $stage = $system->stage ?? ($system->released_at ? 'production' : 'development');
        $system->setAttribute('stage', $stage);

        $system->load([
            'features' => fn ($q) => $q
                ->select([
                    'id',
                    'system_id',
                    'title',
                    'status',
                    'progress',
                    'assigned_team',
                    'due_date',
                    'completed_at',
                ])
                ->orderByRaw("
                    CASE status
                        WHEN 'in_progress' THEN 1
                        WHEN 'planned' THEN 2
                        WHEN 'on_hold' THEN 3
                        WHEN 'done' THEN 4
                        ELSE 5
                    END
                ")
                ->orderBy('due_date'),
        ]);

        $globalLogs = Log::query()
            ->where('system_id', $system->id)
            ->whereNull('feature_id')
            ->latest('logged_at')
            ->limit(20)
            ->get();

        $logsByFeature = Log::query()
            ->where('system_id', $system->id)
            ->whereNotNull('feature_id')
            ->latest('logged_at')
            ->limit(200)
            ->get()
            ->groupBy('feature_id')
            ->map(fn ($logs) => $logs->values());

        return Inertia::render('Systems/Show', [
            'system' => $system,
            'globalLogs' => $globalLogs,
            'logsByFeature' => $logsByFeature,
        ]);
    }

    public function edit(System $system)
    {
        return Inertia::render('Systems/Edit', [
            'system' => $system,
            'features' => $system->features()
                ->select(['id', 'title'])
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function update(Request $request, System $system)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kind' => 'nullable|in:app,server',
            'status' => 'required|string',
            'stage' => 'nullable|in:planning,development,production,maintenance',
            'released_at' => 'nullable|date',
            'description' => 'nullable|string',
            'repository_url' => 'nullable|url',
            'log' => 'nullable|array',
            'log.type' => 'required_with:log|in:progress,bug,fix,deployment,maintenance,decision,idea',
            'log.title' => 'required_with:log|string|max:255',
            'log.description' => 'required_with:log|string',
            'log.feature_id' => [
                'nullable',
                'integer',
                'exists:features,id',
                new FeatureBelongsToSystem($system->id),
            ],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $logPayload = $validated['log'] ?? null;
        unset($validated['log']);

        DB::transaction(function () use ($system, $validated, $logPayload) {
            $system->update($validated);

            if ($logPayload) {
                $type = $logPayload['type'];
                $status = null;

                if ($type === 'bug') {
                    $status = 'open';
                } elseif ($type === 'fix') {
                    $status = 'resolved';
                } elseif ($type === 'progress') {
                    $status = 'on_progress';
                }

                Log::create([
                    'system_id' => $system->id,
                    'feature_id' => $logPayload['feature_id'] ?? null,
                    'type' => $logPayload['type'],
                    'impact' => null,
                    'status' => $status,
                    'title' => $logPayload['title'],
                    'description' => $logPayload['description'],
                    'logged_at' => now(),
                ]);
            }
        });

        return redirect()->route('systems.show', $system);
    }

    public function destroy(System $system)
    {
        $system->delete();

        return redirect()->route('systems.index');
    }
}
