<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$tests = [
    $root.'/tests/Architecture/ModuleStandaloneStructureTest.php',
    $root.'/tests/Feature/ModuleReleaseZipTest.php',
];

foreach ($tests as $test) {
    require $test;
}

echo "All module-cleaner tests passed\n";
