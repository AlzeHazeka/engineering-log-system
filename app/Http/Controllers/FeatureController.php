<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeatureRequest;
use App\Models\Feature;
use App\Models\System;
use Inertia\Inertia;

class FeatureController extends Controller
{
    public function index(System $system)
    {
        return Inertia::render('Features/Index', [
            'system' => $system->only(['id', 'name', 'slug', 'status']),
            'features' => $system->features()
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function create(System $system)
    {
        return Inertia::render('Features/Create', [
            'system' => $system->only(['id', 'name', 'slug', 'status']),
            'feature' => null,
        ]);
    }

    public function store(FeatureRequest $request, System $system)
    {
        $data = $request->validated();
        $data['system_id'] = $system->id;
        $data['progress'] = $data['progress'] ?? 0;

        if (($data['status'] ?? null) === 'done') {
            $data['progress'] = 100;
            $data['completed_at'] = $data['completed_at'] ?? now();
        }

        $feature = Feature::create($data);

        return redirect()->route('systems.show', $system->slug);
    }

    public function edit(System $system, Feature $feature)
    {
        return Inertia::render('Features/Edit', [
            'system' => $system->only(['id', 'name', 'slug', 'status']),
            'feature' => $feature,
        ]);
    }

    public function update(FeatureRequest $request, System $system, Feature $feature)
    {
        $data = $request->validated();
        $data['progress'] = $data['progress'] ?? 0;

        if (($data['status'] ?? null) === 'done') {
            $data['progress'] = 100;
            $data['completed_at'] = $data['completed_at'] ?? now();
        } else {
            $data['completed_at'] = null;
        }

        $feature->update($data);

        return redirect()->route('systems.show', $system->slug);
    }

    public function destroy(System $system, Feature $feature)
    {
        $feature->delete();

        return redirect()->route('systems.show', $system->slug);
    }
}
