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

        Route::post('/modules/{module}/plan', [ModuleCleanerController::class, 'plan'])
            ->middleware('can:module_cleaner.plan')
            ->name('modules.plan');
    });
