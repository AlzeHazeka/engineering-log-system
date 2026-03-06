<?php

namespace App\Http\Controllers;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SystemController extends Controller
{
    public function index()
    {
        return Inertia::render('Systems/Index', [
            'systems' => System::withCount('logs')
                ->latest()
                ->get()
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
            'status' => 'required|string',
            'description' => 'nullable|string',
            'repository_url' => 'nullable|url',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        System::create($validated);

        return redirect()->route('systems.index');
    }

    public function show(System $system)
    {
        return Inertia::render('Systems/Show', [
            'system' => $system
                ->loadCount('logs')
                ->load([
                    'logs' => fn ($q) => $q->latest()->take(5)
                ]),
        ]);
    }

    public function edit(System $system)
    {
        return Inertia::render('Systems/Edit', [
            'system' => $system,
        ]);
    }

    public function update(Request $request, System $system)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string',
            'description' => 'nullable|string',
            'repository_url' => 'nullable|url',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $system->update($validated);

        return redirect()->route('systems.show', $system);
    }

    public function destroy(System $system)
    {
        $system->delete();

        return redirect()->route('systems.index');
    }
}