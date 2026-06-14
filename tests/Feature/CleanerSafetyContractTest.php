<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/TestSupport.php';

$root = module_repo_root();
$planSource = (string) file_get_contents($root.'/module/src/Support/CleanupPlanService.php');
$inventorySource = (string) file_get_contents($root.'/module/src/Support/ResidueInventoryService.php');
$viewSource = '';
foreach (module_files($root.'/module/resources/views/admin') as $viewFile) {
    $viewSource .= (string) file_get_contents($viewFile)."\n";
}

foreach ([
    "'dry_run' => true" => 'Cleaner must only request dry-run plans in 0.1.0g.',
    "'surfaces' => ['tables', 'settings', 'permissions', 'navigation', 'storage', 'packages', 'module_files']" => 'Cleaner must pass every recognised residue surface to the host primitive.',
    'Protected module - cleanup planning disabled.' => 'Protected modules must fail closed server-side.',
    'module_generator' => 'Module Generator must be in the protected planning set.',
    'module_cleaner' => 'Module Cleaner must protect itself.',
] as $needle => $message) {
    module_assert(str_contains($planSource, $needle), $message);
}

foreach (['Schema::drop', 'dropIfExists', 'File::delete', 'Storage::delete', 'unlink(', 'deleteDirectory'] as $forbidden) {
    module_assert(! str_contains($planSource, $forbidden), 'Plan service must not perform direct destructive operation: '.$forbidden);
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
    'details.tables' => 'UI must provide per-module residue table details.',
    'details.storage' => 'UI must provide per-module storage residue details.',
    'details.packages' => 'UI must provide per-module package residue details.',
] as $needle => $message) {
    module_assert(str_contains($viewSource, $needle), $message);
}

echo "CleanerSafetyContractTest passed\n";
