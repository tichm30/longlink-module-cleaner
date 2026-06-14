<?php

use Illuminate\Support\Facades\Route;
use Modules\ModuleCleaner\Http\Controllers\ModuleCleanerController;

Route::middleware(['web', 'auth'])
    ->prefix('admin/module-cleaner')
    ->name('admin.module-cleaner.')
    ->group(function (): void {
        Route::get('/', [ModuleCleanerController::class, 'index'])
            ->middleware('can:module_cleaner.view')
            ->name('index');

        Route::get('/registry', [ModuleCleanerController::class, 'registry'])
            ->middleware('can:module_cleaner.view')
            ->name('registry');

        Route::get('/modules/{module}', [ModuleCleanerController::class, 'show'])
            ->middleware('can:module_cleaner.view')
            ->name('modules.show');

        Route::get('/modules/{module}/plan', [ModuleCleanerController::class, 'planPreview'])
            ->middleware('can:module_cleaner.plan')
            ->name('modules.plan.show');

        Route::post('/modules/{module}/plan', [ModuleCleanerController::class, 'plan'])
            ->middleware('can:module_cleaner.plan')
            ->name('modules.plan');

        Route::post('/modules/{module}/backup', [ModuleCleanerController::class, 'backup'])
            ->middleware('can:module_cleaner.purge')
            ->name('modules.backup');

        Route::get('/orphans', [ModuleCleanerController::class, 'orphans'])
            ->middleware('can:module_cleaner.view')
            ->name('orphans');

        Route::get('/backups', [ModuleCleanerController::class, 'backups'])
            ->middleware('can:module_cleaner.view')
            ->name('backups');

        Route::get('/quarantine', [ModuleCleanerController::class, 'quarantine'])
            ->middleware('can:module_cleaner.view')
            ->name('quarantine');

        Route::get('/logs', [ModuleCleanerController::class, 'logs'])
            ->middleware('can:module_cleaner.view')
            ->name('logs');

        Route::get('/settings', [ModuleCleanerController::class, 'settings'])
            ->middleware('can:module_cleaner.view')
            ->name('settings');

        Route::post('/settings', [ModuleCleanerController::class, 'updateSettings'])
            ->middleware('can:module_cleaner.purge')
            ->name('settings.update');
    });
