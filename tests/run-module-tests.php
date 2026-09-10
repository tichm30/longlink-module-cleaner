<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$packageMode = ! file_exists($root.'/.git');
if (! is_file($root.'/module.json') && ! is_file($root.'/module/module.json')) {
    fwrite(STDERR, "FAIL: module.json is missing from the actual module root.\n");
    exit(1);
}
$tests = [];
foreach (['Architecture', 'Database', 'Feature'] as $directory) {
    foreach (glob(__DIR__.'/'.$directory.'/*Test.php') ?: [] as $test) {
        $tests[] = $test;
    }
}
sort($tests);
$passed = 0;
$failed = [];
$skipped = [];
foreach ($tests as $test) {
    $name = basename(dirname($test)).'/'.basename($test);
    if ($packageMode && $name === 'Feature/ModuleReleaseZipTest.php') {
        $skipped[] = $name;
        echo "SKIP {$name}: outer release ZIP is not nested in an extracted module.\n";

        continue;
    }
    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/Support/execute-test.php').' '.escapeshellarg($test).' 2>&1', $output, $status);
    echo implode("\n", $output)."\n";
    if ($status !== 0 || preg_match('/\\bSKIP(?:PED)?\\b/i', implode("\n", $output))) {
        $failed[] = $name;
        fwrite(STDERR, "FAIL {$name}: child exit {$status}; incomplete tests are not passes.\n");
    } else {
        $passed++;
        echo "PASS {$name}\n";
    }
}
echo json_encode(['mode' => $packageMode ? 'package' : 'repository', 'total' => count($tests),
    'passed' => $passed, 'skipped' => $skipped, 'failed' => $failed], JSON_THROW_ON_ERROR)."\n";
exit($tests !== [] && $failed === [] ? 0 : 1);
