<?php

namespace Modules\ModuleCleaner\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AddonModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ModuleCleaner\Support\CleanupPlanService;
use Modules\ModuleCleaner\Support\ResidueInventoryService;

class ModuleCleanerController extends Controller
{
    public function index(ResidueInventoryService $inventory): View
    {
        $modules = $inventory->modules();

        return view('module_cleaner::admin.index', $this->viewData('dashboard') + [
            'modules' => $modules,
            'summary' => $this->summary($modules),
            'latestPlan' => session('module_cleaner_plan'),
            'logs' => $this->cleanupLogs(5),
        ]);
    }

    public function registry(ResidueInventoryService $inventory): View
    {
        return view('module_cleaner::admin.registry', $this->viewData('registry') + [
            'modules' => $inventory->modules(),
        ]);
    }

    public function show(AddonModule $module, ResidueInventoryService $inventory): View
    {
        return view('module_cleaner::admin.details', $this->viewData('registry') + [
            'entry' => $inventory->inventoryFor($module),
            'latestPlan' => session('module_cleaner_plan'),
        ]);
    }

    public function planPreview(AddonModule $module, ResidueInventoryService $inventory): View
    {
        return view('module_cleaner::admin.plan', $this->viewData('plan') + [
            'entry' => $inventory->inventoryFor($module),
            'plan' => session('module_cleaner_plan'),
        ]);
    }

    public function plan(Request $request, AddonModule $module, CleanupPlanService $plans): RedirectResponse
    {
        try {
            $plan = $plans->dryRun($module, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.module-cleaner.modules.show', $module)
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.module-cleaner.modules.plan.show', $module)
            ->with('status', __('module_cleaner::messages.messages.plan_ready', ['module' => $module->name]))
            ->with('module_cleaner_plan', $plan);
    }

    public function orphans(): View
    {
        return view('module_cleaner::admin.orphans', $this->viewData('orphans') + [
            'candidates' => [],
            'detectionEnabled' => false,
        ]);
    }

    public function backups(): View
    {
        return view('module_cleaner::admin.backups', $this->viewData('backups') + [
            'backups' => $this->storedFiles(storage_path('app/module_cleaner/backups')),
            'storagePath' => 'storage/app/module_cleaner/backups',
        ]);
    }

    public function quarantine(): View
    {
        return view('module_cleaner::admin.quarantine', $this->viewData('quarantine') + [
            'items' => $this->storedFiles(storage_path('app/module_cleaner/quarantine')),
            'storagePath' => 'storage/app/module_cleaner/quarantine',
        ]);
    }

    public function logs(): View
    {
        return view('module_cleaner::admin.logs', $this->viewData('logs') + [
            'logs' => $this->cleanupLogs(100),
        ]);
    }

    public function settings(): View
    {
        return view('module_cleaner::admin.settings', $this->viewData('settings') + [
            'defaults' => [
                'require_backup' => true,
                'require_typed_confirmation' => true,
                'allow_dry_run' => true,
                'block_dependents' => true,
                'enable_orphan_detection' => false,
                'backup_retention_days' => 30,
                'log_retention_days' => 90,
                'quarantine_retention_days' => 14,
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'log_retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'quarantine_retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        return redirect()
            ->route('admin.module-cleaner.settings')
            ->with('status', __('module_cleaner::messages.settings.saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(string $active): array
    {
        return [
            'activeSection' => $active,
            'sections' => $this->sections(),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, route: string, icon: string}>
     */
    private function sections(): array
    {
        return [
            ['key' => 'dashboard', 'label' => __('module_cleaner::messages.sections.dashboard'), 'route' => route('admin.module-cleaner.index'), 'icon' => 'layout-dashboard'],
            ['key' => 'registry', 'label' => __('module_cleaner::messages.sections.registry'), 'route' => route('admin.module-cleaner.registry'), 'icon' => 'blocks'],
            ['key' => 'plan', 'label' => __('module_cleaner::messages.sections.plan'), 'route' => route('admin.module-cleaner.registry'), 'icon' => 'clipboard'],
            ['key' => 'orphans', 'label' => __('module_cleaner::messages.sections.orphans'), 'route' => route('admin.module-cleaner.orphans'), 'icon' => 'database-zap'],
            ['key' => 'backups', 'label' => __('module_cleaner::messages.sections.backups'), 'route' => route('admin.module-cleaner.backups'), 'icon' => 'database-backup'],
            ['key' => 'quarantine', 'label' => __('module_cleaner::messages.sections.quarantine'), 'route' => route('admin.module-cleaner.quarantine'), 'icon' => 'archive'],
            ['key' => 'logs', 'label' => __('module_cleaner::messages.sections.logs'), 'route' => route('admin.module-cleaner.logs'), 'icon' => 'history'],
            ['key' => 'settings', 'label' => __('module_cleaner::messages.sections.settings'), 'route' => route('admin.module-cleaner.settings'), 'icon' => 'settings'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $modules
     * @return array<string, int>
     */
    private function summary(array $modules): array
    {
        return [
            'modules' => count($modules),
            'snapshots' => collect($modules)->where('has_snapshot', true)->count(),
            'protected' => collect($modules)->where('protected', true)->count(),
            'tables' => collect($modules)->sum(fn (array $entry): int => (int) data_get($entry, 'summary.tables', 0)),
            'packages' => collect($modules)->sum(fn (array $entry): int => (int) data_get($entry, 'summary.packages', 0)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cleanupLogs(int $limit): array
    {
        if (! Schema::hasTable('module_cleaner_cleanup_logs')) {
            return [];
        }

        return DB::table('module_cleaner_cleanup_logs')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'module_key' => (string) $row->module_key,
                'module_version' => (string) ($row->module_version ?? ''),
                'action' => (string) $row->action,
                'status' => (string) $row->status,
                'size_freed_bytes' => (int) ($row->size_freed_bytes ?? 0),
                'created_at' => (string) ($row->created_at ?? ''),
            ])
            ->all();
    }

    /**
     * @return array<int, array{name: string, path: string, size: int, modified_at: string}>
     */
    private function storedFiles(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        return collect(File::files($path))
            ->map(fn (\SplFileInfo $file): array => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->values()
            ->all();
    }
}
