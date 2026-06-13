<?php

namespace Modules\ModuleCleaner\Http\Controllers;

use App\Models\AddonModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\ModuleCleaner\Support\CleanupPlanService;
use Modules\ModuleCleaner\Support\ResidueInventoryService;

class ModuleCleanerController
{
    public function index(ResidueInventoryService $inventory): View
    {
        return view('module_cleaner::admin.index', [
            'modules' => $inventory->modules(),
            'latestPlan' => session('module_cleaner_plan'),
        ]);
    }

    public function plan(Request $request, AddonModule $module, CleanupPlanService $plans): RedirectResponse
    {
        try {
            $plan = $plans->dryRun($module, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.module-cleaner.index')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.module-cleaner.index')
            ->with('status', __('module_cleaner::messages.messages.plan_ready', ['module' => $module->name]))
            ->with('module_cleaner_plan', $plan);
    }
}
