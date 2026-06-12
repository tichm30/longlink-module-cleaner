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
    $path = module_repo_root().'/module/module.json';
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
