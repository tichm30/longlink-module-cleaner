<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResidueInventoryService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function modules(): array
    {
        return AddonModule::query()
            ->orderBy('key')
            ->get()
            ->map(fn (AddonModule $module): array => $this->inventoryFor($module))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryFor(AddonModule $module): array
    {
        $snapshot = data_get($module->metadata ?: [], 'ownership_snapshot');
        $hasSnapshot = is_array($snapshot) && ($snapshot['schema'] ?? null) === 'longlink.module_ownership_snapshot.v1';

        if (! $hasSnapshot) {
            return [
                'module' => $module,
                'has_snapshot' => false,
                'tables' => [],
                'settings' => [],
                'permissions' => [],
                'navigation' => [],
                'storage' => [],
                'summary' => [
                    'tables' => 0,
                    'settings' => 0,
                    'permissions' => 0,
                    'storage_paths' => 0,
                    'storage_bytes' => 0,
                ],
            ];
        }

        $tables = collect($this->stringList($snapshot['tables'] ?? []))
            ->map(fn (string $table): array => [
                'name' => $table,
                'exists' => Schema::hasTable($table),
                'rows' => Schema::hasTable($table) ? DB::table($table)->count() : null,
            ])
            ->values()
            ->all();
        $settings = $this->rowPresence('settings', 'key', $this->stringList($snapshot['settings_keys'] ?? []));
        $permissions = $this->rowPresence('permissions', 'key', $this->stringList($snapshot['permission_keys'] ?? []));
        $navigationSources = collect([
            $snapshot['navigation_source'] ?? null,
            $snapshot['menu_source'] ?? null,
            'addon:'.$module->key,
        ])
            ->filter(fn (mixed $source): bool => is_string($source) && $source !== '')
            ->unique()
            ->values()
            ->all();
        $navigation = Schema::hasTable('navigation_items')
            ? collect($navigationSources)
                ->map(fn (string $source): array => [
                    'source' => $source,
                    'rows' => DB::table('navigation_items')->where('source', $source)->count(),
                ])
                ->values()
                ->all()
            : [];
        $storage = collect($this->stringList($snapshot['storage_paths'] ?? []))
            ->map(fn (string $path): array => $this->storageInventory($path))
            ->values()
            ->all();

        return [
            'module' => $module,
            'has_snapshot' => true,
            'tables' => $tables,
            'settings' => $settings,
            'permissions' => $permissions,
            'navigation' => $navigation,
            'storage' => $storage,
            'summary' => [
                'tables' => collect($tables)->where('exists', true)->count(),
                'table_rows' => collect($tables)->sum(fn (array $table): int => (int) ($table['rows'] ?? 0)),
                'settings' => collect($settings)->sum('rows'),
                'permissions' => collect($permissions)->sum('rows'),
                'navigation' => collect($navigation)->sum('rows'),
                'storage_paths' => collect($storage)->where('exists', true)->count(),
                'storage_bytes' => collect($storage)->sum('bytes'),
            ],
        ];
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function stringList(array $values): array
    {
        return collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, array{key: string, rows: int}>
     */
    private function rowPresence(string $table, string $column, array $keys): array
    {
        if (! Schema::hasTable($table) || $keys === []) {
            return [];
        }

        return collect($keys)
            ->map(fn (string $key): array => [
                'key' => $key,
                'rows' => DB::table($table)->where($column, $key)->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{path: string, exists: bool, bytes: int}
     */
    private function storageInventory(string $path): array
    {
        $safePath = trim(str_replace(['..', '\\'], '', $path), '/');
        $absolute = Storage::disk('local')->path($safePath);
        $exists = is_dir($absolute);

        return [
            'path' => $safePath,
            'exists' => $exists,
            'bytes' => $exists ? $this->directoryBytes($absolute) : 0,
        ];
    }

    private function directoryBytes(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        return collect(File::allFiles($path))
            ->sum(fn (mixed $file): int => method_exists($file, 'getSize') ? (int) $file->getSize() : 0);
    }
}
