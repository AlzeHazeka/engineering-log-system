<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Gate;
use App\Models\SlaRule;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'can' => [
                'manage_users' => $request->user()
                    ? Gate::allows('manage-users')
                    : false,
            ],
            'workstation' => [
                'primary_admin_email' => config('workstation.primary_admin_email'),
            ],
            'sla' => [
                'bug_resolution_days' => SlaRule::bugResolutionDays(),
            ],
        ];
    }
}
