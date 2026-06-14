<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/TestSupport.php';

$root = module_repo_root();
$planSource = (string) file_get_contents($root.'/module/src/Support/CleanupPlanService.php');
$inventorySource = (string) file_get_contents($root.'/module/src/Support/ResidueInventoryService.php');
$backupSource = (string) file_get_contents($root.'/module/src/Support/ModuleBackupService.php');
$orphanSource = (string) file_get_contents($root.'/module/src/Support/OrphanTableDetector.php');
$controllerSource = (string) file_get_contents($root.'/module/src/Http/Controllers/ModuleCleanerController.php');
$viewSource = '';
foreach (module_files($root.'/module/resources/views/admin') as $viewFile) {
    $viewSource .= (string) file_get_contents($viewFile)."\n";
}

foreach ([
    "'dry_run' => true" => 'Cleaner must request dry-run plans before execution.',
    "'surfaces' => ['tables', 'settings', 'permissions', 'navigation', 'storage', 'packages', 'module_files']" => 'Cleaner must pass every recognised residue surface to the host primitive.',
    'Protected module - cleanup planning disabled.' => 'Protected modules must fail closed server-side.',
    'ModuleBackupService' => 'Cleaner must create per-module backup evidence before execution.',
    "route('admin.settings.addon-modules.modules.purge'" => 'Cleaner must execute through the host purge route.',
    "'backup_confirmed' => true" => 'Cleaner must keep the host backup guard explicit.',
    'module_generator' => 'Module Generator must be in the protected planning set.',
    'module_cleaner' => 'Module Cleaner must protect itself.',
] as $needle => $message) {
    module_assert(str_contains($planSource, $needle), $message);
}

foreach (['Schema::drop', 'dropIfExists', 'File::delete', 'Storage::delete', 'unlink(', 'deleteDirectory'] as $forbidden) {
    module_assert(! str_contains($planSource, $forbidden), 'Plan service must not perform direct destructive operation: '.$forbidden);
    module_assert(! str_contains($backupSource, $forbidden), 'Backup service must not perform direct destructive operation: '.$forbidden);
    module_assert(! str_contains($orphanSource, $forbidden), 'Orphan detector must not perform direct destructive operation: '.$forbidden);
}

foreach ([
    'ownership_snapshot.json' => 'Backups must include the install-time ownership snapshot.',
    'inventory.json' => 'Backups must include the residue inventory evidence.',
    'role_permission_rows.json' => 'Backups must include role/permission pivot evidence.',
    'restore_notes.md' => 'Backups must include staged restore notes.',
] as $needle => $message) {
    module_assert(str_contains($backupSource, $needle), $message);
}

foreach ([
    'module_cleaner.enable_orphan_detection' => 'Orphan detection must be governed by persisted settings.',
    'OrphanTableDetector' => 'Controller must call the review-only orphan detector.',
    'SettingsStore' => 'Controller must persist Cleaner settings through host settings.',
] as $needle => $message) {
    module_assert(str_contains($controllerSource, $needle), $message);
}

foreach ([
    'protected_reason' => 'Inventory must carry a visible protected reason.',
    'packageInventory' => 'Inventory must include package rows.',
    'moduleFileInventory' => 'Inventory must include module file rows.',
    'storageInventory' => 'Inventory must include storage path rows.',
] as $needle => $message) {
    module_assert(str_contains($inventorySource, $needle), $message);
}

foreach ([
    'protected_reason' => 'UI must show the protected-module reason.',
    'No v1 ownership snapshot is available for this module.' => 'UI must explain no-snapshot modules.',
    ':disabled="! $entry[\'has_snapshot\'] || $entry[\'protected\']"' => 'UI must disable dry-run for protected/no-snapshot modules.',
    'module_cleaner.purge' => 'UI must explain the reserved purge permission boundary.',
    'admin.settings.addon-modules.modules.purge' => 'UI must post final execution to host purge route.',
    'backup_confirmed' => 'UI must pass backup confirmation to host purge.',
    'details.tables' => 'UI must provide per-module residue table details.',
    'details.storage' => 'UI must provide per-module storage residue details.',
    'details.packages' => 'UI must provide per-module package residue details.',
] as $needle => $message) {
    module_assert(str_contains($viewSource, $needle), $message);
}

echo "CleanerSafetyContractTest passed\n";
