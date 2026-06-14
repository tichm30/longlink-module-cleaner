<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/TestSupport.php';

$root = module_repo_root();
$manifest = module_manifest();
$version = (string) ($manifest['version'] ?? '');
$zip = $root.'/releases/longlink-module-cleaner-'.$version.'.zip';

module_assert($version !== '', 'Manifest version must be present.');
module_assert(is_file($zip), 'Release zip must exist: '.$zip);
module_assert(is_file($zip.'.sha256'), 'Release zip checksum sidecar must exist.');

$archive = new ZipArchive;
$opened = $archive->open($zip);

module_assert($opened === true, 'Release zip must open.');

$names = [];
for ($i = 0; $i < $archive->numFiles; $i++) {
    $name = (string) $archive->getNameIndex($i);
    $names[] = $name;
    module_assert(str_starts_with($name, 'module_cleaner/'), 'Zip entries must be rooted at module_cleaner/: '.$name);
    module_assert(! str_contains($name, '__MACOSX'), 'Zip must not include __MACOSX metadata.');
    module_assert(! str_contains($name, '/.git/'), 'Zip must not include git metadata.');
    module_assert(! str_ends_with($name, '/.env'), 'Zip must not include .env files.');
}

$archive->close();

foreach ([
    'module_cleaner/module.json',
    'module_cleaner/src/ModuleCleanerModuleProvider.php',
    'module_cleaner/routes/web.php',
    'module_cleaner/resources/views/admin/index.blade.php',
    'module_cleaner/resources/views/admin/registry.blade.php',
    'module_cleaner/resources/views/admin/details.blade.php',
    'module_cleaner/resources/views/admin/plan.blade.php',
    'module_cleaner/resources/views/admin/orphans.blade.php',
    'module_cleaner/resources/views/admin/backups.blade.php',
    'module_cleaner/resources/views/admin/quarantine.blade.php',
    'module_cleaner/resources/views/admin/logs.blade.php',
    'module_cleaner/resources/views/admin/settings.blade.php',
    'module_cleaner/resources/views/admin/partials/section-nav.blade.php',
] as $requiredEntry) {
    module_assert(in_array($requiredEntry, $names, true), 'Zip missing '.$requiredEntry);
}

$checksum = trim((string) file_get_contents($zip.'.sha256'));
module_assert(str_contains($checksum, 'longlink-module-cleaner-'.$version.'.zip'), 'Checksum sidecar must reference the release zip.');

echo "ModuleReleaseZipTest passed\n";
