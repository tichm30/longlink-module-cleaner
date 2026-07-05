<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanerPersistenceService
{
    /**
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $dependencyGraph
     * @param array<int, array<string, mixed>> $orphanSnapshot
     * @return array<string, mixed>
     */
    public function recordPlan(AddonModule $module, array $plan, array $dependencyGraph, array $orphanSnapshot, ?User $actor = null): array
    {
        if (! Schema::hasTable('module_cleaner_cleanup_plans')) {
            return $plan;
        }

        $record = [
            'addon_module_id' => $module->id,
            'module_key' => (string) $module->key,
            'module_version' => (string) $module->version,
            'status' => 'prepared',
            'surfaces' => json_encode($plan['surfaces'] ?? [], JSON_THROW_ON_ERROR),
            'plan_payload' => json_encode($plan, JSON_THROW_ON_ERROR),
            'dependency_graph' => json_encode($dependencyGraph, JSON_THROW_ON_ERROR),
            'orphan_snapshot' => json_encode($orphanSnapshot, JSON_THROW_ON_ERROR),
            'backup_required' => true,
            'host_purge_route' => route('admin.settings.addon-modules.modules.purge', $module),
            'actor_user_id' => $actor?->id,
            'prepared_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('module_cleaner_cleanup_plans')->insertGetId($record);

        return $plan + [
            'cleaner_plan_id' => $id,
            'dependency_graph' => $dependencyGraph,
            'orphan_snapshot_count' => count($orphanSnapshot),
            'self_destruct_status' => data_get($dependencyGraph, 'self_destruct.status'),
        ];
    }

    /**
     * @param array<string, mixed> $backup
     * @return array<string, mixed>
     */
    public function recordBackup(AddonModule $module, array $backup, ?int $planId = null, ?User $actor = null): array
    {
        if (! Schema::hasTable('module_cleaner_backup_sets')) {
            return $backup;
        }

        $backupRef = $this->backupRef($module, $backup);
        $id = DB::table('module_cleaner_backup_sets')->insertGetId([
            'addon_module_id' => $module->id,
            'cleanup_plan_id' => $planId,
            'module_key' => (string) $module->key,
            'module_version' => (string) $module->version,
            'backup_ref' => $backupRef,
            'relative_path' => (string) ($backup['relative_path'] ?? ''),
            'status' => 'created',
            'manifest_payload' => json_encode($backup, JSON_THROW_ON_ERROR),
            'files' => json_encode($backup['files'] ?? [], JSON_THROW_ON_ERROR),
            'actor_user_id' => $actor?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $backup + [
            'backup_set_id' => $id,
            'backup_ref' => $backupRef,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    public function syncOrphanCandidates(array $candidates, ?User $actor = null): array
    {
        if (! Schema::hasTable('module_cleaner_orphan_candidates')) {
            return $candidates;
        }

        foreach ($candidates as $candidate) {
            $tableName = (string) ($candidate['name'] ?? '');
            if ($tableName === '') {
                continue;
            }

            DB::table('module_cleaner_orphan_candidates')->updateOrInsert(
                ['table_name' => $tableName],
                [
                    'confidence' => (string) ($candidate['confidence'] ?? 'medium'),
                    'status' => (string) ($candidate['disposition'] ?? 'pending_review'),
                    'row_count' => is_numeric($candidate['rows'] ?? null) ? (int) $candidate['rows'] : null,
                    'evidence' => json_encode($candidate['evidence'] ?? [], JSON_THROW_ON_ERROR),
                    'detected_at' => now(),
                    'actor_user_id' => $actor?->id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        return $candidates;
    }

    /**
     * @param array<string, mixed> $graph
     */
    public function syncDependencyGraph(AddonModule $module, array $graph): void
    {
        if (! Schema::hasTable('module_cleaner_dependency_edges')) {
            return;
        }

        foreach (($graph['edges'] ?? []) as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $moduleKey = (string) ($edge['module_key'] ?? $module->key);
            $dependsOn = (string) ($edge['depends_on_module_key'] ?? '');
            $type = (string) ($edge['dependency_type'] ?? 'runtime');

            if ($moduleKey === '' || $dependsOn === '') {
                continue;
            }

            DB::table('module_cleaner_dependency_edges')->updateOrInsert(
                [
                    'module_key' => $moduleKey,
                    'depends_on_module_key' => $dependsOn,
                    'dependency_type' => $type,
                ],
                [
                    'source' => (string) ($edge['source'] ?? 'manifest'),
                    'is_blocking' => (bool) ($edge['is_blocking'] ?? true),
                    'metadata' => json_encode($edge['metadata'] ?? [], JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    /**
     * @param array<string, mixed> $check
     * @return array<string, mixed>
     */
    public function recordSelfDestructCheck(AddonModule $module, array $check, ?User $actor = null): array
    {
        if (! Schema::hasTable('module_cleaner_self_destruct_checks')) {
            return $check;
        }

        $id = DB::table('module_cleaner_self_destruct_checks')->insertGetId([
            'module_key' => (string) $module->key,
            'status' => (string) ($check['status'] ?? 'blocked'),
            'reason' => (string) ($check['reason'] ?? ''),
            'checks' => json_encode($check, JSON_THROW_ON_ERROR),
            'actor_user_id' => $actor?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $check + ['self_destruct_check_id' => $id];
    }

    /**
     * @param array<string, mixed> $item
     */
    public function recordQuarantineItem(AddonModule $module, array $item, ?int $planId = null, ?int $backupSetId = null, ?User $actor = null): void
    {
        if (! Schema::hasTable('module_cleaner_quarantine_items')) {
            return;
        }

        DB::table('module_cleaner_quarantine_items')->insert([
            'addon_module_id' => $module->id,
            'cleanup_plan_id' => $planId,
            'backup_set_id' => $backupSetId,
            'module_key' => (string) $module->key,
            'item_type' => (string) ($item['item_type'] ?? 'file'),
            'source_path' => $item['source_path'] ?? null,
            'quarantine_path' => $item['quarantine_path'] ?? null,
            'status' => (string) ($item['status'] ?? 'copied'),
            'bytes' => (int) ($item['bytes'] ?? 0),
            'checksum' => $item['checksum'] ?? null,
            'metadata' => json_encode($item['metadata'] ?? [], JSON_THROW_ON_ERROR),
            'actor_user_id' => $actor?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function backupRows(): array
    {
        if (! Schema::hasTable('module_cleaner_backup_sets')) {
            return [];
        }

        return DB::table('module_cleaner_backup_sets')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function quarantineRows(): array
    {
        if (! Schema::hasTable('module_cleaner_quarantine_items')) {
            return [];
        }

        return DB::table('module_cleaner_quarantine_items')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function prepareRestorePlan(int $backupSetId, ?User $actor = null): ?array
    {
        if (! Schema::hasTable('module_cleaner_backup_sets')) {
            return null;
        }

        $row = DB::table('module_cleaner_backup_sets')->where('id', $backupSetId)->first();
        if (! $row) {
            return null;
        }

        $payload = [
            'backup_set_id' => $backupSetId,
            'backup_ref' => (string) $row->backup_ref,
            'relative_path' => (string) $row->relative_path,
            'restore_mode' => 'operator_review_required',
            'steps' => [
                'reinstall_or_reupload_module_package',
                'inspect_backup_json_exports',
                'restore_module_owned_rows_intentionally',
                'restore_settings_permissions_navigation_after_review',
            ],
            'prepared_at' => now()->toIso8601String(),
            'actor_user_id' => $actor?->id,
        ];

        DB::table('module_cleaner_backup_sets')
            ->where('id', $backupSetId)
            ->update([
                'status' => 'restore_prepared',
                'restore_payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'restore_prepared_at' => now(),
                'updated_at' => now(),
            ]);

        return $payload;
    }

    /**
     * @param array<string, mixed> $backup
     */
    private function backupRef(AddonModule $module, array $backup): string
    {
        $relative = (string) ($backup['relative_path'] ?? now()->format('YmdHis'));

        return substr((string) $module->key.'-'.sha1($relative), 0, 190);
    }
}
