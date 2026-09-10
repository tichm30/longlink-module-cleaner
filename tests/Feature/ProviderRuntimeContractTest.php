<?php

return static function (): void {
    $class = module_manifest()['runtime']['provider'];
    $provider = new $class(new Illuminate\Foundation\Application(module_source_root()));
    module_assert($provider instanceof App\Support\Modules\ModuleRuntimeContract, 'Provider must instantiate against the actual host runtime contract.');
    module_assert_same('module_cleaner', $provider->moduleKey(), 'Provider identity must match the manifest.');
};
