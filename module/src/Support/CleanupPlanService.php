<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use App\Models\User;
use App\Support\Addons\AddonModuleRegistry;

class CleanupPlanService
{
    public function __construct(
        private readonly AddonModuleRegistry $registry,
        private readonly CleanerAuditLogger $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dryRun(AddonModule $module, ?User $actor = null): array
    {
        $plan = $this->registry->purgeModuleResidue($module, [
            'dry_run' => true,
            'surfaces' => ['tables', 'settings', 'permissions', 'navigation', 'storage', 'packages', 'module_files'],
        ], $actor);

        $this->audit->record($module, 'dry_run', 'planned', $plan, $actor);

        return $plan;
    }
}
