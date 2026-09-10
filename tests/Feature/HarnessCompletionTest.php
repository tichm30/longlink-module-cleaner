<?php

return function (): void {
    $directory = sys_get_temp_dir().'/longlink-harness-'.bin2hex(random_bytes(8));
    if (! mkdir($directory, 0700)) {
        throw new RuntimeException('Cannot create isolated test directory.');
    }
    $cases = [
        'complete' => ['return static function (): void {};', 0],
        'early-success' => ['exit(0);', 1],
        'early-in-callable' => ['return static function (): void { exit(0); };', 1],
        'exception' => ['return static function (): void { throw new RuntimeException("negative control"); };', 1],
        'incomplete' => ['return static function (): string { return "dependencies unavailable"; };', 2],
        'false-result' => ['return static function (): bool { return false; };', 1],
    ];
    $failures = [];
    try {
        foreach ($cases as $name => [$source, $expected]) {
            $file = $directory.'/'.$name.'.php';
            file_put_contents($file, "<?php\n".$source);
            $output = [];
            $status = 0;
            exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/Support/execute-test.php').' '.escapeshellarg($file).' 2>&1', $output, $status);
            if ($status !== $expected) {
                $failures[] = $name.': expected '.$expected.', got '.$status;
            }
        }
    } finally {
        foreach (glob($directory.'/*.php') ?: [] as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
    if ($failures !== []) {
        throw new RuntimeException(implode('; ', $failures));
    }
};
