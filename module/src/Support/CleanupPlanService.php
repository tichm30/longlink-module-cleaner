<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use App\Models\User;
use App\Support\Addons\AddonModuleRegistry;
use App\Support\Settings\SettingsStore;
use Illuminate\Validation\ValidationException;

class CleanupPlanService
{
    private const PROTECTED_MODULE_KEYS = [
        'licensing',
        'email_signatures',
        'embedded_login_gateway',
        'module_generator',
        'module_cleaner',
    ];

    public function __construct(
        private readonly AddonModuleRegistry $registry,
        private readonly CleanerAuditLogger $audit,
        private readonly ModuleBackupService $backups,
        private readonly CleanerPersistenceService $persistence,
        private readonly DependencyGraphService $dependencies,
        private readonly ModuleQuarantineService $quarantine,
        private readonly OrphanTableDetector $orphans,
        private readonly SettingsStore $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dryRun(AddonModule $module, ?User $actor = null): array
    {
        if ($this->isProtected($module)) {
            throw ValidationException::withMessages([
                'module' => 'Protected module - cleanup planning disabled.',
            ]);
        }

        $dependencyGraph = $this->dependencies->graphFor($module);
        $selfDestruct = $this->persistence->recordSelfDestructCheck($module, $dependencyGraph['self_destruct'] ?? [], $actor);
        $dependencyGraph['self_destruct'] = $selfDestruct;
        $this->persistence->syncDependencyGraph($module, $dependencyGraph);

        if ($this->booleanSetting('module_cleaner.block_dependents', true) && (bool) ($dependencyGraph['has_blocking_dependencies'] ?? false)) {
            throw ValidationException::withMessages([
                'module' => 'Dependent modules still reference this module. Cleanup planning is blocked until the dependency graph is cleared.',
            ]);
        }

        $orphanSnapshot = $this->booleanSetting('module_cleaner.enable_orphan_detection', false)
            ? $this->persistence->syncOrphanCandidates($this->orphans->candidates(), $actor)
            : [];

        try {
            $plan = $this->registry->purgeModuleResidue($module, [
                'dry_run' => true,
                'surfaces' => ['tables', 'settings', 'permissions', 'navigation', 'storage', 'packages', 'module_files'],
            ], $actor);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'module' => 'Cleanup plan could not be prepared: '.collect($exception->errors())->flatten()->implode(' '),
            ]);
        }

        $plan = $this->persistence->recordPlan($module, $plan, $dependencyGraph, $orphanSnapshot, $actor);
        $this->audit->record($module, 'dry_run', 'planned', $plan, $actor);

        return $plan;
    }

    /**
     * @return array<string, mixed>
     */
    public function backup(AddonModule $module, ?User $actor = null, ?int $planId = null): array
    {
        if ($this->isProtected($module)) {
            throw ValidationException::withMessages([
                'module' => 'Protected module - backup and cleanup execution disabled.',
            ]);
        }

        $backup = $this->backups->create($module, $actor);
        $backup = $this->persistence->recordBackup($module, $backup, $planId, $actor);
        $this->audit->record($module, 'backup', 'created', ['backup' => $backup], $actor);

        return $backup;
    }

    /**
     * @param array<string, mixed>|null $plan
     * @param array<string, mixed>|null $backup
     * @return array<string, mixed>
     */
    public function quarantine(AddonModule $module, ?User $actor = null, ?array $plan = null, ?array $backup = null): array
    {
        if ($this->isProtected($module)) {
            throw ValidationException::withMessages([
                'module' => 'Protected module - quarantine disabled.',
            ]);
        }

        $quarantine = $this->quarantine->create($module, $actor, $plan, $backup);
        $this->audit->record($module, 'quarantine', 'copied', ['quarantine' => $quarantine], $actor);

        return $quarantine;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function prepareRestore(int $backupSetId, ?User $actor = null): ?array
    {
        return $this->persistence->prepareRestorePlan($backupSetId, $actor);
    }

    /**
     * @param  array<int, string>|null  $surfaces
     * @return array<string, mixed>
     */
    public function executionPayload(AddonModule $module, ?array $surfaces = null): array
    {
        return [
            'route' => route('admin.settings.addon-modules.modules.purge', $module),
            'surfaces' => $surfaces ?: ['tables', 'settings', 'permissions', 'navigation', 'storage', 'packages', 'module_files'],
            'confirm_module_key' => $module->key,
            'backup_confirmed' => true,
        ];
    }

    private function isProtected(AddonModule $module): bool
    {
        return in_array((string) $module->key, self::PROTECTED_MODULE_KEYS, true);
    }

    private function booleanSetting(string $key, bool $default): bool
    {
        $value = $this->settings->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
