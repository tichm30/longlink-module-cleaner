<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use App\Models\User;
use App\Support\Addons\AddonModuleRegistry;
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

        $this->audit->record($module, 'dry_run', 'planned', $plan, $actor);

        return $plan;
    }

    /**
     * @return array<string, mixed>
     */
    public function backup(AddonModule $module, ?User $actor = null): array
    {
        if ($this->isProtected($module)) {
            throw ValidationException::withMessages([
                'module' => 'Protected module - backup and cleanup execution disabled.',
            ]);
        }

        $backup = $this->backups->create($module, $actor);
        $this->audit->record($module, 'backup', 'created', ['backup' => $backup], $actor);

        return $backup;
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
}
