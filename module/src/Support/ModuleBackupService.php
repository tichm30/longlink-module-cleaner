<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ModuleBackupService
{
    private const ROW_EXPORT_LIMIT = 10000;

    public function __construct(
        private readonly ResidueInventoryService $inventory,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function create(AddonModule $module, ?User $actor = null): array
    {
        $timestamp = now()->format('Ymd_His');
        $moduleKey = $this->safeSegment((string) $module->key);
        $absolutePath = storage_path("app/module_cleaner/backups/{$moduleKey}/{$timestamp}");
        $relativePath = "module_cleaner/backups/{$moduleKey}/{$timestamp}";
        $snapshot = data_get($module->metadata ?: [], 'ownership_snapshot', []);
        $inventory = $this->inventory->inventoryFor($module);
        $manifest = $this->moduleManifest($module);

        File::ensureDirectoryExists($absolutePath.'/tables');
        File::ensureDirectoryExists($absolutePath.'/permissions');
        File::ensureDirectoryExists($absolutePath.'/settings');
        File::ensureDirectoryExists($absolutePath.'/navigation');
        File::ensureDirectoryExists($absolutePath.'/packages');

        $written = [];
        $written[] = $this->writeJson($absolutePath.'/manifest.json', [
            'schema' => 'longlink.module_cleaner.backup_manifest.v1',
            'module_key' => $module->key,
            'module_name' => $module->name,
            'module_version' => $module->version,
            'created_at' => now()->toIso8601String(),
            'actor_user_id' => $actor?->id,
            'backup_confirmed' => true,
        ]);
        $written[] = $this->writeJson($absolutePath.'/ownership_snapshot.json', $snapshot);
        $written[] = $this->writeJson($absolutePath.'/inventory.json', $inventory);
        $written[] = $this->writeJson($absolutePath.'/module_manifest.json', $manifest);

        foreach ($this->tableNames($snapshot) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $written[] = $this->writeJson($absolutePath.'/tables/'.$table.'.json', [
                'table' => $table,
                'row_limit' => self::ROW_EXPORT_LIMIT,
                'rows' => DB::table($table)->limit(self::ROW_EXPORT_LIMIT)->get()->map(fn (object $row): array => (array) $row)->all(),
            ]);
        }

        $permissionKeys = $this->stringList($snapshot['permission_keys'] ?? []);
        $permissionRows = $this->rowsWhereIn('permissions', 'key', $permissionKeys);
        $written[] = $this->writeJson($absolutePath.'/permissions/permission_rows.json', $permissionRows);
        $written[] = $this->writeJson($absolutePath.'/permissions/role_permission_rows.json', $this->rolePermissionRows($permissionRows));

        $settingKeys = $this->stringList($snapshot['settings_keys'] ?? []);
        $written[] = $this->writeJson($absolutePath.'/settings/setting_rows.json', $this->rowsWhereIn('settings', 'key', $settingKeys));

        $navigationSources = collect([
            $snapshot['navigation_source'] ?? null,
            $snapshot['menu_source'] ?? null,
            'addon:'.$module->key,
        ])->filter(fn (mixed $source): bool => is_string($source) && $source !== '')->unique()->values()->all();
        $written[] = $this->writeJson($absolutePath.'/navigation/navigation_rows.json', $this->rowsWhereIn('navigation_items', 'source', $navigationSources));

        if (Schema::hasTable('addon_module_packages')) {
            $hasModuleKey = Schema::hasColumn('addon_module_packages', 'module_key');
            $written[] = $this->writeJson($absolutePath.'/packages/package_rows.json', DB::table('addon_module_packages')
                ->where(function ($query) use ($module, $hasModuleKey): void {
                    $query->where('addon_module_id', $module->id);

                    if ($hasModuleKey) {
                        $query->orWhere('module_key', $module->key);
                    }
                })
                ->limit(self::ROW_EXPORT_LIMIT)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all());
        }

        $written[] = $this->writeText($absolutePath.'/restore_notes.md', $this->restoreNotes($module, $relativePath));

        return [
            'module_key' => $module->key,
            'module_version' => $module->version,
            'path' => $absolutePath,
            'relative_path' => $relativePath,
            'created_at' => now()->toIso8601String(),
            'backup_confirmed' => true,
            'files' => array_values(array_filter($written)),
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, string>
     */
    private function tableNames(array $snapshot): array
    {
        return collect($snapshot['tables'] ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->map(fn (string $table): string => trim(str_replace(['..', '/', '\\'], '', $table)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $values
     * @return array<int, string>
     */
    private function stringList(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, array<string, mixed>>
     */
    private function rowsWhereIn(string $table, string $column, array $values): array
    {
        if (! Schema::hasTable($table) || $values === [] || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->whereIn($column, $values)
            ->limit(self::ROW_EXPORT_LIMIT)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $permissionRows
     * @return array<int, array<string, mixed>>
     */
    private function rolePermissionRows(array $permissionRows): array
    {
        if (! Schema::hasTable('role_permission') || $permissionRows === []) {
            return [];
        }

        $ids = collect($permissionRows)
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($ids === [] || ! Schema::hasColumn('role_permission', 'permission_id')) {
            return [];
        }

        return DB::table('role_permission')
            ->whereIn('permission_id', $ids)
            ->limit(self::ROW_EXPORT_LIMIT)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function moduleManifest(AddonModule $module): array
    {
        $rawPath = is_string($module->manifest_path) ? trim($module->manifest_path) : '';
        $path = $rawPath !== '' && str_starts_with($rawPath, DIRECTORY_SEPARATOR)
            ? $rawPath
            : ($rawPath !== '' ? base_path(trim($rawPath, '/')) : '');

        if ($path === '' || ! is_file($path)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        return is_array($manifest) ? $manifest : [];
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $payload
     */
    private function writeJson(string $path, array $payload): string
    {
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $path;
    }

    private function writeText(string $path, string $contents): string
    {
        File::put($path, $contents);

        return $path;
    }

    private function restoreNotes(AddonModule $module, string $relativePath): string
    {
        return <<<MD
# Module Cleaner Backup

Module: {$module->key}
Version: {$module->version}
Storage: storage/app/{$relativePath}

This backup captures module-owned residue before host purge execution. Restore is staged: inspect the JSON exports, reinstall or re-upload the module package first, then restore table rows/settings/navigation/permissions intentionally. Do not import rows blindly into production.
MD;
    }

    private function safeSegment(string $value): string
    {
        $segment = strtolower(trim(preg_replace('/[^a-zA-Z0-9_\\-]+/', '_', $value) ?: '', '_'));

        return $segment !== '' ? $segment : 'module';
    }
}
