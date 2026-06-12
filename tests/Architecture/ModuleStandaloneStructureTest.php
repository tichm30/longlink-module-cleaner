<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/TestSupport.php';

$root = module_repo_root();
$manifest = module_manifest();

module_assert_same('module_cleaner', $manifest['key'] ?? null, 'Manifest key must stay module_cleaner.');
module_assert_same('0.1.0b', $manifest['version'] ?? null, 'Populate release must be 0.1.0b.');
module_assert_same('Modules\\ModuleCleaner\\ModuleCleanerModuleProvider', $manifest['runtime']['provider'] ?? null, 'Runtime provider must point at the module provider.');
module_assert_same('src/', $manifest['autoload']['psr-4']['Modules\\ModuleCleaner\\'] ?? null, 'PSR-4 autoload root must be src/.');
module_assert_same('0.7.8t-CX', $manifest['requires_host_min_version'] ?? null, 'Cleaner must require the host ownership snapshot/purge baseline.');

$expectedOwnedTables = [
    'module_cleaner_cleanup_logs',
];

module_assert_same($expectedOwnedTables, $manifest['owned_tables'] ?? null, 'Owned-table boundary must include only module_cleaner-prefixed tables.');
module_assert_same($expectedOwnedTables, $manifest['ownership']['tables'] ?? null, 'Ownership block must mirror flat owned_tables.');
module_assert_same($expectedOwnedTables, $manifest['cleanup']['safe_to_delete_tables'] ?? null, 'Cleanup table list must mirror owned_tables.');
module_assert_same(['modules/module_cleaner'], $manifest['ownership']['storage_paths'] ?? null, 'Cleaner must declare one module-owned storage root.');
module_assert(($manifest['cleanup']['requires_backup'] ?? false) === true, 'Cleanup must require backup.');
module_assert(($manifest['cleanup']['requires_step_up_mfa'] ?? false) === true, 'Cleanup must require step-up MFA.');

foreach ($manifest['permissions'] ?? [] as $permission) {
    module_assert(is_string($permission) && str_starts_with($permission, 'module_cleaner.'), 'Permission must be module-prefixed: '.var_export($permission, true));
}

$expectedMigrations = [
    '2026_06_13_020000_create_module_cleaner_cleanup_logs_table',
];

module_assert_same($expectedMigrations, $manifest['database_migrations'] ?? null, 'Manifest migration declarations must match migration files.');

$migrationFiles = array_map(
    static fn (string $path): string => basename($path, '.php'),
    module_files($root.'/module/database/migrations'),
);

module_assert_same($expectedMigrations, $migrationFiles, 'module/database/migrations must contain exactly the declared migrations.');

foreach ([
    'module/src/ModuleCleanerModuleProvider.php',
    'module/src/Http/Controllers/ModuleCleanerController.php',
    'module/src/Support/CleanerAuditLogger.php',
    'module/src/Support/CleanupPlanService.php',
    'module/src/Support/ResidueInventoryService.php',
    'module/routes/web.php',
    'module/resources/views/admin/index.blade.php',
    'module/resources/lang/en/messages.php',
] as $requiredPath) {
    module_assert(is_file($root.'/'.$requiredPath), $requiredPath.' must be present.');
}

$providerSource = (string) file_get_contents($root.'/module/src/ModuleCleanerModuleProvider.php');
module_assert(str_contains($providerSource, 'extends AbstractModuleRuntimeProvider'), 'Provider must inherit the host base provider.');
module_assert(str_contains($providerSource, "! Route::has('admin.module-cleaner.index')"), 'Provider must guard route loading with a sentinel route.');

$planSource = (string) file_get_contents($root.'/module/src/Support/CleanupPlanService.php');
module_assert(str_contains($planSource, 'AddonModuleRegistry'), 'Cleaner must orchestrate through the host registry.');
module_assert(str_contains($planSource, 'purgeModuleResidue'), 'Cleaner dry-runs must use the host purge primitive.');
module_assert(! str_contains($planSource, 'Schema::drop'), 'Cleaner must not drop tables directly.');
module_assert(! str_contains($planSource, 'deleteDirectory'), 'Cleaner must not delete directories directly.');

$inventorySource = (string) file_get_contents($root.'/module/src/Support/ResidueInventoryService.php');
module_assert(str_contains($inventorySource, 'ownership_snapshot'), 'Cleaner inventory must be snapshot-first.');
module_assert(! str_contains($inventorySource, 'Schema::drop'), 'Inventory must never drop tables.');

$migrationSource = (string) file_get_contents($root.'/module/database/migrations/2026_06_13_020000_create_module_cleaner_cleanup_logs_table.php');
module_assert(! str_contains($migrationSource, '->constrained('), 'Cleaner migrations must not hard-link to host tables.');
module_assert(! str_contains($migrationSource, "Schema::table('users'"), 'Cleaner migrations must never touch users.');

foreach (module_files($root.'/module/src') as $phpFile) {
    if (pathinfo($phpFile, PATHINFO_EXTENSION) !== 'php') {
        continue;
    }

    $source = (string) file_get_contents($phpFile);
    module_assert(str_starts_with($source, "<?php\n\nnamespace Modules\\ModuleCleaner"), $phpFile.' must stay under the Modules\\ModuleCleaner namespace.');
}

foreach (module_files($root.'/module') as $file) {
    $relative = substr($file, strlen($root) + 1);
    module_assert(! str_contains($relative, '.DS_Store'), 'macOS metadata must not be present in module/: '.$relative);
    module_assert(! str_ends_with($relative, '.zip'), 'Nested zips must not be present in module/: '.$relative);
    module_assert(basename($relative) !== '.env', '.env must not be present in module/.');
}

echo "ModuleStandaloneStructureTest passed\n";
