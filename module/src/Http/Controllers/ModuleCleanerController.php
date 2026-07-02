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
use App\Support\Settings\ModuleSettingsPersister;
use App\Support\Settings\SettingsStore;
use Modules\ModuleCleaner\Settings\ModuleCleanerSettingsCatalog;
use Modules\ModuleCleaner\Support\CleanupPlanService;
use Modules\ModuleCleaner\Support\OrphanTableDetector;
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

    public function planPreview(AddonModule $module, ResidueInventoryService $inventory, CleanupPlanService $plans): View
    {
        $plan = session('module_cleaner_plan');

        return view('module_cleaner::admin.plan', $this->viewData('plan') + [
            'entry' => $inventory->inventoryFor($module),
            'plan' => $plan,
            'backup' => session('module_cleaner_backup'),
            'execution' => is_array($plan) ? $plans->executionPayload($module, $plan['surfaces'] ?? null) : null,
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

    public function backup(Request $request, AddonModule $module, CleanupPlanService $plans): RedirectResponse
    {
        try {
            $backup = $plans->backup($module, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.module-cleaner.modules.plan.show', $module)
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.module-cleaner.modules.plan.show', $module)
            ->with('status', __('module_cleaner::messages.messages.backup_ready', ['module' => $module->name]))
            ->with('module_cleaner_backup', $backup);
    }

    public function orphans(OrphanTableDetector $detector, SettingsStore $settings): View
    {
        $enabled = (bool) $settings->get('module_cleaner.enable_orphan_detection');

        return view('module_cleaner::admin.orphans', $this->viewData('orphans') + [
            'candidates' => $enabled ? $detector->candidates() : [],
            'detectionEnabled' => $enabled,
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

    public function settings(SettingsStore $settings, ModuleCleanerSettingsCatalog $catalog): View
    {
        return view('module_cleaner::admin.settings', $this->viewData('settings') + [
            'defaults' => $this->settingsValues($settings, $catalog),
        ]);
    }

    public function updateSettings(Request $request, ModuleSettingsPersister $settings): RedirectResponse
    {
        $request->validate([
            'backup_retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'log_retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'quarantine_retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'slack_webhook_url' => ['nullable', 'url', 'max:255'],
        ]);

        $settings->persistPayload([
            'module_cleaner.require_backup' => $request->boolean('require_backup'),
            'module_cleaner.require_typed_confirmation' => $request->boolean('require_typed_confirmation'),
            'module_cleaner.allow_dry_run' => $request->boolean('allow_dry_run'),
            'module_cleaner.block_dependents' => $request->boolean('block_dependents'),
            'module_cleaner.enable_orphan_detection' => $request->boolean('enable_orphan_detection'),
            'module_cleaner.backup_retention_days' => (string) $request->integer('backup_retention_days', 30),
            'module_cleaner.log_retention_days' => (string) $request->integer('log_retention_days', 90),
            'module_cleaner.quarantine_retention_days' => (string) $request->integer('quarantine_retention_days', 14),
            'module_cleaner.email_on_cleanup' => $request->boolean('email_on_cleanup'),
            'module_cleaner.slack_webhook_url' => (string) $request->input('slack_webhook_url', ''),
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

        $files = collect(File::files($path))
            ->map(fn (\SplFileInfo $file): array => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ]);

        $directories = collect(File::directories($path))
            ->map(fn (string $directory): array => [
                'name' => basename($directory),
                'path' => $directory,
                'size' => collect(File::allFiles($directory))->sum(fn (mixed $file): int => method_exists($file, 'getSize') ? (int) $file->getSize() : 0),
                'modified_at' => date('Y-m-d H:i:s', filemtime($directory) ?: time()),
            ]);

        return $directories
            ->merge($files)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsValues(SettingsStore $settings, ModuleCleanerSettingsCatalog $catalog): array
    {
        $values = [];

        foreach (array_keys($catalog->defaults()) as $key) {
            $shortKey = str_replace('module_cleaner.', '', $key);
            $values[$shortKey] = $settings->get($key);
        }

        return $values;
    }
}
