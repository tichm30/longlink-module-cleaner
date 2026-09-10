<?php

declare(strict_types=1);

function module_repo_root(): string
{
    return dirname(__DIR__);
}

function module_assert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function module_assert_same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message."\nExpected: ".var_export($expected, true)."\nActual: ".var_export($actual, true));
    }
}

/**
 * @return array<string, mixed>
 */
function module_manifest(): array
{
    $path = module_source_root().'/module.json';
    module_assert(is_file($path), 'module/module.json must exist.');

    $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    module_assert(is_array($payload), 'module/module.json must decode to an object.');

    return $payload;
}

/**
 * @return array<int, string>
 */
function module_files(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

function module_source_root(): string
{
    $root = module_repo_root();
    $source = is_file($root.'/module.json') ? $root : $root.'/module';
    module_assert(is_file($source.'/module.json'), 'Module manifest is missing.');

    return $source;
}

function module_path(string $path): string
{
    $path = ltrim($path, '/');

    return str_starts_with($path, 'module/')
        ? module_source_root().'/'.substr($path, strlen('module/'))
        : module_repo_root().'/'.$path;
}
