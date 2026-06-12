<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanerAuditLogger
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function record(AddonModule $module, string $action, string $status, array $details, ?User $actor = null): void
    {
        if (! Schema::hasTable('module_cleaner_cleanup_logs')) {
            return;
        }

        DB::table('module_cleaner_cleanup_logs')->insert([
            'addon_module_id' => $module->id,
            'module_key' => $module->key,
            'module_version' => $module->version,
            'action' => $action,
            'status' => $status,
            'tables_planned' => json_encode($this->targetsForSurface($details, 'tables'), JSON_THROW_ON_ERROR),
            'files_planned' => json_encode($this->targetsForSurface($details, 'module_files'), JSON_THROW_ON_ERROR),
            'settings_planned' => json_encode($this->targetsForSurface($details, 'settings'), JSON_THROW_ON_ERROR),
            'permissions_planned' => json_encode($this->targetsForSurface($details, 'permissions'), JSON_THROW_ON_ERROR),
            'size_freed_bytes' => (int) data_get($details, 'summary.storage_bytes', 0),
            'backup_ref' => data_get($details, 'backup.ref'),
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'actor_user_id' => $actor?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, mixed>
     */
    private function targetsForSurface(array $details, string $surface): array
    {
        return collect($details['items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && ($item['surface'] ?? null) === $surface)
            ->pluck('target')
            ->values()
            ->all();
    }
}
