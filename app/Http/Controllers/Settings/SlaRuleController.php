<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SlaRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SlaRuleController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Sla', [
            'bug_resolution_days' => SlaRule::bugResolutionDays(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bug_resolution_days' => ['required', 'array'],
            'bug_resolution_days.low' => ['required', 'integer', 'min:0', 'max:3650'],
            'bug_resolution_days.medium' => ['required', 'integer', 'min:0', 'max:3650'],
            'bug_resolution_days.high' => ['required', 'integer', 'min:0', 'max:3650'],
            'bug_resolution_days.critical' => ['required', 'integer', 'min:0', 'max:3650'],
        ]);

        $days = (array) $validated['bug_resolution_days'];

        foreach (['low', 'medium', 'high', 'critical'] as $impact) {
            SlaRule::query()->updateOrCreate(
                [
                    'type' => SlaRule::TYPE_BUG_RESOLUTION,
                    'impact' => $impact,
                ],
                [
                    'days' => (int) $days[$impact],
                ]
            );
        }

        return redirect()->route('settings.sla.index');
    }
}

