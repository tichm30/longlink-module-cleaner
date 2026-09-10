<?php

declare(strict_types=1);
use Composer\Autoload\ClassLoader;

$completed = false;
register_shutdown_function(static function () use (&$completed): void {
    if (! $completed) {
        fwrite(STDERR, "FAIL: test exited before completing its callable.\n");
        exit(1);
    }
});
try {
    require_once dirname(__DIR__).'/TestSupport.php';
    $testPath = $argv[1] ?? '';
    if (! is_file($testPath)) {
        throw new RuntimeException('Test file is missing.');
    }
    $autoload = getenv('LONGLINK_TEST_AUTOLOAD') ?: dirname(__DIR__, 2).'/vendor/autoload.php';
    if (! is_file($autoload)) {
        throw new RuntimeException('Test dependencies missing. Set LONGLINK_TEST_AUTOLOAD to an installed Composer autoloader.');
    }
    $loader = require $autoload;
    if (! $loader instanceof ClassLoader) {
        throw new RuntimeException('Expected a real Composer autoloader.');
    }
    $root = dirname(__DIR__, 2);
    $source = is_file($root.'/module.json') ? $root : $root.'/module';
    $metadata = is_file($root.'/composer.json') ? $root.'/composer.json' : $source.'/module.json';
    $composer = json_decode((string) file_get_contents($metadata), true, flags: JSON_THROW_ON_ERROR);
    $autoloadRoot = is_file($root.'/composer.json') ? $root : $source;
    foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $paths) {
        foreach ((array) $paths as $path) {
            $path = ltrim($path, '/');
            if (is_file($root.'/module.json') && str_starts_with($path, 'module/')) {
                $path = substr($path, strlen('module/'));
            }
            if (! is_dir($autoloadRoot.'/'.$path)) {
                throw new RuntimeException('Declared module autoload directory is missing: '.$path);
            }
            $loader->addPsr4($namespace, $autoloadRoot.'/'.$path, true);
        }
    }
    echo 'Dependency autoloader: '.realpath($autoload)."\n";
    $test = require $testPath;
    if (! is_callable($test)) {
        throw new RuntimeException('Test file did not return a callable.');
    }
    $skip = $test();
    $completed = true;
    if (is_string($skip) && $skip !== '') {
        fwrite(STDERR, 'INCOMPLETE: '.$skip."\n");
        exit(2);
    }
    if ($skip !== null) {
        throw new RuntimeException('Test returned an unsupported result instead of completing normally.');
    }
} catch (Throwable $error) {
    $completed = true;
    fwrite(STDERR, $error::class.': '.$error->getMessage()."\n");
    exit(1);
}
