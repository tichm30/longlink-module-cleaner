<?php

namespace Modules\ModuleCleaner\Support;

use App\Models\AddonModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OrphanTableDetector
{
    private const CORE_TABLES = [
        'addon_module_packages',
        'addon_modules',
        'api_tokens',
        'audit_logs',
        'cache',
        'cache_locks',
        'failed_jobs',
        'jobs',
        'job_batches',
        'migrations',
        'navigation_items',
        'password_reset_tokens',
        'permissions',
        'role_permission',
        'roles',
        'sessions',
        'settings',
        'users',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function candidates(): array
    {
        $known = $this->knownTables();
        $modulePrefixes = $this->modulePrefixes();

        return collect($this->databaseTables())
            ->reject(fn (string $table): bool => in_array($table, self::CORE_TABLES, true))
            ->reject(fn (string $table): bool => in_array($table, $known, true))
            ->map(fn (string $table): array => [
                'name' => $table,
                'rows' => $this->rowCount($table),
                'confidence' => $this->confidence($table, $modulePrefixes),
                'evidence' => $this->evidence($table, $modulePrefixes),
                'disposition' => 'pending_review',
            ])
            ->filter(fn (array $candidate): bool => $candidate['confidence'] !== 'ignore')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function databaseTables(): array
    {
        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                return collect(DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))
                    ->pluck('name')
                    ->map(fn (mixed $name): string => (string) $name)
                    ->values()
                    ->all();
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                return collect(DB::select('select table_name as name from information_schema.tables where table_schema = database()'))
                    ->pluck('name')
                    ->map(fn (mixed $name): string => (string) $name)
                    ->values()
                    ->all();
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function knownTables(): array
    {
        return AddonModule::query()
            ->get()
            ->flatMap(fn (AddonModule $module) => data_get($module->metadata ?: [], 'ownership_snapshot.tables', []))
            ->filter(fn (mixed $table): bool => is_string($table) && $table !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function modulePrefixes(): array
    {
        return AddonModule::query()
            ->pluck('key')
            ->map(fn (mixed $key): string => trim((string) $key))
            ->filter()
            ->map(fn (string $key): string => $key.'_')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $prefixes
     */
    private function confidence(string $table, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($table, $prefix)) {
                return 'high';
            }
        }

        if (str_contains($table, '_')) {
            return 'medium';
        }

        return 'ignore';
    }

    /**
     * @param  array<int, string>  $prefixes
     * @return array<int, string>
     */
    private function evidence(string $table, array $prefixes): array
    {
        $evidence = ['not_declared_by_any_install_time_snapshot'];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($table, $prefix)) {
                $evidence[] = 'matches_installed_module_prefix:'.$prefix;
            }
        }

        return $evidence;
    }

    private function rowCount(string $table): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        try {
            return DB::table($table)->count();
        } catch (Throwable) {
            return null;
        }
    }
}
