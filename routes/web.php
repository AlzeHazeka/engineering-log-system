<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Settings\SlaRuleController;
use App\Http\Controllers\Settings\MasterController;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Systems CRUD
    Route::resource('systems', SystemController::class);

    // Features CRUD (nested under systems)
    Route::scopeBindings()->group(function () {
        Route::resource('systems.features', FeatureController::class)
            ->except(['show']);
    });

    // Logs CRUD
    Route::resource('logs', LogController::class);

    // Users CRUD (admin-only)
    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('can:manage-users');

    // Settings (admin-only)
    Route::prefix('settings')
        ->name('settings.')
        ->middleware('can:manage-users')
        ->group(function () {
            Route::get('/', [MasterController::class, 'index'])->name('master');
            Route::get('/variables/sla', [SlaRuleController::class, 'index'])->name('sla.index');
            Route::put('/variables/sla', [SlaRuleController::class, 'update'])->name('sla.update');
        });
    
    

    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/reports/export', [ReportController::class, 'export'])
        ->name('reports.export');
});

require __DIR__.'/auth.php';
