<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/TestSupport.php';

$root = module_repo_root();
$manifest = module_manifest();

module_assert_same('module_cleaner', $manifest['key'] ?? null, 'Manifest key must stay module_cleaner.');
module_assert_same('Module Cleaner', $manifest['name'] ?? null, 'Manifest display name must avoid redundant host branding.');
module_assert((bool) preg_match('/^0\.1\.0[a-z]{1,2}(?:-CX)?$/', (string) ($manifest['version'] ?? '')), 'Manifest version must be a canonical Module Cleaner 0.1.0 release.');
module_assert_same('Modules\\ModuleCleaner\\ModuleCleanerModuleProvider', $manifest['runtime']['provider'] ?? null, 'Runtime provider must point at the module provider.');
module_assert_same('src/', $manifest['autoload']['psr-4']['Modules\\ModuleCleaner\\'] ?? null, 'PSR-4 autoload root must be src/.');
module_assert_same('0.7.9j-CX', $manifest['requires_host_min_version'] ?? null, 'Cleaner must require the host utility, category-link, stale-navigation cleanup, Platform Tools, and ownership snapshot/purge baseline.');

$expectedOwnedTables = [
    'module_cleaner_cleanup_logs',
];

module_assert_same($expectedOwnedTables, $manifest['owned_tables'] ?? null, 'Owned-table boundary must include only module_cleaner-prefixed tables.');
module_assert_same($expectedOwnedTables, $manifest['ownership']['tables'] ?? null, 'Ownership block must mirror flat owned_tables.');
module_assert_same($expectedOwnedTables, $manifest['cleanup']['safe_to_delete_tables'] ?? null, 'Cleanup table list must mirror owned_tables.');
module_assert_same($manifest['permissions'], $manifest['ownership']['permission_keys'] ?? null, 'Ownership must use canonical permission_keys.');
$expectedSettings = [
    'module_cleaner.require_backup',
    'module_cleaner.require_typed_confirmation',
    'module_cleaner.allow_dry_run',
    'module_cleaner.block_dependents',
    'module_cleaner.enable_orphan_detection',
    'module_cleaner.backup_retention_days',
    'module_cleaner.log_retention_days',
    'module_cleaner.quarantine_retention_days',
    'module_cleaner.email_on_cleanup',
    'module_cleaner.slack_webhook_url',
];
module_assert_same($expectedSettings, $manifest['settings'] ?? null, 'Flat settings list must declare Cleaner settings.');
module_assert_same($expectedSettings, $manifest['ownership']['settings_keys'] ?? null, 'Ownership must use canonical settings_keys.');
module_assert(! array_key_exists('permissions', $manifest['ownership'] ?? []), 'Ownership aliases must not be emitted in 0.1.0d manifests.');
module_assert(! array_key_exists('settings', $manifest['ownership'] ?? []), 'Ownership aliases must not be emitted in 0.1.0d manifests.');
module_assert_same(['modules/module_cleaner', 'module_cleaner/backups', 'module_cleaner/quarantine'], $manifest['ownership']['storage_paths'] ?? null, 'Cleaner must declare module-owned storage roots.');
module_assert_same('quarantine', $manifest['cleanup']['default_mode'] ?? null, 'Cleanup default mode must be canonical quarantine.');
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
    'module/src/Support/ModuleBackupService.php',
    'module/src/Support/OrphanTableDetector.php',
    'module/src/Support/ResidueInventoryService.php',
    'module/src/Settings/ModuleCleanerSettingsCatalog.php',
    'module/routes/web.php',
    'module/resources/views/admin/index.blade.php',
    'module/resources/views/admin/registry.blade.php',
    'module/resources/views/admin/details.blade.php',
    'module/resources/views/admin/plan.blade.php',
    'module/resources/views/admin/orphans.blade.php',
    'module/resources/views/admin/backups.blade.php',
    'module/resources/views/admin/quarantine.blade.php',
    'module/resources/views/admin/logs.blade.php',
    'module/resources/views/admin/settings.blade.php',
    'module/resources/views/admin/partials/section-nav.blade.php',
    'module/resources/lang/en/messages.php',
] as $requiredPath) {
    module_assert(is_file($root.'/'.$requiredPath), $requiredPath.' must be present.');
}

$providerSource = (string) file_get_contents($root.'/module/src/ModuleCleanerModuleProvider.php');
module_assert(str_contains($providerSource, 'extends AbstractModuleRuntimeProvider'), 'Provider must inherit the host base provider.');
module_assert(str_contains($providerSource, "! Route::has('admin.module-cleaner.index')"), 'Provider must guard route loading with a sentinel route.');
module_assert(str_contains($providerSource, 'platform_tools.module_cleaner'), 'Cleaner menu must register under Platform Tools.');
module_assert(str_contains($providerSource, "'broom'"), 'Cleaner menu must use the semantic broom icon.');
module_assert(! str_contains($providerSource, 'settings.module_cleaner'), 'Cleaner must not register as a Settings/Configurations child.');

$routeSource = (string) file_get_contents($root.'/module/routes/web.php');
foreach (['registry', 'modules.show', 'modules.plan.show', 'modules.backup', 'orphans', 'backups', 'quarantine', 'logs', 'settings', 'settings.update'] as $routeName) {
    module_assert(str_contains($routeSource, "->name('".$routeName."')"), 'Cleaner route map must include '.$routeName.'.');
}

$controllerSource = (string) file_get_contents($root.'/module/src/Http/Controllers/ModuleCleanerController.php');
module_assert(! str_contains($controllerSource, "['key' => 'plan'"), 'Cleaner section navigation must not show a duplicate Cleanup Plans link.');

$planSource = (string) file_get_contents($root.'/module/src/Support/CleanupPlanService.php');
module_assert(str_contains($planSource, 'AddonModuleRegistry'), 'Cleaner must orchestrate through the host registry.');
module_assert(str_contains($planSource, 'purgeModuleResidue'), 'Cleaner dry-runs must use the host purge primitive.');
module_assert(str_contains($planSource, "'dry_run' => true"), 'Cleaner planning must call host purge with dry_run=true.');
module_assert(str_contains($planSource, 'ModuleBackupService'), 'Cleaner must create backup evidence before execution.');
module_assert(str_contains($planSource, "route('admin.settings.addon-modules.modules.purge'"), 'Cleaner execution must hand off to the host purge route.');
module_assert(str_contains($planSource, "'backup_confirmed' => true"), 'Cleaner execution payload must require backup confirmation.');
module_assert(str_contains($planSource, 'PROTECTED_MODULE_KEYS'), 'Cleaner planning must protect first-party, generator, and cleaner keys.');
module_assert(str_contains($planSource, 'module_generator'), 'Cleaner must protect the Module Generator from planning.');
module_assert(str_contains($planSource, 'module_cleaner'), 'Cleaner must protect itself from planning.');
module_assert(! str_contains($planSource, 'Schema::drop'), 'Cleaner must not drop tables directly.');
module_assert(! str_contains($planSource, 'deleteDirectory'), 'Cleaner must not delete directories directly.');

$inventorySource = (string) file_get_contents($root.'/module/src/Support/ResidueInventoryService.php');
module_assert(str_contains($inventorySource, 'ownership_snapshot'), 'Cleaner inventory must be snapshot-first.');
module_assert(str_contains($inventorySource, "'protected' => \$protected"), 'Cleaner inventory must expose protected rows to the UI.');
module_assert(str_contains($inventorySource, 'packageInventory'), 'Cleaner inventory must include package residue details.');
module_assert(str_contains($inventorySource, 'moduleFileInventory'), 'Cleaner inventory must include module file details.');
module_assert(str_contains($inventorySource, "Schema::hasColumn('addon_module_packages', 'module_key')"), 'Cleaner package inventory must be schema-aware for module_key.');
module_assert(str_contains($inventorySource, 'package_rows'), 'Cleaner package inventory must use a MariaDB-safe package_rows alias.');
module_assert(str_contains($inventorySource, 'package_bytes'), 'Cleaner package inventory must use a MariaDB-safe package_bytes alias.');
module_assert(! str_contains($inventorySource, 'COUNT(*) as rows'), 'Cleaner package inventory must not use MariaDB reserved alias rows.');
module_assert(! str_contains($inventorySource, 'SUM(file_size_bytes), 0) as bytes'), 'Cleaner package inventory must not use reserved/ambiguous alias bytes.');
module_assert(! str_contains($inventorySource, 'Schema::drop'), 'Inventory must never drop tables.');

$auditSource = (string) file_get_contents($root.'/module/src/Support/CleanerAuditLogger.php');
foreach (['navigation_planned', 'storage_planned', 'packages_planned', 'module_files_planned'] as $column) {
    module_assert(str_contains($auditSource, $column), 'Audit logger must write '.$column.'.');
}

$backupSource = (string) file_get_contents($root.'/module/src/Support/ModuleBackupService.php');
foreach (['ownership_snapshot.json', 'inventory.json', 'permission_rows.json', 'role_permission_rows.json', 'restore_notes.md'] as $needle) {
    module_assert(str_contains($backupSource, $needle), 'Backup service must write '.$needle.'.');
}
foreach (['Schema::drop', 'dropIfExists', 'File::delete', 'Storage::delete', 'unlink(', 'deleteDirectory'] as $forbidden) {
    module_assert(! str_contains($backupSource, $forbidden), 'Backup service must not perform direct destructive operation: '.$forbidden);
}

$orphanSource = (string) file_get_contents($root.'/module/src/Support/OrphanTableDetector.php');
module_assert(str_contains($orphanSource, 'sqlite_master'), 'Orphan detector must support SQLite table discovery.');
module_assert(str_contains($orphanSource, 'information_schema.tables'), 'Orphan detector must support MySQL/MariaDB table discovery.');
module_assert(str_contains($orphanSource, 'pending_review'), 'Orphan detector must mark candidates as review-only.');
foreach (['Schema::drop', 'dropIfExists', 'DB::statement', 'File::delete', 'Storage::delete'] as $forbidden) {
    module_assert(! str_contains($orphanSource, $forbidden), 'Orphan detector must not perform direct destructive operation: '.$forbidden);
}

$settingsSource = (string) file_get_contents($root.'/module/src/Settings/ModuleCleanerSettingsCatalog.php');
foreach ($expectedSettings as $settingKey) {
    module_assert(str_contains($settingsSource, $settingKey), 'Settings catalog must include '.$settingKey.'.');
}

$migrationSource = (string) file_get_contents($root.'/module/database/migrations/2026_06_13_020000_create_module_cleaner_cleanup_logs_table.php');
module_assert(! str_contains($migrationSource, '->constrained('), 'Cleaner migrations must not hard-link to host tables.');
module_assert(! str_contains($migrationSource, "Schema::table('users'"), 'Cleaner migrations must never touch users.');
foreach (['navigation_planned', 'storage_planned', 'packages_planned', 'module_files_planned'] as $column) {
    module_assert(str_contains($migrationSource, $column), 'Cleanup log migration must include '.$column.'.');
}

$viewSource = '';
foreach (module_files($root.'/module/resources/views/admin') as $viewFile) {
    $viewSource .= (string) file_get_contents($viewFile)."\n";
}
module_assert(str_contains($viewSource, '<x-layouts.app-shell'), 'Cleaner UI must use the host app shell.');
module_assert(! str_contains($viewSource, '<x-app-layout'), 'Cleaner UI must not use the missing x-app-layout component.');
module_assert(str_contains($viewSource, '<x-card'), 'Cleaner UI must use host cards.');
module_assert(str_contains($viewSource, '<x-button'), 'Cleaner UI must use host buttons.');
module_assert(str_contains($viewSource, '<x-responsive-category-links'), 'Cleaner UI must use the host category-link component.');
module_assert(str_contains($viewSource, 'settings-category-layout'), 'Cleaner UI must render a desktop left category column.');
module_assert(str_contains($viewSource, 'app.settings.mobile_category_menu'), 'Cleaner UI must render the shared mobile Categories Menu.');
module_assert(str_contains($viewSource, 'data-table-wrap'), 'Cleaner UI must use the host table wrapper.');
module_assert(str_contains($viewSource, 'data-table-wrap is-card-table'), 'Cleaner UI must use the full host mobile-card table wrapper.');
module_assert(str_contains($viewSource, 'data-table is-mobile-card-table'), 'Cleaner UI must use the host mobile-card table class.');
module_assert(str_contains($viewSource, 'class="stack"'), 'Cleaner UI must use the canonical host stack utility between major cards.');
module_assert(! str_contains($viewSource, 'form-stack'), 'Cleaner UI must not ship deprecated module-invented form-stack classes.');
module_assert(! str_contains($viewSource, '@php('), 'Cleaner views must use block-form @php directives so inline expressions do not trip Blade compilation.');
module_assert(str_contains($viewSource, 'pill-list'), 'Cleaner UI must use host token-backed pill layout.');
module_assert(! str_contains($viewSource, 'responsive-data-grid'), 'Cleaner UI must not use module-private responsive grid classes.');
module_assert(! str_contains($viewSource, 'responsive-definition-list'), 'Cleaner UI must not use module-private definition-list classes.');
module_assert(str_contains($viewSource, 'v1 ownership snapshot'), 'Cleaner UI must use v1 ownership snapshot wording.');
module_assert(str_contains($viewSource, 'module_cleaner.purge'), 'Cleaner UI must expose the reserved purge permission boundary.');
module_assert(str_contains($viewSource, "\$entry['protected']"), 'Cleaner UI must disable plans for protected modules.');
module_assert(str_contains($viewSource, 'surface_breakdown'), 'Cleaner registry must hide residue counts behind a row disclosure.');
module_assert(str_contains($viewSource, "dashboard.packages"), 'Cleaner dashboard must include package stat cards.');
foreach (['Module Registry', 'Dry-Run Cleanup Plan', 'Orphan Tables', 'Backups', 'Quarantine', 'Cleanup Logs', 'Cleaner Settings'] as $label) {
    module_assert(str_contains($viewSource, $label) || str_contains($viewSource, 'module_cleaner::messages'), 'Cleaner UI must expose '.$label.'.');
}

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
