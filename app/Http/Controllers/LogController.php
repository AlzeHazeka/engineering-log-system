<?php

namespace App\Http\Controllers;
use App\Models\Log;
use App\Models\System;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class LogController extends Controller
{
   public function index(Request $request)
    {
        $date = $request->input('date');
        $systemId = $request->input('system_id');
        $type = $request->input('type');
        $impact = $request->input('impact');

        if (!$date) {
            $date = Carbon::today()->toDateString();
        }

        $query = Log::with('system')
            ->whereDate('logged_at', $date);

        if ($systemId) {
            $query->where('system_id', $systemId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($impact) {
            $query->where('impact', $impact);
        }

        return Inertia::render('Logs/Index', [
            'logs' => $query
                ->latest('logged_at')
                ->paginate(10)
                ->withQueryString(),

            'systems' => System::select('id', 'name')->orderBy('name')->get(),

            'filters' => [
                'date' => $date,
                'system_id' => $systemId,
                'type' => $type,
                'impact' => $impact,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Logs/Create', [
            'systems' => System::select('id', 'name')->orderBy('name')->get(),
            'types' => [
                'change',
                'error',
                'fix',
                'maintenance',
                'decision',
                'deployment',
                'idea',
            ],
            'impacts' => [
                'low',
                'medium',
                'high',
                'critical',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_id' => 'required|exists:systems,id',
            'type' => 'required|string',
            'impact' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'logged_at' => 'required|date',
        ]);

        $log = Log::create($validated);

        return redirect()->route('logs.index', [
            'date' => Carbon::parse($validated['logged_at'])->toDateString()
        ]);
    }

    public function show(Log $log)
    {
        return Inertia::render('Logs/Show', [
            'log' => $log->load('system'),
        ]);
    }

    public function edit(Log $log)
    {
        return Inertia::render('Logs/Edit', [
            'log' => $log,
            'systems' => System::select('id', 'name')
                ->orderBy('name')
                ->get(),

            'types' => [
                'change',
                'error',
                'fix',
                'maintenance',
                'decision',
                'deployment',
                'idea',
            ],

            'impacts' => [
                'low',
                'medium',
                'high',
                'critical',
            ],
        ]);
    }

    public function update(Request $request, Log $log)
    {
        $validated = $request->validate([
            'system_id' => 'required|exists:systems,id',
            'type' => 'required|string',
            'impact' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'logged_at' => 'required|date',
        ]);

        $log->update($validated);

        return redirect()->route('logs.index', [
            'date' => Carbon::parse($validated['logged_at'])->toDateString()
        ]);
    }

    public function destroy(Log $log)
    {
        $log->delete();

        return redirect()->route('logs.index');
    }
}
