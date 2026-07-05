<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ModuleQuarantineService
{
    public function __construct(
        private readonly ResidueInventoryService $inventory,
        private readonly CleanerPersistenceService $persistence,
    ) {}

    /**
     * @param array<string, mixed>|null $plan
     * @param array<string, mixed>|null $backup
     * @return array<string, mixed>
     */
    public function create(AddonModule $module, ?User $actor = null, ?array $plan = null, ?array $backup = null): array
    {
        $timestamp = now()->format('Ymd_His');
        $moduleKey = $this->safeSegment((string) $module->key);
        $relativePath = "module_cleaner/quarantine/{$moduleKey}/{$timestamp}";
        $absolutePath = storage_path('app/'.$relativePath);
        $inventory = $this->inventory->inventoryFor($module);
        $items = [];

        File::ensureDirectoryExists($absolutePath);

        foreach (($inventory['module_files'] ?? []) as $entry) {
            $items = array_merge($items, $this->copyPath($module, (string) ($entry['path'] ?? ''), base_path((string) ($entry['path'] ?? '')), $absolutePath.'/module_files', 'module_files', $plan, $backup, $actor));
        }

        foreach (($inventory['storage'] ?? []) as $entry) {
            $path = (string) ($entry['path'] ?? '');
            $items = array_merge($items, $this->copyPath($module, $path, Storage::disk('local')->path($path), $absolutePath.'/storage', 'storage', $plan, $backup, $actor));
        }

        foreach (($inventory['packages'] ?? []) as $entry) {
            $path = (string) ($entry['storage_path'] ?? '');
            $items = array_merge($items, $this->copyPath($module, $path, storage_path('app/'.$path), $absolutePath.'/packages', 'packages', $plan, $backup, $actor));
        }

        File::put($absolutePath.'/quarantine_manifest.json', json_encode([
            'schema' => 'longlink.module_cleaner.quarantine_manifest.v1',
            'module_key' => $module->key,
            'module_version' => $module->version,
            'created_at' => now()->toIso8601String(),
            'backup_ref' => $backup['backup_ref'] ?? null,
            'items' => $items,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return [
            'module_key' => $module->key,
            'relative_path' => $relativePath,
            'created_at' => now()->toIso8601String(),
            'items' => $items,
        ];
    }

    /**
     * @param array<string, mixed>|null $plan
     * @param array<string, mixed>|null $backup
     * @return array<int, array<string, mixed>>
     */
    private function copyPath(AddonModule $module, string $sourceLabel, string $sourcePath, string $targetRoot, string $itemType, ?array $plan, ?array $backup, ?User $actor): array
    {
        if ($sourceLabel === '' || ! file_exists($sourcePath)) {
            return [];
        }

        File::ensureDirectoryExists($targetRoot);
        $targetPath = $targetRoot.'/'.$this->safeSegment(basename($sourceLabel) ?: $itemType);

        if (is_dir($sourcePath)) {
            File::copyDirectory($sourcePath, $targetPath);
        } else {
            File::copy($sourcePath, $targetPath);
        }

        $bytes = is_dir($targetPath)
            ? collect(File::allFiles($targetPath))->sum(fn (mixed $file): int => method_exists($file, 'getSize') ? (int) $file->getSize() : 0)
            : (int) filesize($targetPath);

        $item = [
            'item_type' => $itemType,
            'source_path' => $sourceLabel,
            'quarantine_path' => str_replace(storage_path('app/'), '', $targetPath),
            'status' => 'copied',
            'bytes' => $bytes,
            'checksum' => is_file($targetPath) ? hash_file('sha256', $targetPath) : null,
            'metadata' => [
                'copied_at' => now()->toIso8601String(),
                'backup_ref' => $backup['backup_ref'] ?? null,
            ],
        ];

        $this->persistence->recordQuarantineItem(
            $module,
            $item,
            isset($plan['cleaner_plan_id']) ? (int) $plan['cleaner_plan_id'] : null,
            isset($backup['backup_set_id']) ? (int) $backup['backup_set_id'] : null,
            $actor,
        );

        return [$item];
    }

    private function safeSegment(string $value): string
    {
        $segment = strtolower(trim(preg_replace('/[^a-zA-Z0-9_\\-]+/', '_', $value) ?: '', '_'));

        return $segment !== '' ? $segment : 'item';
    }
}
